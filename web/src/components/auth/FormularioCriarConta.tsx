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

/**
 * Formulário de criar conta (US1-1).
 *
 * Nenhuma regra de negócio aqui (Princípio IV): a validação de verdade é da
 * API. O front só mostra o que ela respondeu, no campo certo. Duplicar a regra
 * aqui faria o app mobile nascer com comportamento diferente do site.
 */
export function FormularioCriarConta() {
  const router = useRouter()
  const [estado, setEstado] = useState<EstadoEnvio>(estadoInicial)

  async function enviar(evento: React.FormEvent<HTMLFormElement>) {
    evento.preventDefault()

    // Guarda contra dupla submissão no cliente; a garantia real é do backend.
    if (estado.enviando) return

    const dados = new FormData(evento.currentTarget)
    setEstado({ ...estadoInicial, enviando: true })

    const resultado = await chamarApi<RespostaSessao>('/contas', {
      metodo: 'POST',
      corpo: {
        nome: dados.get('nome'),
        email: dados.get('email'),
        senha: dados.get('senha'),
      },
    })

    if (resultado.tipo === 'ok') {
      guardarToken(resultado.dados.token)
      setEstado({ ...estadoInicial, sucesso: resultado.mensagem ?? 'Conta criada!' })
      router.push(consumirDestino() ?? '/')
      return
    }

    if (resultado.tipo === 'validacao') {
      setEstado({ ...estadoInicial, erros: resultado.erros })
      return
    }

    setEstado({ ...estadoInicial, erroGeral: resultado.mensagem })
  }

  return (
    <FormularioBase estado={estado} rotuloAcao="Criar conta" rotuloEnviando="Criando…" onSubmit={enviar}>
      <Campo
        name="nome"
        rotulo="Como você quer ser chamado"
        autoComplete="name"
        erro={erroDoCampo(estado.erros, 'nome')}
      />
      <Campo
        name="email"
        type="email"
        inputMode="email"
        rotulo="E-mail"
        autoComplete="email"
        dica="Use um e-mail que você acessa — enviaremos confirmações para ele."
        erro={erroDoCampo(estado.erros, 'email')}
      />
      <Campo
        name="senha"
        type="password"
        rotulo="Senha"
        autoComplete="new-password"
        dica="Pelo menos 8 caracteres."
        erro={erroDoCampo(estado.erros, 'senha')}
      />
    </FormularioBase>
  )
}
