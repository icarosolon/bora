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

/** Formulário de entrar com e-mail e senha (US1-2, US1-6). */
export function SignInForm() {
  const router = useRouter()
  const [state, setState] = useState<SubmitState>(initialState)

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (state.submitting) return

    const form = new FormData(event.currentTarget)
    setState({ ...initialState, submitting: true })

    const result = await callApi<SessionResponse>('/sessoes', {
      method: 'POST',
      body: { email: form.get('email'), password: form.get('password') },
    })

    if (result.kind === 'ok') {
      storeToken(result.data.token)
      router.push(consumeRedirect() ?? '/')
      return
    }

    if (result.kind === 'validation') {
      setState({ ...initialState, errors: result.errors })
      return
    }

    // 401 (credenciais), 429 (limite) e falha de rede caem aqui. A mensagem vem
    // da API justamente para o site e o app dizerem a mesma coisa.
    setState({ ...initialState, generalError: result.message })
  }

  return (
    <BaseForm state={state} actionLabel="Entrar" submittingLabel="Entrando…" onSubmit={submit}>
      <Field
        name="email"
        type="email"
        inputMode="email"
        label="E-mail"
        autoComplete="email"
        error={fieldError(state.errors, 'email')}
      />
      <Field
        name="password"
        type="password"
        label="Senha"
        autoComplete="current-password"
        error={fieldError(state.errors, 'password')}
      />
    </BaseForm>
  )
}
