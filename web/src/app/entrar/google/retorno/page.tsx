'use client'

import { Suspense, useEffect, useRef, useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { callApi } from '@/lib/api'
import { consumeRedirect, storeToken, storePendingMerge } from '@/lib/session'

type SessionResponse = {
  account: { id: number; name: string }
  token: string
  expires_at: string
}

type Status =
  | { state: 'signing_in' }
  | { state: 'error'; message: string }

function Content() {
  const router = useRouter()
  const params = useSearchParams()
  const [status, setStatus] = useState<Status>({ state: 'signing_in' })

  // O React roda efeitos duas vezes em desenvolvimento (StrictMode). Sem este
  // guarda, o `code` seria trocado duas vezes — e o Google só o aceita uma,
  // fazendo a segunda tentativa falhar e sobrescrever um login bem-sucedido
  // com uma mensagem de erro.
  const alreadyExchanged = useRef(false)

  useEffect(() => {
    if (alreadyExchanged.current) return
    alreadyExchanged.current = true

    const code = params.get('code')
    const state = params.get('state')
    const googleError = params.get('error')

    // A pessoa cancelou na tela do Google: ele volta com `error`, sem `code`.
    if (googleError || !code || !state) {
      setStatus({
        state: 'error',
        message: 'Não deu para entrar com o Google agora. Tente de novo ou use seu e-mail e senha.',
      })
      return
    }

    callApi<SessionResponse>('/auth/google/sessoes', {
      method: 'POST',
      body: { code, state },
    }).then((r) => {
      if (r.kind === 'ok') {
        storeToken(r.data.token)
        router.replace(consumeRedirect() ?? '/')
        return
      }

      if (r.kind === 'conflict') {
        // 409: já existe conta com este e-mail. Nada foi gravado; falta a
        // confirmação do titular, que é a US3.
        //
        // O token vai em `sessionStorage`, NÃO na query string: na URL ele
        // cairia no histórico do navegador, no log de servidor e no `Referer`
        // — a mesma regra que vale para o token de sessão.
        const data = r.data as { merge_token?: string; email?: string }

        if (data.merge_token) {
          storePendingMerge({ token: data.merge_token, email: data.email ?? '' })
        }

        router.replace('/unir-contas')
        return
      }

      setStatus({ state: 'error', message: r.message })
    })
  }, [params, router])

  return (
    <AuthLayout title="Entrando com o Google">
      {status.state === 'signing_in' ? (
        <Alert kind="info">Entrando…</Alert>
      ) : (
        <>
          <Alert kind="error">{status.message}</Alert>

          <p className="mt-6 text-base">
            <Link href="/entrar" className="font-medium underline underline-offset-4">
              Voltar para entrar
            </Link>
          </p>
        </>
      )}
    </AuthLayout>
  )
}

/**
 * Retorno do Google (US2).
 *
 * **Esta é a URL registrada no console do Google** como URI de redirecionamento
 * autorizado. Ela aponta para o `web/` e não para a API de propósito: assim o
 * `code` chega ao navegador, que o troca por sessão num POST — e **o token
 * nunca passa pela barra de endereço**, nem pelo histórico, nem pelo log de
 * servidor.
 *
 * `useSearchParams` exige Suspense no App Router. Aqui não custa nada: é tela
 * transacional, não catálogo público — não há SEO a proteger (a armadilha que o
 * spike BORA-32 registrou vale para as páginas de catálogo).
 */
export default function GoogleCallbackPage() {
  return (
    <Suspense
      fallback={
        <AuthLayout title="Entrando com o Google">
          <Alert kind="info">Carregando…</Alert>
        </AuthLayout>
      }
    >
      <Content />
    </Suspense>
  )
}
