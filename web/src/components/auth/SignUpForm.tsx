'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { BaseForm, initialState, type SubmitState } from '@/components/auth/BaseForm'
import { Field } from '@/components/ui/field'
import { callApi, fieldError } from '@/lib/api'
import { consumeRedirect, storeToken } from '@/lib/session'

type SessionResponse = {
  account: { id: number; name: string; email: string; email_verified: boolean }
  token: string
  expires_at: string
}

/**
 * Formulário de criar conta (US1-1).
 *
 * Nenhuma regra de negócio aqui (Princípio IV): a validação de verdade é da
 * API. O front só mostra o que ela respondeu, no campo certo. Duplicar a regra
 * aqui faria o app mobile nascer com comportamento diferente do site.
 */
export function SignUpForm() {
  const router = useRouter()
  const [state, setState] = useState<SubmitState>(initialState)

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()

    // Guarda contra dupla submissão no cliente; a garantia real é do backend.
    if (state.submitting) return

    const form = new FormData(event.currentTarget)
    setState({ ...initialState, submitting: true })

    const result = await callApi<SessionResponse>('/contas', {
      method: 'POST',
      body: {
        name: form.get('name'),
        email: form.get('email'),
        password: form.get('password'),
      },
    })

    if (result.kind === 'ok') {
      storeToken(result.data.token)
      setState({ ...initialState, success: result.message ?? 'Conta criada!' })
      router.push(consumeRedirect() ?? '/')
      return
    }

    if (result.kind === 'validation') {
      setState({ ...initialState, errors: result.errors })
      return
    }

    setState({ ...initialState, generalError: result.message })
  }

  return (
    <BaseForm state={state} actionLabel="Criar conta" submittingLabel="Criando…" onSubmit={submit}>
      <Field
        name="name"
        label="Como você quer ser chamado"
        autoComplete="name"
        error={fieldError(state.errors, 'name')}
      />
      <Field
        name="email"
        type="email"
        inputMode="email"
        label="E-mail"
        autoComplete="email"
        hint="Use um e-mail que você acessa — enviaremos confirmações para ele."
        error={fieldError(state.errors, 'email')}
      />
      <Field
        name="password"
        type="password"
        label="Senha"
        autoComplete="new-password"
        hint="Pelo menos 8 caracteres."
        error={fieldError(state.errors, 'password')}
      />
    </BaseForm>
  )
}
