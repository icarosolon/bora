/**
 * Cliente da API pública do Bora.
 *
 * O `web/` consome EXCLUSIVAMENTE `/api/v1/...` — os mesmos endpoints, com a
 * mesma autenticação, que o futuro app mobile usará (Princípio IV, ADR-0002).
 * Não existe endpoint "só do site", e nenhuma regra de negócio mora aqui: este
 * módulo traduz HTTP em tipos, nada mais.
 *
 * Envelope da constituição: `data` em recursos, `message` em ações, `errors`
 * em validação 422.
 */

import { esquecerToken, lerToken } from '@/lib/sessao'

const BASE = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1'

/** Erros por campo, como a tela precisa para mostrar a mensagem no lugar certo. */
export type ErrosPorCampo = Record<string, string[]>

/**
 * Resultado discriminado: a tela trata cada caso explicitamente, em vez de
 * cair num `catch` genérico que viraria "algo deu errado" — o que o
 * ux-requirements.md proíbe.
 */
export type Resultado<T> =
  | { tipo: 'ok'; dados: T; mensagem?: string }
  | { tipo: 'validacao'; erros: ErrosPorCampo; mensagem: string }
  | { tipo: 'nao_autenticado'; mensagem: string }
  | { tipo: 'conflito'; dados: unknown; mensagem: string }
  | { tipo: 'expirado'; mensagem: string }
  | { tipo: 'limite'; mensagem: string; segundos: number | null }
  | { tipo: 'falha'; mensagem: string }

type Opcoes = {
  metodo?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  corpo?: unknown
  autenticado?: boolean
}

const MENSAGEM_REDE =
  'Não conseguimos falar com o Bora agora. Confira sua conexão e tente de novo.'

export async function chamarApi<T>(
  caminho: string,
  { metodo = 'GET', corpo, autenticado = false }: Opcoes = {},
): Promise<Resultado<T>> {
  const cabecalhos: Record<string, string> = { Accept: 'application/json' }

  if (corpo !== undefined) cabecalhos['Content-Type'] = 'application/json'

  if (autenticado) {
    const token = lerToken()
    if (token) cabecalhos.Authorization = `Bearer ${token}`
  }

  let resposta: Response
  try {
    resposta = await fetch(`${BASE}${caminho}`, {
      method: metodo,
      headers: cabecalhos,
      body: corpo === undefined ? undefined : JSON.stringify(corpo),
    })
  } catch {
    // Rede fora, DNS, CORS bloqueado: nada disso é culpa da pessoa, e a
    // mensagem precisa dizer o que fazer.
    return { tipo: 'falha', mensagem: MENSAGEM_REDE }
  }

  if (resposta.status === 204) {
    return { tipo: 'ok', dados: undefined as T }
  }

  let payload: Record<string, unknown> = {}
  try {
    payload = (await resposta.json()) as Record<string, unknown>
  } catch {
    if (resposta.ok) return { tipo: 'ok', dados: undefined as T }
  }

  const mensagem =
    typeof payload.message === 'string' ? payload.message : MENSAGEM_REDE

  if (resposta.ok) {
    return { tipo: 'ok', dados: payload.data as T, mensagem }
  }

  switch (resposta.status) {
    case 422:
      return {
        tipo: 'validacao',
        erros: (payload.errors as ErrosPorCampo) ?? {},
        mensagem,
      }

    case 401:
      // Sessão expirada ou credencial recusada. Descarta o token morto para a
      // próxima navegação não repetir a chamada inútil.
      if (autenticado) esquecerToken()
      return { tipo: 'nao_autenticado', mensagem }

    case 409:
      // União de credenciais necessária — não é erro, é um passo a mais (US3).
      return { tipo: 'conflito', dados: payload.data, mensagem }

    case 410:
      // Link de e-mail expirado ou já usado.
      return { tipo: 'expirado', mensagem }

    case 429: {
      // Retry-After só é legível porque config/cors.php o expõe.
      const cabecalho = resposta.headers.get('Retry-After')
      const segundos = cabecalho ? Number.parseInt(cabecalho, 10) : NaN
      return {
        tipo: 'limite',
        mensagem,
        segundos: Number.isNaN(segundos) ? null : segundos,
      }
    }

    default:
      return { tipo: 'falha', mensagem }
  }
}

/** Extrai a primeira mensagem de um campo, que é o que a tela mostra ao lado dele. */
export function erroDoCampo(erros: ErrosPorCampo, campo: string): string | undefined {
  return erros[campo]?.[0]
}
