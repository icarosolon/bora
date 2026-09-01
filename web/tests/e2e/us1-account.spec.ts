import type { Page } from '@playwright/test'
import { expect, test } from './base'

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
  await page.route('**/api/v1/eu', (route) =>
    route.fulfill({ status: 401, contentType: 'application/json', body: JSON.stringify({ message: 'Faça login para continuar.' }) }),
  )
}

/**
 * A asserção que o `ux-requirements.md` exige explicitamente: a 360px não pode
 * haver rolagem horizontal nem elemento estourando a largura.
 */
async function noHorizontalScroll(page: Page) {
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
    await noHorizontalScroll(page)
  })

  test('a ação principal tem alvo de toque confortável', async ({ page }) => {
    await page.goto('/entrar')

    const box = await page.getByRole('button', { name: 'Entrar', exact: true }).boundingBox()

    // ux-requirements.md: alvos de toque >= 44px.
    expect(box?.height ?? 0).toBeGreaterThanOrEqual(44)
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
    await noHorizontalScroll(page)
  })

  test('mostra o erro no campo quando a API recusa', async ({ page }) => {
    await page.route('**/api/v1/contas', (route) =>
      route.fulfill({
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
    // elemento com role="alert" próprio (bar de ferramentas), e um seletor
    // solto casaria com os dois.
    const alert = page.getByRole('main').getByRole('alert')

    // Princípio I visto pela tela: recusa com orientação, não conta paralela.
    await expect(alert).toContainText('já tem conta')
    // E o erro aparece NO CAMPO, não só numa faixa geral (ux-requirements.md).
    await expect(page.getByLabel('E-mail')).toHaveAttribute('aria-invalid', 'true')
    await noHorizontalScroll(page)
  })

  test('navega por teclado até a ação principal', async ({ page }) => {
    await page.goto('/criar-conta')

    // Espera a página ficar interativa: até hidratar, o botão de envio fica
    // desabilitado de propósito (ver BaseForm e E-012), e `Tab` pula
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
  test('passa a mostrar o nome logo após entrar, sem recarregar', async ({ page }) => {
    /*
     * REGRESSÃO real (2026-08-31): o login funcionava e o token era guardado,
     * mas a bar continuava mostrando "Entrar" até a pessoa recarregar a
     * página — parecia que o login tinha falhado.
     *
     * Causa: o cabeçalho vive no LAYOUT RAIZ, monta uma vez e não remonta em
     * navegação client-side. Passou por 74 testes e2e porque nenhum fazia
     * login de verdade e depois olhava a bar.
     */
    await page.route('**/api/v1/sessoes', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            account: { id: 1, name: 'Maria Souza', email: 'maria@exemplo.com', email_verified: true },
            token: 'tok-sessao',
            expires_at: '2026-09-30T00:00:00-03:00',
          },
        }),
      }),
    )

    await page.route('**/api/v1/eu', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: { id: 1, name: 'Maria Souza', email: 'maria@exemplo.com', email_verified: true },
        }),
      }),
    )

    await page.goto('/entrar')
    await page.getByLabel('E-mail').fill('maria@exemplo.com')
    await page.getByLabel('Senha').fill('senhaforte1')
    await page.getByRole('button', { name: 'Entrar', exact: true }).click()

    // SEM recarregar: a bar tem de refletir a sessão sozinha.
    const bar = page.getByRole('banner')
    await expect(bar).toContainText('Maria Souza')
    await expect(bar.getByRole('button', { name: 'Sair' })).toBeVisible()
  })

  test('oferece entrar para quem não está autenticado', async ({ page }) => {
    await page.goto('/entrar')

    // "Sair" e "Entrar" com rótulo de text, nunca ícone solto.
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
    await page.route('**/api/v1/contas', (route) =>
      route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'x', errors: { name: ['Digite seu nome.'] } }),
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

test.describe('confirmar e-mail', () => {
  /*
   * Esta tela ficou SEM teste e2e até 2026-08-31, e foi justamente nela que
   * apareceu uma divergência de hidratação (`isAuthenticated()` chamado durante
   * a renderização). O `base.ts` reprova erro de console; sem um teste que
   * visite a página, porém, a rede não tem onde pegar.
   */
  test('confirma o e-mail e oferece seguir', async ({ page }) => {
    await page.route('**/api/v1/email/verificar', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'E-mail confirmado. Obrigado!' }),
      }),
    )

    await page.goto('/verificar-email?token=abc')

    await expect(page.getByRole('main')).toContainText('E-mail confirmado')
    await expect(page.getByRole('link', { name: 'Ir para o Bora' })).toBeVisible()
    await noHorizontalScroll(page)
  })

  test('explica e oferece caminho quando o link expirou', async ({ page }) => {
    await page.route('**/api/v1/email/verificar', (route) =>
      route.fulfill({
        status: 410,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Este link expirou ou já foi usado. Entre na sua conta e peça um novo e-mail de confirmação.',
        }),
      }),
    )

    await page.goto('/verificar-email?token=velho')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('expirou')
    // Sem sessão, oferece entrar — o reenvio exige autenticação.
    await expect(page.getByRole('link', { name: 'Entre na sua conta' })).toBeVisible()
    await noHorizontalScroll(page)
  })

  test('explica em vez de quebrar quando não há token', async ({ page }) => {
    await page.goto('/verificar-email')

    await expect(page.getByRole('main').getByRole('alert')).toContainText('Link incompleto')
  })

  test('não diverge entre servidor e cliente quando há sessão guardada', async ({ page }) => {
    /*
     * REGRESSÃO de uma divergência de hidratação real (2026-08-31).
     *
     * `isAuthenticated()` era chamado durante a renderização; como ele lê
     * `localStorage`, o servidor devolvia `false` e o cliente `true`. O React
     * acusava HTML divergente e desistia de corrigir a subárvore.
     *
     * A condição SÓ ocorre com token guardado — por isso o token é semeado
     * antes de a página carregar. Sem isso, o teste passa mesmo com o bug
     * presente (foi o que aconteceu na primeira tentativa de provar a rede).
     * O `base.ts` reprova o erro de console que o React emite.
     */
    await page.addInitScript(() => {
      localStorage.setItem('bora.session.token', 'tok-de-teste')
    })

    /*
     * `/eu` precisa responder 200 AQUI, sobrepondo o 401 do `beforeEach`.
     *
     * O cenário tem de ser coerente: com 401, o cabeçalho descarta o token
     * (comportamento correto — token inválido não fica guardado), e aí a página
     * às vezes lia a sessão antes e às vezes depois do descarte. O teste ficava
     * intermitente por culpa da própria premissa, não do produto.
     */
    await page.route('**/api/v1/eu', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: { id: 1, name: 'Maria', email: 'maria@exemplo.com', email_verified: false },
        }),
      }),
    )

    await page.route('**/api/v1/email/verificar', (route) =>
      route.fulfill({
        status: 410,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Este link expirou ou já foi usado.' }),
      }),
    )

    await page.goto('/verificar-email?token=velho')

    // Autenticado: a tela oferece reenviar, em vez de mandar entrar.
    await expect(page.getByRole('button', { name: 'Enviar um novo link' })).toBeVisible()
  })
})
