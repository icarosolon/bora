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

  /*
   * O `connect-src` precisa liberar exatamente o host que o navegador vai
   * chamar. Como o cliente deriva a API de onde a página foi aberta
   * (`src/lib/api.ts`), aqui se faz o mesmo a partir do host da requisição:
   * abrindo em `localhost:3000`, libera `localhost:8000`; abrindo pelo IP da
   * rede no celular, libera aquele IP. Fixar um host aqui recriaria o E-011,
   * agora na forma de bloqueio de CSP em vez de conexão recusada.
   */
  let apiHost: string
  if (process.env.NEXT_PUBLIC_API_URL) {
    try {
      apiHost = new URL(process.env.NEXT_PUBLIC_API_URL).origin
    } catch {
      apiHost = 'http://localhost:8000'
    }
  } else {
    // Do header `Host`, e NÃO de `request.nextUrl`: com o servidor subido em
    // `-H 0.0.0.0`, o nextUrl devolve o endereço de bind (`0.0.0.0`), que não
    // é o host que o navegador pediu — e a CSP sairia liberando `0.0.0.0:8000`,
    // bloqueando tanto o computador quanto o celular.
    const host = (request.headers.get('host') ?? 'localhost:3000').split(':')[0]
    const protocolo = request.headers.get('x-forwarded-proto') ?? 'http'

    apiHost = `${protocolo}://${host}:8000`
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
