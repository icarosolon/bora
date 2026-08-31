'use client'

import { useState } from 'react'
import Link from 'next/link'
import { FormularioBase, estadoInicial, type EstadoEnvio } from '@/components/auth/FormularioBase'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { Campo } from '@/components/ui/campo'
import { chamarApi, erroDoCampo } from '@/lib/api'

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
export default function EsqueciSenha() {
  const [estado, setEstado] = useState<EstadoEnvio>(estadoInicial)
  const [enviado, setEnviado] = useState<string | null>(null)

  async function enviar(evento: React.FormEvent<HTMLFormElement>) {
    evento.preventDefault()
    if (estado.enviando) return

    const dados = new FormData(evento.currentTarget)
    setEstado({ ...estadoInicial, enviando: true })

    const resultado = await chamarApi('/senha/esqueci', {
      metodo: 'POST',
      corpo: { email: dados.get('email') },
    })

    if (resultado.tipo === 'ok') {
      setEstado(estadoInicial)
      setEnviado(resultado.mensagem ?? 'Se este e-mail estiver cadastrado, você receberá um link.')
      return
    }

    if (resultado.tipo === 'validacao') {
      setEstado({ ...estadoInicial, erros: resultado.erros })
      return
    }

    setEstado({ ...estadoInicial, erroGeral: resultado.mensagem })
  }

  if (enviado) {
    return (
      <LayoutAuth
        titulo="Confira seu e-mail"
        rodape={
          <p>
            <Link href="/entrar" className="font-medium underline underline-offset-4">
              Voltar para entrar
            </Link>
          </p>
        }
      >
        <Aviso tipo="sucesso">{enviado}</Aviso>
        <p className="mt-4 text-base text-muted-foreground">
          Procure também na caixa de spam. O link vale por 1 hora e só pode ser usado uma vez.
        </p>
      </LayoutAuth>
    )
  }

  return (
    <LayoutAuth
      titulo="Esqueci minha senha"
      subtitulo="Informe seu e-mail e enviaremos um link para criar uma nova senha."
      rodape={
        <p>
          Lembrou?{' '}
          <Link href="/entrar" className="font-medium underline underline-offset-4">
            Voltar para entrar
          </Link>
        </p>
      }
    >
      <FormularioBase
        estado={estado}
        rotuloAcao="Enviar link"
        rotuloEnviando="Enviando…"
        onSubmit={enviar}
      >
        <Campo
          name="email"
          type="email"
          inputMode="email"
          rotulo="E-mail"
          autoComplete="email"
          erro={erroDoCampo(estado.erros, 'email')}
        />
      </FormularioBase>
    </LayoutAuth>
  )
}
