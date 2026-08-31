import type { Page } from '@playwright/test'
import { comSessaoValida, expect, test } from './base'

/**
 * US2 — entrar com Google, nas duas larguras obrigatórias.
 *
 * O provedor é **simulado**: nenhum teste fala com o Google de verdade. Isso é
 * deliberado — depender de conta externa e de rede tornaria a suíte lenta,
 * frágil e impossível de rodar em CI, sem cobrir nada que os 130 testes de
 * backend já não cubram. O que se verifica aqui é a TELA.
 */

async function semRolagemHorizontal(page: Page) {
  const { scrollWidth, clientWidth } = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }))

  expect(scrollWidth, 'a página tem rolagem horizontal').toBeLessThanOrEqual(clientWidth)
}

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/eu', (rota) =>
    rota.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Faça login para continuar.' }),
    }),
  )
})

test.describe('botão do Google na tela de entrar', () => {
  test('aparece acima do formulário de e-mail e senha', async ({ page }) => {
    await page.goto('/entrar')

    const botaoGoogle = page.getByRole('button', { name: 'Entrar com Google' })
    const campoEmail = page.getByLabel('E-mail')

    await expect(botaoGoogle).toBeVisible()

    // O caminho de menor fricção vem primeiro (US2, decisão de tela).
    const yBotao = (await botaoGoogle.boundingBox())?.y ?? 0
    const yCampo = (await campoEmail.boundingBox())?.y ?? 0
    expect(yBotao).toBeLessThan(yCampo)

    await semRolagemHorizontal(page)
  })

  test('tem alvo de toque confortável', async ({ page }) => {
    await page.goto('/entrar')

    const caixa = await page.getByRole('button', { name: 'Entrar com Google' }).boundingBox()

    expect(caixa?.height ?? 0).toBeGreaterThanOrEqual(44)
  })

  test('leva para a URL de autorização devolvida pela API', async ({ page }) => {
    await page.route('**/api/v1/auth/google/url', (rota) =>
      rota.fulfill({
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
    await comSessaoValida(page)
    await page.route('**/api/v1/auth/google/sessoes', (rota) =>
      rota.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            conta: { id: 1, nome: 'Maria' },
            token: 'tok-google',
            expira_em: '2026-09-30T00:00:00-03:00',
          },
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')

    await expect(page).toHaveURL(/\/$|\/\?/)
    expect(await page.evaluate(() => localStorage.getItem('bora.sessao.token'))).toBe('tok-google')
  })

  test('o token nunca aparece na URL', async ({ page }) => {
    await comSessaoValida(page)
    await page.route('**/api/v1/auth/google/sessoes', (rota) =>
      rota.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: { conta: { id: 1, nome: 'Maria' }, token: 'tok-secreto', expira_em: '2026-09-30T00:00:00-03:00' },
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')
    await page.waitForURL(/\/$|\/\?/)

    // É a razão de o retorno apontar para o web/ e não para a API.
    expect(page.url()).not.toContain('tok-secreto')
  })

  test('mostra mensagem humana e caminho de volta quando o Google recusa', async ({ page }) => {
    await page.route('**/api/v1/auth/google/sessoes', (rota) =>
      rota.fulfill({
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
    await semRolagemHorizontal(page)
  })

  test('trata o cancelamento na tela do Google', async ({ page }) => {
    // O Google volta com `error` e sem `code` quando a pessoa cancela.
    await page.goto('/entrar/google/retorno?error=access_denied')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('Google')
    expect(await page.evaluate(() => localStorage.getItem('bora.sessao.token'))).toBeNull()
  })

  test('manda para a união quando o e-mail já tem conta', async ({ page }) => {
    await page.route('**/api/v1/auth/google/sessoes', (rota) =>
      rota.fulfill({
        status: 409,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Você já tem conta no Bora com este e-mail.',
          data: {
            situacao: 'uniao_necessaria',
            email: 'maria@exemplo.com',
            uniao_token: 'tok-uniao',
            expira_em: '2026-08-31T13:00:00-03:00',
          },
        }),
      }),
    )

    await page.goto('/entrar/google/retorno?code=abc&state=xyz')

    // A tela de destino é da US3; aqui garante-se o encaminhamento e que
    // NENHUMA sessão foi aberta (nada foi gravado no servidor tampouco).
    await expect(page).toHaveURL(/\/unir-contas$/)
    expect(await page.evaluate(() => localStorage.getItem('bora.sessao.token'))).toBeNull()

    // O token de união vai por `sessionStorage`, NUNCA pela URL: na query
    // string ele cairia no histórico, no log de servidor e no `Referer`.
    expect(page.url(), 'token de união não pode ir na URL').not.toContain('tok-uniao')
    expect(
      await page.evaluate(() => sessionStorage.getItem('bora.uniao.pendente')),
      'o pedido precisa ficar guardado para a tela de união',
    ).toContain('tok-uniao')
  })
})
