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
export const EVENTO_SESSAO = 'bora:sessao'

function avisarMudancaDeSessao(): void {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new Event(EVENTO_SESSAO))
}

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

  avisarMudancaDeSessao()
}

export function esquecerToken(): void {
  try {
    armazenamento()?.removeItem(CHAVE_TOKEN)
  } catch {
    /* nada a fazer */
  }

  avisarMudancaDeSessao()
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
const CHAVE_UNIAO = 'bora.uniao.pendente'

export type UniaoPendente = { token: string; email: string }

function sessao(): Storage | null {
  if (typeof window === 'undefined') return null
  try {
    return window.sessionStorage
  } catch {
    return null
  }
}

export function guardarUniaoPendente(pendente: UniaoPendente): void {
  try {
    sessao()?.setItem(CHAVE_UNIAO, JSON.stringify(pendente))
  } catch {
    /* sem storage, a tela de união explica que o pedido expirou */
  }
}

export function lerUniaoPendente(): UniaoPendente | null {
  try {
    const bruto = sessao()?.getItem(CHAVE_UNIAO)
    if (!bruto) return null

    const dados = JSON.parse(bruto) as Partial<UniaoPendente>

    return dados.token ? { token: dados.token, email: dados.email ?? '' } : null
  } catch {
    return null
  }
}

export function esquecerUniaoPendente(): void {
  try {
    sessao()?.removeItem(CHAVE_UNIAO)
  } catch {
    /* nada a fazer */
  }
}
