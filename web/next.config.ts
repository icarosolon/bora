import os from 'node:os'
import type { NextConfig } from 'next'

/**
 * Endereços IPv4 desta máquina na rede local.
 *
 * Descobertos em tempo de execução em vez de fixados: o IP muda com o DHCP, e
 * um valor fixo aqui vira exatamente a armadilha que já custou uma sessão.
 */
function ipsDaMaquina(): string[] {
  return Object.values(os.networkInterfaces())
    .flat()
    .filter((i) => !!i && i.family === 'IPv4' && !i.internal)
    .map((i) => i!.address)
}

/**
 * Cabeçalhos de segurança que NÃO dependem de requisição.
 *
 * A Content-Security-Policy não está aqui de propósito: precisa de um nonce
 * novo a cada requisição, então vive em `src/middleware.ts`. Header estático de
 * CSP quebrou a hidratação do Next uma vez (E-009) — não repetir.
 */
const nextConfig: NextConfig = {
  /*
   * O servidor de DESENVOLVIMENTO do Next recusa requisições de origem
   * diferente de localhost: os chunks voltam 403, o React não hidrata e a
   * página fica viva só na aparência (E-013). Como a validação visual do Bora é
   * feita no CELULAR (Princípio XI), a máquina precisa se autorizar na própria
   * rede. Vale só em desenvolvimento — não afeta produção.
   */
  allowedDevOrigins: ipsDaMaquina(),

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
