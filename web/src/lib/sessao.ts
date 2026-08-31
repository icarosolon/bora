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
 * entre elas a CSP estrita em next.config.ts.
 */

const CHAVE_TOKEN = 'bora.sessao.token'

/** localStorage não existe no servidor nem em navegador com storage bloqueado. */
function armazenamento(): Storage | null {
  if (typeof window === 'undefined') return null
  try {
    return window.localStorage
  } catch {
    // Navegação privativa restrita ou cookies/site data bloqueados.
    return null
  }
}

export function lerToken(): string | null {
  try {
    return armazenamento()?.getItem(CHAVE_TOKEN) ?? null
  } catch {
    return null
  }
}

export function guardarToken(token: string): void {
  try {
    armazenamento()?.setItem(CHAVE_TOKEN, token)
  } catch {
    // Sem storage a pessoa continua navegando; ela só precisará entrar de novo
    // ao recarregar. Falhar aqui seria pior que degradar.
  }
}

export function esquecerToken(): void {
  try {
    armazenamento()?.removeItem(CHAVE_TOKEN)
  } catch {
    /* nada a fazer */
  }
}

export function estaAutenticado(): boolean {
  return lerToken() !== null
}

/**
 * Guarda para onde voltar depois do login (edge case de sessão expirada da
 * spec: a pessoa retorna ao ponto de origem, não à home).
 */
const CHAVE_DESTINO = 'bora.sessao.destino'

export function guardarDestino(caminho: string): void {
  try {
    // Só caminho interno: evita virar redirecionamento aberto para outro site.
    if (caminho.startsWith('/') && !caminho.startsWith('//')) {
      armazenamento()?.setItem(CHAVE_DESTINO, caminho)
    }
  } catch {
    /* nada a fazer */
  }
}

export function consumirDestino(): string | null {
  try {
    const destino = armazenamento()?.getItem(CHAVE_DESTINO) ?? null
    armazenamento()?.removeItem(CHAVE_DESTINO)
    return destino
  } catch {
    return null
  }
}
