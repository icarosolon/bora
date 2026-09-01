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
 *
 * Repare que os CAMPOS do JSON são em inglês, embora o CAMINHO da rota seja em
 * português (`/sessoes`, `/email/verificar`). É a convenção do projeto: o
 * caminho é endereço, visível e compartilhável; o corpo é código.
 * Ver docs/architecture/naming-conventions.md.
 */

import { forgetToken, readToken, storeRedirect } from '@/lib/session'

/**
 * Endereço da API.
 *
 * Sem `NEXT_PUBLIC_API_URL`, deriva o host de ONDE A PÁGINA FOI ABERTA, em vez
 * de fixar `localhost`. Isso é o que faz a validação no celular funcionar sem
 * configuração: abrindo em `http://192.168.0.105:3000`, a API vira
 * `http://192.168.0.105:8000` sozinha; no computador, continua `localhost`.
 *
 * Fixar `localhost` aqui foi a causa raiz do E-011: no celular, `localhost` é o
 * próprio celular, então toda chamada morria — e a tela, que é renderizada no
 * servidor, continuava carregando normalmente, escondendo o problema.
 */
function apiBaseUrl(): string {
  if (process.env.NEXT_PUBLIC_API_URL) return process.env.NEXT_PUBLIC_API_URL

  if (typeof window !== 'undefined') {
    return `${window.location.protocol}//${window.location.hostname}:8000/api/v1`
  }

  return 'http://localhost:8000/api/v1'
}

/** Erros por campo, como a tela precisa para mostrar a mensagem no lugar certo. */
export type FieldErrors = Record<string, string[]>

/**
 * Resultado discriminado: a tela trata cada caso explicitamente, em vez de
 * cair num `catch` genérico que viraria "algo deu errado" — o que o
 * ux-requirements.md proíbe.
 */
export type Result<T> =
  | { kind: 'ok'; data: T; message?: string }
  | { kind: 'validation'; errors: FieldErrors; message: string }
  | { kind: 'unauthenticated'; message: string }
  | { kind: 'conflict'; data: unknown; message: string }
  | { kind: 'expired'; message: string }
  | { kind: 'rate_limited'; message: string; seconds: number | null }
  | { kind: 'failure'; message: string }

type Options = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
  authenticated?: boolean
}

const NETWORK_MESSAGE =
  'Não conseguimos falar com o Bora agora. Confira sua conexão e tente de novo.'

export async function callApi<T>(
  path: string,
  { method = 'GET', body, authenticated = false }: Options = {},
): Promise<Result<T>> {
  const headers: Record<string, string> = { Accept: 'application/json' }

  if (body !== undefined) headers['Content-Type'] = 'application/json'

  if (authenticated) {
    const token = readToken()
    if (token) headers.Authorization = `Bearer ${token}`
  }

  let response: Response
  try {
    response = await fetch(`${apiBaseUrl()}${path}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch {
    // Rede fora, DNS, CORS bloqueado: nada disso é culpa da pessoa, e a
    // mensagem precisa dizer o que fazer.
    return { kind: 'failure', message: NETWORK_MESSAGE }
  }

  if (response.status === 204) {
    return { kind: 'ok', data: undefined as T }
  }

  let payload: Record<string, unknown> = {}
  try {
    payload = (await response.json()) as Record<string, unknown>
  } catch {
    if (response.ok) return { kind: 'ok', data: undefined as T }
  }

  const message =
    typeof payload.message === 'string' ? payload.message : NETWORK_MESSAGE

  if (response.ok) {
    return { kind: 'ok', data: payload.data as T, message }
  }

  switch (response.status) {
    case 422:
      return {
        kind: 'validation',
        errors: (payload.errors as FieldErrors) ?? {},
        message,
      }

    case 401:
      // Sessão expirada ou credencial recusada. Descarta o token morto para a
      // próxima navegação não repetir a chamada inútil.
      if (authenticated) {
        forgetToken()

        // Guarda de onde a pessoa saiu, para ela voltar ao mesmo lugar depois
        // de entrar de novo — em vez de cair na home e ter de se achar
        // (edge case de sessão expirada da spec 001).
        if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/entrar')) {
          storeRedirect(window.location.pathname + window.location.search)
        }
      }
      return { kind: 'unauthenticated', message }

    case 409:
      // União de credenciais necessária — não é erro, é um passo a mais (US3).
      return { kind: 'conflict', data: payload.data, message }

    case 410:
      // Link de e-mail expirado ou já usado.
      return { kind: 'expired', message }

    case 429: {
      // Retry-After só é legível porque config/cors.php o expõe.
      const header = response.headers.get('Retry-After')
      const seconds = header ? Number.parseInt(header, 10) : NaN
      return {
        kind: 'rate_limited',
        message,
        seconds: Number.isNaN(seconds) ? null : seconds,
      }
    }

    default:
      return { kind: 'failure', message }
  }
}

/** Extrai a primeira mensagem de um campo, que é o que a tela mostra ao lado dele. */
export function fieldError(errors: FieldErrors, field: string): string | undefined {
  return errors[field]?.[0]
}
