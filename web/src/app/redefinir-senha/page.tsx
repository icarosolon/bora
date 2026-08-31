'use client'

import { Suspense, useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { FormularioBase, estadoInicial, type EstadoEnvio } from '@/components/auth/FormularioBase'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { Campo } from '@/components/ui/campo'
import { chamarApi, erroDoCampo } from '@/lib/api'
import { esquecerToken } from '@/lib/sessao'

function Conteudo() {
  const router = useRouter()
  const parametros = useSearchParams()
  const token = parametros.get('token')
  const [estado, setEstado] = useState<EstadoEnvio>(estadoInicial)
  const [expirado, setExpirado] = useState<string | null>(null)

  async function enviar(evento: React.FormEvent<HTMLFormElement>) {
    evento.preventDefault()
    if (estado.enviando) return

    const dados = new FormData(evento.currentTarget)
    setEstado({ ...estadoInicial, enviando: true })

    const resultado = await chamarApi('/senha/redefinir', {
      metodo: 'POST',
      corpo: { token, senha: dados.get('senha') },
    })

    if (resultado.tipo === 'ok') {
      // A API revoga todas as sessões (FR-015); o token guardado aqui já não
      // vale nada, e deixá-lo faria a próxima tela tentar usá-lo à toa.
      esquecerToken()
      router.replace('/entrar?senha-alterada=1')
      return
    }

    if (resultado.tipo === 'validacao') {
      setEstado({ ...estadoInicial, erros: resultado.erros })
      return
    }

    if (resultado.tipo === 'expirado') {
      setExpirado(resultado.mensagem)
      return
    }

    setEstado({ ...estadoInicial, erroGeral: resultado.mensagem })
  }

  // Link velho, já usado, ou visita direta sem token: explica e devolve o
  // caminho, em vez de mostrar formulário que vai falhar de qualquer jeito.
  if (!token || expirado) {
    return (
      <LayoutAuth titulo="Criar nova senha">
        <Aviso tipo="erro">
          {expirado ?? 'Link inválido. Abra o link direto do e-mail que enviamos.'}
        </Aviso>
        <p className="mt-6 text-base">
          <Link href="/esqueci-senha" className="font-medium underline underline-offset-4">
            Pedir um novo link
          </Link>
        </p>
      </LayoutAuth>
    )
  }

  return (
    <LayoutAuth
      titulo="Criar nova senha"
      subtitulo="Depois de salvar, você entra com ela. As sessões abertas em outros aparelhos serão encerradas."
    >
      <FormularioBase
        estado={estado}
        rotuloAcao="Salvar nova senha"
        rotuloEnviando="Salvando…"
        onSubmit={enviar}
      >
        <Campo
          name="senha"
          type="password"
          rotulo="Nova senha"
          autoComplete="new-password"
          dica="Pelo menos 8 caracteres."
          erro={erroDoCampo(estado.erros, 'senha')}
        />
      </FormularioBase>
    </LayoutAuth>
  )
}

/**
 * Criar nova senha pelo link recebido (US4).
 *
 * Não faz login automático de propósito: quem abriu o link provou que lê o
 * e-mail, não que é a pessoa naquele aparelho — pode ser um computador
 * emprestado. Redefinida a senha, a pessoa entra normalmente.
 */
export default function RedefinirSenha() {
  return (
    <Suspense
      fallback={
        <LayoutAuth titulo="Criar nova senha">
          <Aviso tipo="informacao">Carregando…</Aviso>
        </LayoutAuth>
      }
    >
      <Conteudo />
    </Suspense>
  )
}
