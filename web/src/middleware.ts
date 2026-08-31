import { NextResponse, type NextRequest } from 'next/server'

/**
 * CSP por nonce — mitigação obrigatória do risco aceito na decisão D2.
 *
 * POR QUE NÃO É UM HEADER ESTÁTICO NO next.config.ts (foi assim e estava
 * errado): o Next injeta scripts INLINE para hidratar a página. Uma CSP com
 * `script-src 'self'` e sem `'unsafe-inline'` bloqueia esses scripts, o React
 * nunca hidrata e a aplicação inteira vira HTML morto — formulário não envia,
 * cabeçalho não atualiza. O pior é que a página continua *parecendo* certa, e
 * só um teste de interação percebe. Foi o e2e da US1 que pegou.
 *
 * A saída correta não é afrouxar para `'unsafe-inline'` — isso devolveria o
 * buraco de XSS que a CSP existe para fechar, justamente onde o token da sessão
 * mora em localStorage. A saída é o nonce: um valor novo por requisição, que o
 * Next carimba nos scripts dele. Um script injetado por XSS não tem o nonce.
 *
 * `strict-dynamic` deixa os scripts já confiados carregarem os chunks da
 * aplicação sem precisar listar host por host.
 */
export function middleware(request: NextRequest) {
  const nonce = Buffer.from(crypto.randomUUID()).toString('base64')

  const emDesenvolvimento = process.env.NODE_ENV === 'development'

  const apiOrigem = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1'
  let apiHost = 'http://localhost:8000'
  try {
    apiHost = new URL(apiOrigem).origin
  } catch {
    // mantém o padrão local
  }

  const csp = [
    "default-src 'self'",
    // 'unsafe-eval' só em desenvolvimento: o recarregamento rápido do Next
    // depende dele. Em produção fica de fora.
    `script-src 'self' 'nonce-${nonce}' 'strict-dynamic'${emDesenvolvimento ? " 'unsafe-eval'" : ''}`,
    // Estilo inline não executa código; o Tailwind e o Next injetam estilo.
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
    "font-src 'self' https://fonts.gstatic.com data:",
    "img-src 'self' data: blob:",
    // Em dev o Next usa websocket para o recarregamento rápido.
    `connect-src 'self' ${apiHost}${emDesenvolvimento ? ' ws: wss:' : ''}`,
    "form-action 'self'",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    "object-src 'none'",
  ].join('; ')

  // O Next lê a CSP do header da REQUISIÇÃO para descobrir o nonce e aplicá-lo
  // aos próprios scripts. Sem estas duas linhas, o nonce do header de resposta
  // não bate com o dos scripts e o bloqueio continua.
  const cabecalhosDaRequisicao = new Headers(request.headers)
  cabecalhosDaRequisicao.set('x-nonce', nonce)
  cabecalhosDaRequisicao.set('Content-Security-Policy', csp)

  const resposta = NextResponse.next({
    request: { headers: cabecalhosDaRequisicao },
  })

  resposta.headers.set('Content-Security-Policy', csp)

  return resposta
}

export const config = {
  matcher: [
    // Fora os arquivos estáticos e imagens otimizadas: gerar nonce para eles
    // não protege nada e só custa processamento.
    {
      source: '/((?!_next/static|_next/image|favicon.ico).*)',
      missing: [
        { type: 'header', key: 'next-router-prefetch' },
        { type: 'header', key: 'purpose', value: 'prefetch' },
      ],
    },
  ],
}
