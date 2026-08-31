import type { NextConfig } from 'next'

/**
 * Cabeçalhos de segurança que NÃO dependem de requisição.
 *
 * A Content-Security-Policy não está aqui de propósito: ela precisa de um nonce
 * novo a cada requisição, então vive em `src/middleware.ts`. Header estático de
 * CSP quebrou a hidratação do Next uma vez — não repetir.
 */
const nextConfig: NextConfig = {
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
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
