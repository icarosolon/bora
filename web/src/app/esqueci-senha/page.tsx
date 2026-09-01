'use client'

import { useState } from 'react'
import Link from 'next/link'
import { BaseForm, initialState, type SubmitState } from '@/components/auth/BaseForm'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { Field } from '@/components/ui/field'
import { callApi, fieldError } from '@/lib/api'

/**
 * "Esqueci minha senha" (US4, decisao D6).
 *
 * A tela NAO diz se o e-mail existe -- a API responde sempre a mesma coisa, e a
 * tela apenas repete. Qualquer diferenca aqui (texto, icone, tempo) desfaria a
 * nao-enumeracao que o backend protege.
 *
 * O estado de sucesso ENSINA o proximo passo em vez de so confirmar: onde
 * procurar, quanto tempo vale. Sem isso a pessoa fica olhando a caixa de
 * entrada sem saber se deu certo (ux-requirements.md).
 */
export default function ForgotPasswordPage() {
  const [state, setState] = useState<SubmitState>(initialState)
  const [sent, setSent] = useState<string | null>(null)

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (state.submitting) return

    const form = new FormData(event.currentTarget)
    setState({ ...initialState, submitting: true })

    const result = await callApi('/senha/esqueci', {
      method: 'POST',
      body: { email: form.get('email') },
    })

    if (result.kind === 'ok') {
      setState(initialState)
      setSent(result.message ?? 'Se este e-mail estiver cadastrado, você receberá um link.')
      return
    }

    if (result.kind === 'validation') {
      setState({ ...initialState, errors: result.errors })
      return
    }

    setState({ ...initialState, generalError: result.message })
  }

  if (sent) {
    return (
      <AuthLayout
        title="Confira seu e-mail"
        footer={
          <p>
            <Link href="/entrar" className="font-medium underline underline-offset-4">
              Voltar para entrar
            </Link>
          </p>
        }
      >
        <Alert kind="success">{sent}</Alert>
        <p className="mt-4 text-base text-muted-foreground">
          Procure também na caixa de spam. O link vale por 1 hora e só pode ser usado uma vez.
        </p>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout
      title="Esqueci minha senha"
      subtitle="Informe seu e-mail e enviaremos um link para criar uma nova senha."
      footer={
        <p>
          Lembrou?{' '}
          <Link href="/entrar" className="font-medium underline underline-offset-4">
            Voltar para entrar
          </Link>
        </p>
      }
    >
      <BaseForm
        state={state}
        actionLabel="Enviar link"
        submittingLabel="Enviando…"
        onSubmit={submit}
      >
        <Field
          name="email"
          type="email"
          inputMode="email"
          label="E-mail"
          autoComplete="email"
          error={fieldError(state.errors, 'email')}
        />
      </BaseForm>
    </AuthLayout>
  )
}
