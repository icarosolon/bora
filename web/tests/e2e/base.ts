import { test as base, expect } from '@playwright/test'

/**
 * `test` estendido que **reprova quando o navegador registra erro no console**.
 *
 * Existe por causa de um defeito real: `verificar-email` chamava
 * `estaAutenticado()` durante a renderização. Como a função lê `localStorage`,
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
const IGNORADOS = [
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
function ehRespostaDeErroSimuladaDaApi(url: string | undefined): boolean {
  return !!url && url.includes('/api/v1/')
}

export const test = base.extend<{ semErrosDeConsole: void }>({
  semErrosDeConsole: [
    async ({ page }, use) => {
      const erros: string[] = []

      page.on('console', (m) => {
        if (m.type() !== 'error') return

        const texto = m.text()
        if (IGNORADOS.some((r) => r.test(texto))) return
        if (ehRespostaDeErroSimuladaDaApi(m.location()?.url)) return

        erros.push(texto)
      })

      page.on('pageerror', (e) => erros.push(`Erro não tratado: ${e.message}`))

      await use()

      expect(erros, 'o navegador registrou erro no console').toEqual([])
    },
    { auto: true },
  ],
})

export { expect }
