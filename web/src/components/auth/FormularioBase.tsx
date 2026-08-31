'use client'

import { useState } from 'react'
import { Aviso } from '@/components/ui/aviso'
import { Button } from '@/components/ui/button'
import type { ErrosPorCampo } from '@/lib/api'

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
export type EstadoEnvio = {
  enviando: boolean
  erroGeral: string | null
  sucesso: string | null
  erros: ErrosPorCampo
}

export const estadoInicial: EstadoEnvio = {
  enviando: false,
  erroGeral: null,
  sucesso: null,
  erros: {},
}

export function useEstadoEnvio() {
  return useState<EstadoEnvio>(estadoInicial)
}

type FormularioBaseProps = {
  estado: EstadoEnvio
  rotuloAcao: string
  rotuloEnviando?: string
  onSubmit: (evento: React.FormEvent<HTMLFormElement>) => void
  children: React.ReactNode
  acessorio?: React.ReactNode
}

export function FormularioBase({
  estado,
  rotuloAcao,
  rotuloEnviando = 'Enviando…',
  onSubmit,
  children,
  acessorio,
}: FormularioBaseProps) {
  return (
    <form onSubmit={onSubmit} noValidate className="flex flex-col gap-5">
      {estado.erroGeral && <Aviso tipo="erro">{estado.erroGeral}</Aviso>}
      {estado.sucesso && <Aviso tipo="sucesso">{estado.sucesso}</Aviso>}

      <fieldset disabled={estado.enviando} className="flex flex-col gap-5 border-0 p-0">
        {children}
      </fieldset>

      <Button
        type="submit"
        disabled={estado.enviando}
        className="min-h-11 w-full text-base"
      >
        {estado.enviando ? rotuloEnviando : rotuloAcao}
      </Button>

      {acessorio}
    </form>
  )
}
