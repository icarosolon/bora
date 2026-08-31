import type { Page } from '@playwright/test'
import { expect, test } from './base'

/**
 * US4 — recuperar senha, nas duas larguras obrigatórias.
 *
 * O ponto mais delicado desta tela é o que ela **não** pode fazer: distinguir
 * e-mail cadastrado de não cadastrado. Qualquer diferença visível — texto,
 * ícone, caminho — desfaria a não-enumeração que o backend protege.
 */

async function semRolagemHorizontal(page: Page) {
  const { scrollWidth, clientWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }))

  expect(scrollWidth, 'a página tem rolagem horizontal').toBeLessThanOrEqual(clientWidth)
}

const MENSAGEM_NEUTRA =
  'Se este e-mail estiver cadastrado, você receberá um link para redefinir a senha.'

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/eu', (rota) =>
    rota.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Faça login para continuar.' }),
    }),
  )

  await page.route('**/api/v1/senha/esqueci', (rota) =>
    rota.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ message: MENSAGEM_NEUTRA }),
    }),
  )
})

test.describe('esqueci minha senha', () => {
  test('é alcançável a partir da tela de entrar', async ({ page }) => {
    await page.goto('/entrar')

    await page.getByRole('link', { name: 'Esqueci minha senha' }).click()

    await expect(page).toHaveURL(/\/esqueci-senha/)
    await expect(page.getByRole('heading', { name: 'Esqueci minha senha' })).toBeVisible()
    await semRolagemHorizontal(page)
  })

  test('a tela de sucesso ensina o próximo passo', async ({ page }) => {
    await page.goto('/esqueci-senha')
    await page.getByLabel('E-mail').fill('maria@exemplo.com')
    await page.getByRole('button', { name: 'Enviar link' }).click()

    const principal = page.getByRole('main')
    await expect(principal).toContainText('Se este e-mail estiver cadastrado')
    // ux-requirements.md: estado de sucesso ensina, não só confirma.
    await expect(principal).toContainText('spam')
    await expect(principal).toContainText('1 hora')
    await semRolagemHorizontal(page)
  })

  test('a tela não revela se o e-mail existe', async ({ page }) => {
    // O mesmo texto para qualquer e-mail: a tela repete a API, não interpreta.
    await page.goto('/esqueci-senha')
    await page.getByLabel('E-mail').fill('ninguem@exemplo.com')
    await page.getByRole('button', { name: 'Enviar link' }).click()

    await expect(page.getByRole('main')).toContainText('Se este e-mail estiver cadastrado')
    await expect(page.getByRole('main')).not.toContainText(/não encontrad|não existe|inválid/i)
  })

  test('a ação principal tem alvo de toque confortável', async ({ page }) => {
    await page.goto('/esqueci-senha')

    const caixa = await page.getByRole('button', { name: 'Enviar link' }).boundingBox()

    expect(caixa?.height ?? 0).toBeGreaterThanOrEqual(44)
  })
})

test.describe('criar nova senha', () => {
  test('salva e manda entrar, sem deixar a senha na URL', async ({ page }) => {
    await page.route('**/api/v1/senha/redefinir', (rota) =>
      rota.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Senha alterada. Você já pode entrar com ela.' }),
      }),
    )

    await page.goto('/redefinir-senha?token=abc')
    await page.getByLabel('Nova senha').fill('senhaSuperSecreta123')
    await page.getByRole('button', { name: 'Salvar nova senha' }).click()

    await expect(page).toHaveURL(/\/entrar/)
    expect(page.url(), 'a senha não pode aparecer na URL').not.toContain('senhaSuperSecreta123')
  })

  test('avisa que as outras sessões serão encerradas', async ({ page }) => {
    // Efeito colateral relevante: a pessoa precisa saber antes, não descobrir
    // depois no outro aparelho.
    await page.goto('/redefinir-senha?token=abc')

    await expect(page.getByRole('main')).toContainText('outros aparelhos')
  })

  test('explica e oferece novo link quando o token expirou', async ({ page }) => {
    await page.route('**/api/v1/senha/redefinir', (rota) =>
      rota.fulfill({
        status: 410,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Este link expirou ou já foi usado. Peça um novo link em "Esqueci minha senha".',
        }),
      }),
    )

    await page.goto('/redefinir-senha?token=velho')
    await page.getByLabel('Nova senha').fill('senhaNova123')
    await page.getByRole('button', { name: 'Salvar nova senha' }).click()

    await expect(page.getByRole('main').getByRole('alert')).toContainText('expirou')
    await expect(page.getByRole('link', { name: 'Pedir um novo link' })).toBeVisible()
  })

  test('explica em vez de quebrar quando não há token', async ({ page }) => {
    await page.goto('/redefinir-senha')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('Link inválido')
    await expect(page.getByRole('link', { name: 'Pedir um novo link' })).toBeVisible()
    await semRolagemHorizontal(page)
  })

  test('mostra o erro no campo quando a senha é curta', async ({ page }) => {
    await page.route('**/api/v1/senha/redefinir', (rota) =>
      rota.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Confira os campos destacados.',
          errors: { senha: ['A senha precisa de pelo menos 8 caracteres.'] },
        }),
      }),
    )

    await page.goto('/redefinir-senha?token=abc')
    await page.getByLabel('Nova senha').fill('curta')
    await page.getByRole('button', { name: 'Salvar nova senha' }).click()

    await expect(page.getByRole('main').getByRole('alert')).toContainText('pelo menos 8')
    await expect(page.getByLabel('Nova senha')).toHaveAttribute('aria-invalid', 'true')
  })
})
