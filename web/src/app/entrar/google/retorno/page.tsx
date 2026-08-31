'use client'

import { Suspense, useEffect, useRef, useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { chamarApi } from '@/lib/api'
import { consumirDestino, guardarToken } from '@/lib/sessao'

type Sessao = { conta: { id: number; nome: string }; token: string; expira_em: string }

type Situacao =
  | { estado: 'entrando' }
  | { estado: 'erro'; mensagem: string }

function Conteudo() {
  const router = useRouter()
  const parametros = useSearchParams()
  const [situacao, setSituacao] = useState<Situacao>({ estado: 'entrando' })

  // O React roda efeitos duas vezes em desenvolvimento (StrictMode). Sem este
  // guarda, o `code` seria trocado duas vezes — e o Google só o aceita uma,
  // fazendo a segunda tentativa falhar e sobrescrever um login bem-sucedido
  // com uma mensagem de erro.
  const jaTrocou = useRef(false)

  useEffect(() => {
    if (jaTrocou.current) return
    jaTrocou.current = true

    const code = parametros.get('code')
    const state = parametros.get('state')
    const erroDoGoogle = parametros.get('error')

    // A pessoa cancelou na tela do Google: ele volta com `error`, sem `code`.
    if (erroDoGoogle || !code || !state) {
      setSituacao({
        estado: 'erro',
        mensagem: 'Não deu para entrar com o Google agora. Tente de novo ou use seu e-mail e senha.',
      })
      return
    }

    chamarApi<Sessao>('/auth/google/sessoes', {
      metodo: 'POST',
      corpo: { code, state },
    }).then((r) => {
      if (r.tipo === 'ok') {
        guardarToken(r.dados.token)
        router.replace(consumirDestino() ?? '/')
        return
      }

      if (r.tipo === 'conflito') {
        // 409: já existe conta com este e-mail. Nada foi gravado; falta a
        // confirmação do titular, que é a US3.
        const dados = r.dados as { uniao_token?: string; email?: string }
        const busca = new URLSearchParams()
        if (dados.uniao_token) busca.set('token', dados.uniao_token)
        if (dados.email) busca.set('email', dados.email)

        router.replace(`/unir-contas?${busca.toString()}`)
        return
      }

      setSituacao({ estado: 'erro', mensagem: r.mensagem })
    })
  }, [parametros, router])

  return (
    <LayoutAuth titulo="Entrando com o Google">
      {situacao.estado === 'entrando' ? (
        <Aviso tipo="informacao">Entrando…</Aviso>
      ) : (
        <>
          <Aviso tipo="erro">{situacao.mensagem}</Aviso>

          <p className="mt-6 text-base">
            <Link href="/entrar" className="font-medium underline underline-offset-4">
              Voltar para entrar
            </Link>
          </p>
        </>
      )}
    </LayoutAuth>
  )
}

/**
 * Retorno do Google (US2).
 *
 * **Esta é a URL registrada no console do Google** como URI de redirecionamento
 * autorizado. Ela aponta para o `web/` e não para a API de propósito: assim o
 * `code` chega ao navegador, que o troca por sessão num POST — e **o token
 * nunca passa pela barra de endereço**, nem pelo histórico, nem pelo log de
 * servidor.
 *
 * `useSearchParams` exige Suspense no App Router. Aqui não custa nada: é tela
 * transacional, não catálogo público — não há SEO a proteger (a armadilha que o
 * spike BORA-32 registrou vale para as páginas de catálogo).
 */
export default function RetornoDoGoogle() {
  return (
    <Suspense
      fallback={
        <LayoutAuth titulo="Entrando com o Google">
          <Aviso tipo="informacao">Carregando…</Aviso>
        </LayoutAuth>
      }
    >
      <Conteudo />
    </Suspense>
  )
}
