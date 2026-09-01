'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { BaseForm, initialState, type SubmitState } from '@/components/auth/BaseForm'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Field } from '@/components/ui/field'
import { callApi, fieldError } from '@/lib/api'
import { useHydrated } from '@/lib/hydration'
import { consumeRedirect, forgetPendingMerge, storeToken } from '@/lib/session'

type SessionResponse = {
  account: { id: number; name: string }
  token: string
  expires_at: string
}

/**
 * Confirmação da união de credenciais (US3, decisão D1).
 *
 * A tela precisa **explicar o que vai acontecer** antes de pedir a senha: a
 * pessoa entrou pelo Google e foi parar num pedido de senha, o que assusta se
 * não for justificado. É o `ux-requirements.md` na prática — "cada tela se
 * explica sozinha ou falhou".
 *
 * O plano B (link por e-mail) fica visível desde o começo, não escondido atrás
 * de um erro: quem já sabe que não lembra a senha não deveria precisar errar
 * primeiro para descobrir que existe saída.
 */
export function MergeAccountsForm({ token, email }: { token: string; email: string }) {
  const router = useRouter()
  const [state, setState] = useState<SubmitState>(initialState)
  const [sendingLink, setSendingLink] = useState(false)
  const [linkSent, setLinkSent] = useState<string | null>(null)

  // O botão do plano B também dispara ação: antes da hidratação o toque seria
  // ignorado em silêncio. Ver `useHydrated`.
  const ready = useHydrated()

  async function confirmWithPassword(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (state.submitting) return

    const form = new FormData(event.currentTarget)
    setState({ ...initialState, submitting: true })

    const result = await callApi<SessionResponse>('/uniao-credenciais', {
      method: 'POST',
      body: { merge_token: token, password: form.get('password') },
    })

    if (result.kind === 'ok') {
      storeToken(result.data.token)
      // O pedido cumpriu seu papel; deixa-lo guardado faria uma visita futura
      // a /unir-contas tentar reusar um token ja consumido.
      forgetPendingMerge()
      router.replace(consumeRedirect() ?? '/')
      return
    }

    if (result.kind === 'validation') {
      setState({ ...initialState, errors: result.errors })
      return
    }

    // 401 (senha errada), 410 (pedido expirado) e 429 (limite) trazem mensagem
    // da API — a mesma que o app mobile mostraria.
    setState({ ...initialState, generalError: result.message })
  }

  async function requestLink() {
    if (sendingLink) return

    setSendingLink(true)
    const result = await callApi('/uniao-credenciais/link', {
      method: 'POST',
      body: { merge_token: token },
    })
    setSendingLink(false)

    setLinkSent(
      result.kind === 'ok'
        ? (result.message ?? 'Enviamos um link para o seu e-mail.')
        : result.message,
    )
  }

  return (
    <div className="flex flex-col gap-6">
      <Alert kind="info">
        Você já tem uma conta no Bora com <strong>{email}</strong>. Confirme que é você e as
        duas formas de entrar passam a valer para a mesma conta — nada é duplicado.
      </Alert>

      <BaseForm
        state={state}
        actionLabel="Unir e entrar"
        submittingLabel="Unindo…"
        onSubmit={confirmWithPassword}
      >
        <Field
          name="password"
          type="password"
          label="Sua senha do Bora"
          autoComplete="current-password"
          error={fieldError(state.errors, 'password')}
        />
      </BaseForm>

      <div className="flex flex-col gap-3">
        <p className="text-base text-muted-foreground">Não lembra a senha?</p>

        <Button
          type="button"
          variant="outline"
          onClick={requestLink}
          disabled={sendingLink || !ready}
          aria-busy={sendingLink || !ready}
          className="min-h-11 w-full text-base"
        >
          {sendingLink ? 'Enviando…' : ready ? 'Receber link por e-mail' : 'Carregando…'}
        </Button>

        {linkSent && <Alert kind="info">{linkSent}</Alert>}
      </div>
    </div>
  )
}
