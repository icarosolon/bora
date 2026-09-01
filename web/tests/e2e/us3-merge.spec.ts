import type { Page } from '@playwright/test'
import { withValidSession, expect, test } from './base'

/**
 * US3 — unir credenciais, nas duas larguras obrigatórias.
 *
 * A tela é a única do produto em que a pessoa entra por um caminho (Google) e
 * é interrompida por outro (senha). Se ela não se explicar, a reação natural é
 * desconfiar — por isso os testes verificam o TEXTO, não só o fluxo.
 */

async function noHorizontalScroll(page: Page) {
  const { scrollWidth, clientWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }))

  expect(scrollWidth, 'a página tem rolagem horizontal').toBeLessThanOrEqual(clientWidth)
}

const URL_UNIAO = '/unir-contas'

/**
 * O pedido de união vem por `sessionStorage`, não pela URL — token em query
 * string cairia no histórico, no log de servidor e no `Referer` (fechado no
 * Polish da spec 001). Por isso os testes semeiam o pedido antes de navegar.
 */
async function semearUniaoPendente(page: Page) {
  await page.addInitScript(() => {
    sessionStorage.setItem(
      'bora.merge.pending',
      JSON.stringify({ token: 'tok-uniao', email: 'maria@exemplo.com' }),
    )
  })
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

test.describe('tela de unir contas', () => {
  test('explica o que vai acontecer e não tem rolagem horizontal', async ({ page }) => {
    await semearUniaoPendente(page)
    await page.goto(URL_UNIAO)

    const explanation = page.getByRole('main').getByRole('status')
    await expect(explanation).toContainText('maria@exemplo.com')
    await expect(explanation).toContainText('mesma conta')

    await expect(page.getByRole('button', { name: 'Unir e entrar' })).toBeVisible()
    await noHorizontalScroll(page)
  })

  test('a ação principal tem alvo de toque confortável', async ({ page }) => {
    await semearUniaoPendente(page)
    await page.goto(URL_UNIAO)

    const box = await page.getByRole('button', { name: 'Unir e entrar' }).boundingBox()

    expect(box?.height ?? 0).toBeGreaterThanOrEqual(44)
  })

  test('une com a senha certa e não deixa a senha na URL', async ({ page }) => {
    await withValidSession(page)
    await page.route('**/api/v1/uniao-credenciais', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Pronto — agora você pode entrar com Google ou com sua senha.',
          data: { account: { id: 1, name: 'Maria' }, token: 'tok-sessao', expires_at: '2026-09-30T00:00:00-03:00' },
        }),
      }),
    )

    await semearUniaoPendente(page)
    await page.goto(URL_UNIAO)
    await page.getByLabel('Sua senha do Bora').fill('senhaSuperSecreta123')
    await page.getByRole('button', { name: 'Unir e entrar' }).click()

    await page.waitForURL((u) => !u.pathname.startsWith('/unir-contas'))

    expect(page.url(), 'a senha não pode aparecer na URL').not.toContain('senhaSuperSecreta123')
    expect(await page.evaluate(() => localStorage.getItem('bora.session.token'))).toBe('tok-sessao')
  })

  test('mostra a mensagem e mantém o plano B quando a senha erra', async ({ page }) => {
    await page.route('**/api/v1/uniao-credenciais', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Senha não confere. Tente de novo ou receba um link por e-mail.' }),
      }),
    )

    await semearUniaoPendente(page)
    await page.goto(URL_UNIAO)
    await page.getByLabel('Sua senha do Bora').fill('errada123')
    await page.getByRole('button', { name: 'Unir e entrar' }).click()

    await expect(page.getByRole('main').getByRole('alert')).toContainText('não confere')
    await expect(page.getByRole('button', { name: 'Receber link por e-mail' })).toBeVisible()
    await noHorizontalScroll(page)
  })

  test('confirma o envio do link do plano B', async ({ page }) => {
    await page.route('**/api/v1/uniao-credenciais/link', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Enviamos um link para o seu e-mail. Ele vale por 1 hora.' }),
      }),
    )

    await semearUniaoPendente(page)
    await page.goto(URL_UNIAO)
    await page.getByRole('button', { name: 'Receber link por e-mail' }).click()

    await expect(page.getByRole('main')).toContainText('vale por 1 hora')
  })

  test('explica em vez de quebrar quando não há token', async ({ page }) => {
    await page.goto('/unir-contas')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('expirou')
    await expect(page.getByRole('link', { name: 'Voltar para entrar' })).toBeVisible()
  })
})

test.describe('confirmação da união pelo link', () => {
  test('conclui e guarda a sessão', async ({ page }) => {
    await withValidSession(page)
    await page.route('**/api/v1/uniao-credenciais/link/confirmar', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Pronto — agora você pode entrar com Google ou com sua senha.',
          data: { account: { id: 1, name: 'Maria' }, token: 'tok-link', expires_at: '2026-09-30T00:00:00-03:00' },
        }),
      }),
    )

    await page.goto('/unir-contas/confirmar?token=abc')
    await page.waitForURL((u) => !u.pathname.startsWith('/unir-contas'))

    expect(await page.evaluate(() => localStorage.getItem('bora.session.token'))).toBe('tok-link')
  })

  test('explica quando o link já foi usado', async ({ page }) => {
    await page.route('**/api/v1/uniao-credenciais/link/confirmar', (route) =>
      route.fulfill({
        status: 410,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Este link expirou ou já foi usado. Entre com o Google de novo para recomeçar.' }),
      }),
    )

    await page.goto('/unir-contas/confirmar?token=abc')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('expirou ou já foi usado')
    await expect(page.getByRole('link', { name: 'Voltar para entrar' })).toBeVisible()
  })
})
