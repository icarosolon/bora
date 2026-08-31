'use client'

import { Suspense, useEffect, useRef, useState } from 'react'
import Link from 'next/link'
import { useRouter, useSearchParams } from 'next/navigation'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { chamarApi } from '@/lib/api'
import { consumirDestino, guardarToken } from '@/lib/sessao'

type Sessao = { conta: { id: number; nome: string }; token: string; expira_em: string }

function Conteudo() {
  const router = useRouter()
  const parametros = useSearchParams()
  const [erro, setErro] = useState<string | null>(null)

  // Mesmo guarda da pagina de retorno do Google: o efeito roda duas vezes em
  // desenvolvimento, e o token do link e de uso unico -- a segunda chamada
  // falharia e sobrescreveria um sucesso com mensagem de erro.
  const jaConfirmou = useRef(false)

  useEffect(() => {
    if (jaConfirmou.current) return
    jaConfirmou.current = true

    const token = parametros.get('token')

    if (!token) {
      setErro('Link inválido. Abra o link direto do e-mail que enviamos.')
      return
    }

    chamarApi<Sessao>('/uniao-credenciais/link/confirmar', {
      metodo: 'POST',
      corpo: { token },
    }).then((r) => {
      if (r.tipo === 'ok') {
        guardarToken(r.dados.token)
        router.replace(consumirDestino() ?? '/')
        return
      }

      setErro(r.mensagem)
    })
  }, [parametros, router])

  return (
    <LayoutAuth titulo="Unir contas">
      {erro ? (
        <>
          <Aviso tipo="erro">{erro}</Aviso>
          <p className="mt-6 text-base">
            <Link href="/entrar" className="font-medium underline underline-offset-4">
              Voltar para entrar
            </Link>
          </p>
        </>
      ) : (
        <Aviso tipo="informacao">Confirmando…</Aviso>
      )}
    </LayoutAuth>
  )
}

/** Confirmacao da uniao pelo link recebido por e-mail (plano B da D1). */
export default function ConfirmarUniao() {
  return (
    <Suspense
      fallback={
        <LayoutAuth titulo="Unir contas">
          <Aviso tipo="informacao">Carregando…</Aviso>
        </LayoutAuth>
      }
    >
      <Conteudo />
    </Suspense>
  )
}
