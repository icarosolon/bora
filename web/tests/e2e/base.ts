import { test as base, expect, type Page } from '@playwright/test'

/**
 * `test` estendido que **reprova quando o navegador registra erro no console**.
 *
 * Existe por causa de um defeito real: `verificar-email` chamava
 * `isAuthenticated()` durante a renderização. Como a função lê `localStorage`,
 * o servidor devolvia `false` e o cliente `true` — o React acusava HTML
 * divergente e **desistia de corrigir aquela subárvore**, deixando a tela num
 * estado errado sem nada visível indicar. Passou por 66 testes e2e, 37 de
 * componente e o build.
 *
 * Nenhum dos que tínhamos podia pegar: o de componente roda em jsdom, que não
 * faz renderização de servidor nem hidratação; o e2e não olhava o console.
 *
 * Esta rede pega, de uma vez, toda a família: divergência de hidratação,
 * violação de CSP, erro de JavaScript não tratado e recurso bloqueado.
 */

/** Ruído do ambiente de desenvolvimento, não defeito da aplicação. */
const IGNORED = [
  // O recarregamento rápido do Next não conecta quando a página é servida por
  // um host diferente do bind — irrelevante para o comportamento da tela.
  /websocket connection to .*_next\/hmr/i,
  /download the react devtools/i,
]

/**
 * Resposta 4xx da API é DELIBERADA em boa parte dos testes: eles simulam senha
 * errada, link expirado, união necessária. O navegador registra isso como erro
 * de recurso, mas é o cenário sendo exercitado, não defeito.
 *
 * O filtro é por URL, e não pela mensagem, de propósito: falha ao carregar
 * qualquer outra coisa — um chunk de JavaScript, por exemplo — **continua
 * reprovando**. Foi exatamente um 403 em `/_next/static/chunks` que quebrou a
 * hidratação no E-013, e essa rede precisa continuar pegando aquilo.
 */
function isSimulatedApiError(url: string | undefined): boolean {
  return !!url && url.includes('/api/v1/')
}

export const test = base.extend<{ noConsoleErrors: void }>({
  noConsoleErrors: [
    async ({ page }, use) => {
      const errors: string[] = []

      page.on('console', (m) => {
        if (m.type() !== 'error') return

        const text = m.text()
        if (IGNORED.some((r) => r.test(text))) return
        if (isSimulatedApiError(m.location()?.url)) return

        errors.push(text)
      })

      page.on('pageerror', (e) => errors.push(`Erro não tratado: ${e.message}`))

      await use()

      expect(errors, 'o navegador registrou erro no console').toEqual([])
    },
    { auto: true },
  ],
})

export { expect }

/**
 * Faz `/api/v1/eu` responder como autenticado.
 *
 * **Todo teste que ESTABELECE sessão precisa disto.** As specs devolvem 401 por
 * padrão no `beforeEach` (a maioria dos casos é de quem não entrou), e o
 * cabeçalho consulta `/eu` assim que um token é guardado. Com 401, o cliente
 * descarta o token — comportamento correto do produto — e o teste fica
 * contraditório: guarda sessão e ao mesmo tempo declara que ela é inválida.
 *
 * Essa contradição já produziu duas falhas intermitentes; por isso o auxiliar
 * mora aqui, e não copiado em cada spec.
 */
export async function withValidSession(page: Page) {
  await page.route('**/api/v1/eu', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: { id: 1, name: 'Maria', email: 'maria@exemplo.com', email_verified: true },
      }),
    }),
  )
}
