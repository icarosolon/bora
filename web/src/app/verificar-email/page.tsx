'use client'

import { useEffect, useState } from 'react'
import { useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { Suspense } from 'react'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { callApi } from '@/lib/api'
import { isAuthenticated } from '@/lib/session'

type Status =
  | { state: 'verifying' }
  | { state: 'confirmed'; message: string }
  | { state: 'expired'; message: string }
  | { state: 'failure'; message: string }

function Content() {
  const params = useSearchParams()
  const token = params.get('token')
  const [status, setStatus] = useState<Status>({ state: 'verifying' })
  const [resending, setResending] = useState(false)
  const [resent, setResent] = useState<string | null>(null)

  /*
   * Estado de sessão em `useState` + `useEffect`, e NÃO chamando
   * `isAuthenticated()` direto no JSX.
   *
   * A função lê `localStorage`, que não existe no servidor: ela devolvia
   * `false` na renderização do servidor e podia devolver `true` na hidratação.
   * O React reclamava de HTML divergente e **desistia de corrigir aquela
   * subárvore** — a tela ficava com o estado errado em silêncio.
   *
   * Regra que vale para toda tela deste projeto: nada que dependa do navegador
   * (localStorage, window, data/hora) pode ser lido durante a renderização.
   */
  const [authenticated, setAuthenticated] = useState(false)
  useEffect(() => setAuthenticated(isAuthenticated()), [])

  useEffect(() => {
    if (!token) {
      setStatus({
        state: 'failure',
        message: 'Link incompleto. Abra o link direto do e-mail que enviamos.',
      })
      return
    }

    callApi('/email/verificar', { method: 'POST', body: { token } }).then((r) => {
      if (r.kind === 'ok') {
        setStatus({ state: 'confirmed', message: r.message ?? 'E-mail confirmado.' })
      } else if (r.kind === 'expired') {
        setStatus({ state: 'expired', message: r.message })
      } else {
        setStatus({ state: 'failure', message: r.message })
      }
    })
  }, [token])

  async function resend() {
    setResending(true)
    const r = await callApi('/email/verificar/reenviar', {
      method: 'POST',
      authenticated: true,
    })
    setResending(false)
    setResent(
      r.kind === 'ok'
        ? (r.message ?? 'Enviamos um novo link.')
        : r.message,
    )
  }

  return (
    <AuthLayout title="Confirmar e-mail">
      {status.state === 'verifying' && (
        <Alert kind="info">Confirmando seu e-mail…</Alert>
      )}

      {status.state === 'confirmed' && (
        <>
          <Alert kind="success">{status.message}</Alert>
          <p className="mt-6">
            <Link href="/" className="font-medium underline underline-offset-4">
              Ir para o Bora
            </Link>
          </p>
        </>
      )}

      {(status.state === 'expired' || status.state === 'failure') && (
        <>
          <Alert kind="error">{status.message}</Alert>

          {/* Só oferece reenvio a quem está logado: o endpoint exige sessão. */}
          {authenticated ? (
            <>
              <Button
                type="button"
                onClick={resend}
                disabled={resending}
                className="mt-6 min-h-11 w-full text-base"
              >
                {resending ? 'Enviando…' : 'Enviar um novo link'}
              </Button>
              {resent && (
                <div className="mt-4">
                  <Alert kind="info">{resent}</Alert>
                </div>
              )}
            </>
          ) : (
            <p className="mt-6">
              <Link href="/entrar" className="font-medium underline underline-offset-4">
                Entre na sua conta
              </Link>{' '}
              para pedir um novo link.
            </p>
          )}
        </>
      )}
    </AuthLayout>
  )
}

/**
 * Confirmação de e-mail pelo link (US1-7).
 *
 * `useSearchParams` exige Suspense no App Router. Aqui isso não custa nada:
 * a página é da área logada/transacional, não do catálogo público — não há
 * requisito de SEO a proteger (a armadilha que o spike BORA-32 registrou vale
 * para as páginas de catálogo).
 */
export default function VerifyEmailPage() {
  return (
    <Suspense fallback={<AuthLayout title="Confirmar e-mail"><Alert kind="info">Carregando…</Alert></AuthLayout>}>
      <Content />
    </Suspense>
  )
}
