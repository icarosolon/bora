'use client'

import { useState } from 'react'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import type { FieldErrors } from '@/lib/api'
import { useHydrated } from '@/lib/hydration'

/**
 * Casca comum dos formulários de conta.
 *
 * Concentra o que o ux-requirements.md exige de TODO formulário desta feature,
 * para nenhuma tela esquecer um pedaço:
 *
 * - **Carregando**: o botão mostra o estado e fica desabilitado — é também o
 *   que impede a dupla submissão (toque duplo em rede lenta não pode criar
 *   duas contas).
 * - **Erro geral** em faixa no topo, em linguagem humana.
 * - **Sucesso** confirmado visivelmente, nunca silêncio.
 * - **Ação principal ao alcance do polegar**: o botão vem logo abaixo dos
 *   campos, na metade inferior — nunca só numa barra no topo.
 */
export type SubmitState = {
  submitting: boolean
  generalError: string | null
  success: string | null
  errors: FieldErrors
}

export const initialState: SubmitState = {
  submitting: false,
  generalError: null,
  success: null,
  errors: {},
}

export function useSubmitState() {
  return useState<SubmitState>(initialState)
}

type BaseFormProps = {
  state: SubmitState
  actionLabel: string
  submittingLabel?: string
  onSubmit: (event: React.FormEvent<HTMLFormElement>) => void
  children: React.ReactNode
  accessory?: React.ReactNode
}

export function BaseForm({
  state,
  actionLabel,
  submittingLabel = 'Enviando…',
  onSubmit,
  children,
  accessory,
}: BaseFormProps) {
  // Sem isto, um toque antes da hidratação faz o navegador submeter
  // nativamente e a senha vai para a URL (E-012). Ver `useHydrated`.
  const ready = useHydrated()

  return (
    <form
      onSubmit={onSubmit}
      // Defesa em profundidade: se por qualquer motivo escapar uma submissão
      // nativa, ela vai como POST e não expõe os campos na barra de endereço.
      method="post"
      noValidate
      className="flex flex-col gap-5"
    >
      {state.generalError && <Alert kind="error">{state.generalError}</Alert>}
      {state.success && <Alert kind="success">{state.success}</Alert>}

      <fieldset disabled={state.submitting} className="flex flex-col gap-5 border-0 p-0">
        {children}
      </fieldset>

      <Button
        type="submit"
        disabled={state.submitting || !ready}
        aria-busy={state.submitting || !ready}
        className="min-h-11 w-full text-base"
      >
        {/*
          Enquanto não hidratou, o rótulo diz "Carregando…" em vez de mostrar a
          ação como se estivesse disponível. Se a hidratação falhar de vez, a
          tela ADMITE que não está pronta, em vez de exibir um botão morto sem
          explicação — foi assim que o E-013 passou despercebido.
        */}
        {state.submitting ? submittingLabel : ready ? actionLabel : 'Carregando…'}
      </Button>

      {accessory}
    </form>
  )
}
