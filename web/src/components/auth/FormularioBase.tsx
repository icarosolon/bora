'use client'

import { useEffect, useState } from 'react'
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
  /*
   * Só libera o envio depois de o componente montar no navegador.
   *
   * Isto NÃO é detalhe de teste. Antes da hidratação o `onSubmit` do React não
   * existe, e um toque no botão faz o navegador submeter o formulário do jeito
   * nativo: navega para a mesma página com os campos na QUERY STRING — ou seja,
   * **a senha vai para a URL**, para o histórico do navegador e para o log de
   * servidor. Exatamente o que a spec proíbe.
   *
   * A janela é curta num computador rápido, mas o público do Bora usa aparelho
   * modesto em rede lenta (ux-requirements.md), onde ela é bem real: a pessoa
   * tocaria, perderia o que digitou e vazaria a senha sem nada na tela indicar.
   *
   * Foi um teste e2e que revelou — clicando mais rápido que a hidratação.
   */
  const [pronto, setPronto] = useState(false)
  useEffect(() => setPronto(true), [])

  return (
    <form
      onSubmit={onSubmit}
      // Defesa em profundidade: se por qualquer motivo escapar uma submissão
      // nativa, ela vai como POST e não expõe os campos na barra de endereço.
      method="post"
      noValidate
      className="flex flex-col gap-5"
    >
      {estado.erroGeral && <Aviso tipo="erro">{estado.erroGeral}</Aviso>}
      {estado.sucesso && <Aviso tipo="sucesso">{estado.sucesso}</Aviso>}

      <fieldset disabled={estado.enviando} className="flex flex-col gap-5 border-0 p-0">
        {children}
      </fieldset>

      <Button
        type="submit"
        disabled={estado.enviando || !pronto}
        aria-busy={estado.enviando || !pronto}
        className="min-h-11 w-full text-base"
      >
        {/*
          Enquanto não hidratou, o rótulo diz "Carregando…" em vez de mostrar a
          ação como se estivesse disponível. Se a hidratação falhar de vez, a
          tela ADMITE que não está pronta, em vez de exibir um botão morto sem
          explicação — foi assim que o E-013 passou despercebido.
        */}
        {estado.enviando ? rotuloEnviando : pronto ? rotuloAcao : 'Carregando…'}
      </Button>

      {acessorio}
    </form>
  )
}
