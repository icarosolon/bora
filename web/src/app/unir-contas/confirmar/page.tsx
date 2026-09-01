'use client'

import { Suspense, useEffect, useRef, useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { callApi } from '@/lib/api'
import { consumeRedirect, storeToken } from '@/lib/session'

type SessionResponse = {
  account: { id: number; name: string }
  token: string
  expires_at: string
}

function Content() {
  const router = useRouter()
  const params = useSearchParams()
  const [error, setError] = useState<string | null>(null)

  // Mesmo guarda da pagina de retorno do Google: o efeito roda duas vezes em
  // desenvolvimento, e o token do link e de uso unico -- a segunda chamada
  // falharia e sobrescreveria um sucesso com mensagem de erro.
  const alreadyConfirmed = useRef(false)

  useEffect(() => {
    if (alreadyConfirmed.current) return
    alreadyConfirmed.current = true

    const token = params.get('token')

    if (!token) {
      setError('Link inválido. Abra o link direto do e-mail que enviamos.')
      return
    }

    callApi<SessionResponse>('/uniao-credenciais/link/confirmar', {
      method: 'POST',
      body: { token },
    }).then((r) => {
      if (r.kind === 'ok') {
        storeToken(r.data.token)
        router.replace(consumeRedirect() ?? '/')
        return
      }

      setError(r.message)
    })
  }, [params, router])

  return (
    <AuthLayout title="Unir contas">
      {error ? (
        <>
          <Alert kind="error">{error}</Alert>
          <p className="mt-6 text-base">
            <Link href="/entrar" className="font-medium underline underline-offset-4">
              Voltar para entrar
            </Link>
          </p>
        </>
      ) : (
        <Alert kind="info">Confirmando…</Alert>
      )}
    </AuthLayout>
  )
}

/** Confirmacao da uniao pelo link recebido por e-mail (plano B da D1). */
export default function ConfirmMergePage() {
  return (
    <Suspense
      fallback={
        <AuthLayout title="Unir contas">
          <Alert kind="info">Carregando…</Alert>
        </AuthLayout>
      }
    >
      <Content />
    </Suspense>
  )
}
