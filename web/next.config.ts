import type { NextConfig } from 'next'

/**
 * Cabeçalhos de segurança.
 *
 * A CSP aqui NÃO é enfeite: é mitigação obrigatória do risco aceito na decisão
 * D2 da spec 001. O token da sessão fica em `localStorage`, legível por
 * JavaScript, então um XSS bem-sucedido roubaria a sessão. A CSP é a barreira
 * que torna esse XSS muito mais difícil. Ver research.md §3.
 *
 * `unsafe-inline` em script-src está proibido de propósito — é justamente o que
 * anularia a proteção. Em desenvolvimento o Next precisa de `unsafe-eval` para
 * o refresh rápido; em produção, não.
 */
const emDesenvolvimento = process.env.NODE_ENV === 'development'

const apiOrigem = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1'
const apiHost = (() => {
  try {
    return new URL(apiOrigem).origin
  } catch {
    return 'http://localhost:8000'
  }
})()

const csp = [
  "default-src 'self'",
  `script-src 'self'${emDesenvolvimento ? " 'unsafe-eval'" : ''}`,
  // Tailwind injeta estilo; inline em style não dá execução de código.
  "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
  "font-src 'self' https://fonts.gstatic.com data:",
  "img-src 'self' data: blob:",
  `connect-src 'self' ${apiHost}`,
  "form-action 'self'",
  "frame-ancestors 'none'",
  "base-uri 'self'",
  "object-src 'none'",
].join('; ')

const nextConfig: NextConfig = {
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          { key: 'Content-Security-Policy', value: csp },
          { key: 'X-Content-Type-Options', value: 'nosniff' },
          { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
          { key: 'X-Frame-Options', value: 'DENY' },
          // Nenhuma tela desta feature usa câmera, microfone ou localização.
          {
            key: 'Permissions-Policy',
            value: 'camera=(), microphone=(), geolocation=()',
          },
        ],
      },
    ]
  },
}

export default nextConfig
