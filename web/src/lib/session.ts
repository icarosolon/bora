/**
 * Guarda do token da sessão — decisão D2 da spec 001.
 *
 * ATENÇÃO, e isto não é detalhe: este módulo é EXCLUSIVAMENTE de cliente.
 *
 * - Nunca importe daqui em componente de servidor.
 * - Nunca passe o token em prop que cruze a fronteira servidor→cliente: o spike
 *   BORA-32 verificou que essas props são serializadas no HTML da página, o que
 *   entregaria o token no código-fonte para qualquer um.
 *
 * O token fica em `localStorage`, decisão consciente do Ícaro (2026-08-30). A
 * doc do Sanctum desaconselha token de API para SPA de primeira parte, mas o
 * Princípio IV exige que o site use a MESMA autenticação do futuro app mobile,
 * e o modo cookie do Sanctum não serve para app. O risco aceito e escrito: um
 * XSS bem-sucedido rouba a sessão. As mitigações estão em research.md §3 —
 * entre elas a CSP estrita em src/middleware.ts.
 */

const TOKEN_KEY = 'bora.session.token'

/**
 * Evento avisando que a sessão mudou (entrou ou saiu).
 *
 * Existe porque o cabeçalho vive no **layout raiz**: ele monta uma vez e não
 * remonta em navegação client-side. Sem este aviso, entrar por e-mail e senha
 * guardava o token e navegava para a home, mas a barra continuava mostrando
 * "Entrar" até a pessoa recarregar a página — parecia que o login não tinha
 * funcionado, quando tinha.
 *
 * Quem guarda ou esquece o token avisa; quem exibe estado de sessão escuta.
 */
export const SESSION_EVENT = 'bora:session'

function notifySessionChange(): void {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new Event(SESSION_EVENT))
}

/** localStorage não existe no servidor nem em navegador com storage bloqueado. */
function storage(): Storage | null {
  if (typeof window === 'undefined') return null
  try {
    return window.localStorage
  } catch {
    // Navegação privativa restrita ou cookies/site data bloqueados.
    return null
  }
}

export function readToken(): string | null {
  try {
    return storage()?.getItem(TOKEN_KEY) ?? null
  } catch {
    return null
  }
}

export function storeToken(token: string): void {
  try {
    storage()?.setItem(TOKEN_KEY, token)
  } catch {
    // Sem storage a pessoa continua navegando; ela só precisará entrar de novo
    // ao recarregar. Falhar aqui seria pior que degradar.
  }

  notifySessionChange()
}

export function forgetToken(): void {
  try {
    storage()?.removeItem(TOKEN_KEY)
  } catch {
    /* nada a fazer */
  }

  notifySessionChange()
}

export function isAuthenticated(): boolean {
  return readToken() !== null
}

/**
 * Guarda para onde voltar depois do login (edge case de sessão expirada da
 * spec: a pessoa retorna ao ponto de origem, não à home).
 */
const REDIRECT_KEY = 'bora.session.redirect'

export function storeRedirect(path: string): void {
  try {
    // Só caminho interno: evita virar redirecionamento aberto para outro site.
    if (path.startsWith('/') && !path.startsWith('//')) {
      storage()?.setItem(REDIRECT_KEY, path)
    }
  } catch {
    /* nada a fazer */
  }
}

export function consumeRedirect(): string | null {
  try {
    const redirect = storage()?.getItem(REDIRECT_KEY) ?? null
    storage()?.removeItem(REDIRECT_KEY)
    return redirect
  } catch {
    return null
  }
}

/**
 * Pedido de união pendente (US3), guardado entre a página de retorno do Google
 * e a tela de unir contas.
 *
 * Fica em `sessionStorage`, e não na URL, para o token **não** cair no
 * histórico do navegador, no log de servidor nem no cabeçalho `Referer` — a
 * mesma regra que vale para o token de sessão ("token nunca em URL").
 *
 * `sessionStorage` e não `localStorage` de propósito: é um pedido em andamento,
 * de 15 minutos, que morre com a aba. Não faz sentido sobreviver ao navegador
 * ser fechado — e some sozinho se a pessoa desistir no meio.
 */
const MERGE_KEY = 'bora.merge.pending'

export type PendingMerge = { token: string; email: string }

function sessionStore(): Storage | null {
  if (typeof window === 'undefined') return null
  try {
    return window.sessionStorage
  } catch {
    return null
  }
}

export function storePendingMerge(pending: PendingMerge): void {
  try {
    sessionStore()?.setItem(MERGE_KEY, JSON.stringify(pending))
  } catch {
    /* sem storage, a tela de união explica que o pedido expirou */
  }
}

export function readPendingMerge(): PendingMerge | null {
  try {
    const raw = sessionStore()?.getItem(MERGE_KEY)
    if (!raw) return null

    const data = JSON.parse(raw) as Partial<PendingMerge>

    return data.token ? { token: data.token, email: data.email ?? '' } : null
  } catch {
    return null
  }
}

export function forgetPendingMerge(): void {
  try {
    sessionStore()?.removeItem(MERGE_KEY)
  } catch {
    /* nada a fazer */
  }
}
