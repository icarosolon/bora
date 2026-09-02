import type { Page } from '@playwright/test'
import { withValidSession, expect, test } from './base'

/**
 * US2 — entrar com Google, nas duas larguras obrigatórias.
 *
 * O provedor é **simulado**: nenhum teste fala com o Google de verdade. Isso é
 * deliberado — depender de conta externa e de rede tornaria a suíte lenta,
 * frágil e impossível de rodar em CI, sem cobrir nada que os 130 testes de
 * backend já não cubram. O que se verifica aqui é a TELA.
 */

async function noHorizontalScroll(page: Page) {
  const { scrollWidth, clientWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }))

  expect(scrollWidth, 'a página tem rolagem horizontal').toBeLessThanOrEqual(clientWidth)
}

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/eu', (route) =>
    route.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Faça login para continuar.' }),
    }),
  )
})

test.describe('botão do Google na tela de entrar', () => {
  test('aparece acima do formulário de e-mail e senha', async ({ page }) => {
    await page.goto('/entrar')

    const googleButton = page.getByRole('button', { name: 'Entrar com Google' })
    const emailField = page.getByLabel('E-mail')

    await expect(googleButton).toBeVisible()

    // O caminho de menor fricção vem primeiro (US2, decisão de tela).
    const buttonY = (await googleButton.boundingBox())?.y ?? 0
    const fieldY = (await emailField.boundingBox())?.y ?? 0
    expect(buttonY).toBeLessThan(fieldY)

    await noHorizontalScroll(page)
  })

  test('tem alvo de toque confortável', async ({ page }) => {
    await page.goto('/entrar')

    const box = await page.getByRole('button', { name: 'Entrar com Google' }).boundingBox()

    expect(box?.height ?? 0).toBeGreaterThanOrEqual(44)
  })

  test('leva para a URL de autorização devolvida pela API', async ({ page }) => {
    await page.route('**/api/v1/auth/google/url', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { url: '/entrar?veio-do-google=1', state: 'estado-1' } }),
      }),
    )

    await page.goto('/entrar')
    await page.getByRole('button', { name: 'Entrar com Google' }).click()

    await expect(page).toHaveURL(/veio-do-google=1/)
  })
})

test.describe('retorno do Google', () => {
  test('entra e guarda o token quando dá certo', async ({ page }) => {
    await withValidSession(page)
    await page.route('**/api/v1/auth/google/sessoes', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            account: { id: 1, name: 'Maria' },
            token: 'tok-google',
            expires_at: '2026-09-30T00:00:00-03:00',
          },
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')

    await expect(page).toHaveURL(/\/$|\/\?/)
    expect(await page.evaluate(() => localStorage.getItem('bora.session.token'))).toBe('tok-google')
  })

  test('o token nunca aparece na URL', async ({ page }) => {
    await withValidSession(page)
    await page.route('**/api/v1/auth/google/sessoes', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: { account: { id: 1, name: 'Maria' }, token: 'tok-secreto', expires_at: '2026-09-30T00:00:00-03:00' },
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')
    await page.waitForURL(/\/$|\/\?/)

    // É a razão de o retorno apontar para o web/ e não para a API.
    expect(page.url()).not.toContain('tok-secreto')
  })

  test('mostra mensagem humana e caminho de volta quando o Google recusa', async ({ page }) => {
    await page.route('**/api/v1/auth/google/sessoes', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Não deu para entrar com o Google agora. Tente de novo ou use seu e-mail e senha.',
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('e-mail e senha')
    await expect(page.getByRole('link', { name: 'Voltar para entrar' })).toBeVisible()
    await noHorizontalScroll(page)
  })

  test('trata o cancelamento na tela do Google', async ({ page }) => {
    // O Google volta com `error` e sem `code` quando a pessoa cancela.
    await page.goto('/entrar/google/retorno?error=access_denied')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('Google')
    expect(await page.evaluate(() => localStorage.getItem('bora.session.token'))).toBeNull()
  })

  test('manda para a união quando o e-mail já tem conta', async ({ page }) => {
    await page.route('**/api/v1/auth/google/sessoes', (route) =>
      route.fulfill({
        status: 409,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Você já tem conta no Bora com este e-mail.',
          data: {
            status: 'merge_required',
            email: 'maria@exemplo.com',
            merge_token: 'tok-uniao',
            expires_at: '2026-08-31T13:00:00-03:00',
          },
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')

    // A tela de destino é da US3; aqui garante-se o encaminhamento e que
    // NENHUMA sessão foi aberta (nada foi gravado no servidor tampouco).
    await expect(page).toHaveURL(/\/unir-contas$/)
    expect(await page.evaluate(() => localStorage.getItem('bora.session.token'))).toBeNull()

    // O token de união vai por `sessionStorage`, NUNCA pela URL: na query
    // string ele cairia no histórico, no log de servidor e no `Referer`.
    expect(page.url(), 'token de união não pode ir na URL').not.toContain('tok-uniao')
    expect(
      await page.evaluate(() => sessionStorage.getItem('bora.merge.pending')),
      'o pedido precisa ficar guardado para a tela de união',
    ).toContain('tok-uniao')
  })
})

/**
 * US2-5 / FR-012 — conta nascida do Google ganhar uma senha.
 *
 * O caso existe porque a tela `/definir-senha` esteve implementada e
 * **inalcançável**: nada no produto levava até ela, então a pessoa que entrava
 * pelo Google não tinha caminho visível para ganhar uma senha. Estes testes
 * cobrem o CAMINHO, que é justamente o que faltava — a tela em si já tinha
 * teste de backend.
 */
test.describe('caminho para definir a primeira senha', () => {
  async function entrarComoContaDoGoogle(page: Page) {
    await withValidSession(page, ['google'])
    await page.goto('/')
    await page.evaluate(() => localStorage.setItem('bora.session.token', 'tok-google'))
    await page.reload()
  }

  test('oferece definir senha a quem só entra pelo Google', async ({ page }) => {
    await entrarComoContaDoGoogle(page)

    const link = page.getByRole('link', { name: 'Definir senha' })
    await expect(link).toBeVisible()

    // Alvo de toque: ux-requirements.md exige 44px de altura.
    const box = await link.boundingBox()
    expect(box?.height ?? 0).toBeGreaterThanOrEqual(44)

    // A faixa é larga; a 360px ela não pode empurrar a página para os lados.
    await noHorizontalScroll(page)
  })

  test('o toque leva à tela de definir senha, e lá a faixa some', async ({ page }) => {
    await entrarComoContaDoGoogle(page)

    await page.getByRole('link', { name: 'Definir senha' }).click()

    await expect(page).toHaveURL(/\/definir-senha$/)
    await expect(page.getByRole('heading', { name: 'Definir senha' })).toBeVisible()

    // Oferecer o caminho para onde a pessoa já chegou seria ruído.
    await expect(page.getByRole('link', { name: 'Definir senha' })).toHaveCount(0)
    await noHorizontalScroll(page)
  })

  test('não aparece para quem já tem senha', async ({ page }) => {
    await withValidSession(page, ['password', 'google'])
    await page.goto('/')
    await page.evaluate(() => localStorage.setItem('bora.session.token', 'tok-sessao'))
    await page.reload()

    await expect(page.getByRole('button', { name: 'Sair' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Definir senha' })).toHaveCount(0)
  })
})
