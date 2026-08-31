import { expect, test, type Page } from '@playwright/test'

/**
 * US1 ponta a ponta nas DUAS larguras obrigatórias (ux-requirements.md).
 *
 * O `playwright.config.ts` define os projetos `celular-360` e
 * `computador-1280`, então cada teste daqui roda duas vezes.
 *
 * A API é interceptada de propósito: este teste verifica a TELA — layout,
 * acessibilidade, estados e navegação. O comportamento do servidor já é
 * coberto por 99 testes de backend; depender dele aqui tornaria a suíte de
 * tela lenta e frágil sem cobrir nada novo.
 */

async function interceptarApi(page: Page) {
  await page.route('**/api/v1/eu', (rota) =>
    rota.fulfill({ status: 401, contentType: 'application/json', body: JSON.stringify({ message: 'Faça login para continuar.' }) }),
  )
}

/**
 * A asserção que o `ux-requirements.md` exige explicitamente: a 360px não pode
 * haver rolagem horizontal nem elemento estourando a largura.
 */
async function semRolagemHorizontal(page: Page) {
  const { scrollWidth, clientWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }))

  expect(scrollWidth, 'a página tem rolagem horizontal').toBeLessThanOrEqual(clientWidth)
}

test.beforeEach(async ({ page }) => {
  await interceptarApi(page)
})

test.describe('tela de entrar', () => {
  test('não tem rolagem horizontal e mostra a ação principal', async ({ page }) => {
    await page.goto('/entrar')

    await expect(page.getByRole('heading', { name: 'Entrar', level: 1 })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Entrar', exact: true })).toBeVisible()
    await semRolagemHorizontal(page)
  })

  test('a ação principal tem alvo de toque confortável', async ({ page }) => {
    await page.goto('/entrar')

    const caixa = await page.getByRole('button', { name: 'Entrar', exact: true }).boundingBox()

    // ux-requirements.md: alvos de toque >= 44px.
    expect(caixa?.height ?? 0).toBeGreaterThanOrEqual(44)
  })

  test('leva para criar conta e para esqueci minha senha', async ({ page }) => {
    await page.goto('/entrar')

    await expect(page.getByRole('link', { name: 'Esqueci minha senha' })).toBeVisible()

    await page.getByRole('link', { name: 'Criar conta' }).click()
    await expect(page).toHaveURL(/\/criar-conta/)
  })
})

test.describe('tela de criar conta', () => {
  test('não tem rolagem horizontal e os campos têm rótulo', async ({ page }) => {
    await page.goto('/criar-conta')

    await expect(page.getByLabel('E-mail')).toBeVisible()
    await expect(page.getByLabel('Senha')).toBeVisible()
    await expect(page.getByLabel('Como você quer ser chamado')).toBeVisible()
    await semRolagemHorizontal(page)
  })

  test('mostra o erro no campo quando a API recusa', async ({ page }) => {
    await page.route('**/api/v1/contas', (rota) =>
      rota.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Confira os campos destacados.',
          errors: { email: ['Este e-mail já tem conta. Entre com sua senha ou use "Esqueci minha senha".'] },
        }),
      }),
    )

    await page.goto('/criar-conta')
    await page.getByLabel('E-mail').fill('maria@exemplo.com')
    await page.getByLabel('Senha').fill('senhaforte1')
    await page.getByRole('button', { name: 'Criar conta' }).click()

    // Escopado ao `main` de propósito: em desenvolvimento o Next injeta um
    // elemento com role="alert" próprio (barra de ferramentas), e um seletor
    // solto casaria com os dois.
    const alerta = page.getByRole('main').getByRole('alert')

    // Princípio I visto pela tela: recusa com orientação, não conta paralela.
    await expect(alerta).toContainText('já tem conta')
    // E o erro aparece NO CAMPO, não só numa faixa geral (ux-requirements.md).
    await expect(page.getByLabel('E-mail')).toHaveAttribute('aria-invalid', 'true')
    await semRolagemHorizontal(page)
  })

  test('navega por teclado até a ação principal', async ({ page }) => {
    await page.goto('/criar-conta')

    // Espera a página ficar interativa: até hidratar, o botão de envio fica
    // desabilitado de propósito (ver FormularioBase e E-012), e `Tab` pula
    // controle desabilitado.
    await expect(page.getByRole('button', { name: 'Criar conta' })).toBeEnabled()

    await page.getByLabel('Como você quer ser chamado').focus()
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')
    await page.keyboard.press('Tab')

    await expect(page.getByRole('button', { name: 'Criar conta' })).toBeFocused()
  })
})

test.describe('cabeçalho', () => {
  test('oferece entrar para quem não está autenticado', async ({ page }) => {
    await page.goto('/entrar')

    // "Sair" e "Entrar" com rótulo de texto, nunca ícone solto.
    await expect(page.getByRole('banner').getByRole('link', { name: 'Entrar' })).toBeVisible()
  })
})

test.describe('regressão: submissão antes da hidratação', () => {
  test('o formulário nunca submete de forma nativa, com a senha na URL', async ({ page }) => {
    /*
     * Antes da hidratação o onSubmit do React não existe, e um toque fazia o
     * navegador submeter nativamente — indo para a mesma página com os campos
     * na query string, ou seja, COM A SENHA NA URL (E-012).
     *
     * O botão agora só habilita depois de montar, então o Playwright espera
     * sozinho. A asserção que importa é a da URL.
     */
    await page.route('**/api/v1/contas', (rota) =>
      rota.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'x', errors: { nome: ['Digite seu nome.'] } }),
      }),
    )

    await page.goto('/criar-conta')
    await page.getByLabel('E-mail').fill('maria@exemplo.com')
    await page.getByLabel('Senha').fill('senhaSuperSecreta123')
    await page.getByRole('button', { name: 'Criar conta' }).click()

    await expect(page.getByRole('main').getByRole('alert')).toContainText('Digite seu nome')

    const url = page.url()
    expect(url, 'a senha não pode aparecer na URL').not.toContain('senhaSuperSecreta123')
    expect(url, 'o formulário não pode ter submetido nativamente').not.toContain('?')
  })
})
