'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { BaseForm, initialState, type SubmitState } from '@/components/auth/BaseForm'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { Field } from '@/components/ui/field'
import { callApi, fieldError } from '@/lib/api'
import { isAuthenticated } from '@/lib/session'

/**
 * Definir a primeira senha (US2-5, decisão D1 — direção inversa).
 *
 * Para quem entrou pelo Google e quer também poder entrar com e-mail e senha.
 * A confirmação do titular é a **sessão ativa**: por isso a tela só faz sentido
 * autenticada, e quem chega sem sessão é mandado para o login.
 */
export default function SetPasswordPage() {
  const router = useRouter()
  const [state, setState] = useState<SubmitState>(initialState)
  const [checkingSession, setCheckingSession] = useState(true)

  useEffect(() => {
    if (!isAuthenticated()) {
      router.replace('/entrar')
      return
    }
    setCheckingSession(false)
  }, [router])

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (state.submitting) return

    const form = new FormData(event.currentTarget)
    setState({ ...initialState, submitting: true })

    const result = await callApi('/senha', {
      method: 'POST',
      body: { password: form.get('password') },
      authenticated: true,
    })

    if (result.kind === 'ok') {
      setState({
        ...initialState,
        success: result.message ?? 'Senha definida.',
      })
      return
    }

    if (result.kind === 'validation') {
      setState({ ...initialState, errors: result.errors })
      return
    }

    if (result.kind === 'unauthenticated') {
      router.replace('/entrar')
      return
    }

    setState({ ...initialState, generalError: result.message })
  }

  if (checkingSession) {
    return (
      <AuthLayout title="Definir senha">
        <Alert kind="info">Carregando…</Alert>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout
      title="Definir senha"
      subtitle="Assim você poderá entrar com o Google ou com sua senha, como preferir."
    >
      <BaseForm
        state={state}
        actionLabel="Salvar senha"
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
