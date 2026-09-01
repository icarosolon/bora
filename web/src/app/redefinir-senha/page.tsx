'use client'

import { Suspense, useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { BaseForm, initialState, type SubmitState } from '@/components/auth/BaseForm'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { Field } from '@/components/ui/field'
import { callApi, fieldError } from '@/lib/api'
import { forgetToken } from '@/lib/session'

function Content() {
  const router = useRouter()
  const params = useSearchParams()
  const token = params.get('token')
  const [state, setState] = useState<SubmitState>(initialState)
  const [expired, setExpired] = useState<string | null>(null)

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (state.submitting) return

    const form = new FormData(event.currentTarget)
    setState({ ...initialState, submitting: true })

    const result = await callApi('/senha/redefinir', {
      method: 'POST',
      body: { token, password: form.get('password') },
    })

    if (result.kind === 'ok') {
      // A API revoga todas as sessões (FR-015); o token guardado aqui já não
      // vale nada, e deixá-lo faria a próxima tela tentar usá-lo à toa.
      forgetToken()
      router.replace('/entrar?senha-alterada=1')
      return
    }

    if (result.kind === 'validation') {
      setState({ ...initialState, errors: result.errors })
      return
    }

    if (result.kind === 'expired') {
      setExpired(result.message)
      return
    }

    setState({ ...initialState, generalError: result.message })
  }

  // Link velho, já usado, ou visita direta sem token: explica e devolve o
  // caminho, em vez de mostrar formulário que vai falhar de qualquer jeito.
  if (!token || expired) {
    return (
      <AuthLayout title="Criar nova senha">
        <Alert kind="error">
          {expired ?? 'Link inválido. Abra o link direto do e-mail que enviamos.'}
        </Alert>
        <p className="mt-6 text-base">
          <Link href="/esqueci-senha" className="font-medium underline underline-offset-4">
            Pedir um novo link
          </Link>
        </p>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout
      title="Criar nova senha"
      subtitle="Depois de salvar, você entra com ela. As sessões abertas em outros aparelhos serão encerradas."
    >
      <BaseForm
        state={state}
        actionLabel="Salvar nova senha"
        submittingLabel="Salvando…"
        onSubmit={submit}
      >
        <Field
          name="password"
          type="password"
          label="Nova senha"
          autoComplete="new-password"
          hint="Pelo menos 8 caracteres."
          error={fieldError(state.errors, 'password')}
        />
      </BaseForm>
    </AuthLayout>
  )
}

/**
 * Criar nova senha pelo link recebido (US4).
 *
 * Não faz login automático de propósito: quem abriu o link provou que lê o
 * e-mail, não que é a pessoa naquele aparelho — pode ser um computador
 * emprestado. Redefinida a senha, a pessoa entra normalmente.
 */
export default function ResetPasswordPage() {
  return (
    <Suspense
      fallback={
        <AuthLayout title="Criar nova senha">
          <Alert kind="info">Carregando…</Alert>
        </AuthLayout>
      }
    >
      <Content />
    </Suspense>
  )
}
