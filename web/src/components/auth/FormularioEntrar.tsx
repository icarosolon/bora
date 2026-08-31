'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { FormularioBase, estadoInicial, type EstadoEnvio } from '@/components/auth/FormularioBase'
import { Campo } from '@/components/ui/campo'
import { chamarApi, erroDoCampo } from '@/lib/api'
import { consumirDestino, guardarToken } from '@/lib/sessao'

type RespostaSessao = {
  conta: { id: number; nome: string; email: string; email_verificado: boolean }
  token: string
  expira_em: string
}

/** Formulário de entrar com e-mail e senha (US1-2, US1-6). */
export function FormularioEntrar() {
  const router = useRouter()
  const [estado, setEstado] = useState<EstadoEnvio>(estadoInicial)

  async function enviar(evento: React.FormEvent<HTMLFormElement>) {
    evento.preventDefault()
    if (estado.enviando) return

    const dados = new FormData(evento.currentTarget)
    setEstado({ ...estadoInicial, enviando: true })

    const resultado = await chamarApi<RespostaSessao>('/sessoes', {
      metodo: 'POST',
      corpo: { email: dados.get('email'), senha: dados.get('senha') },
    })

    if (resultado.tipo === 'ok') {
      guardarToken(resultado.dados.token)
      router.push(consumirDestino() ?? '/')
      return
    }

    if (resultado.tipo === 'validacao') {
      setEstado({ ...estadoInicial, erros: resultado.erros })
      return
    }

    // 401 (credenciais), 429 (limite) e falha de rede caem aqui. A mensagem vem
    // da API justamente para o site e o app dizerem a mesma coisa.
    setEstado({ ...estadoInicial, erroGeral: resultado.mensagem })
  }

  return (
    <FormularioBase estado={estado} rotuloAcao="Entrar" rotuloEnviando="Entrando…" onSubmit={enviar}>
      <Campo
        name="email"
        type="email"
        inputMode="email"
        rotulo="E-mail"
        autoComplete="email"
        erro={erroDoCampo(estado.erros, 'email')}
      />
      <Campo
        name="senha"
        type="password"
        rotulo="Senha"
        autoComplete="current-password"
        erro={erroDoCampo(estado.erros, 'senha')}
      />
    </FormularioBase>
  )
}
