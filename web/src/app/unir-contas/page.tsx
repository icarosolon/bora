'use client'

import { Suspense } from 'react'
import Link from 'next/link'
import { useSearchParams } from 'next/navigation'
import { FormularioUnirContas } from '@/components/auth/FormularioUnirContas'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'

function Conteudo() {
  const parametros = useSearchParams()
  const token = parametros.get('token')
  const email = parametros.get('email') ?? 'seu e-mail'

  // Chegar aqui sem token significa link velho, recarregamento tardio ou visita
  // direta. Em vez de tela quebrada, explica e devolve o caminho.
  if (!token) {
    return (
      <LayoutAuth titulo="Unir contas">
        <Aviso tipo="erro">
          Este pedido expirou. Toque em &quot;Entrar com Google&quot; de novo para recomeçar.
        </Aviso>
        <p className="mt-6 text-base">
          <Link href="/entrar" className="font-medium underline underline-offset-4">
            Voltar para entrar
          </Link>
        </p>
      </LayoutAuth>
    )
  }

  return (
    <LayoutAuth titulo="Unir contas" subtitulo="Falta só confirmar que é você.">
      <FormularioUnirContas token={token} email={email} />
    </LayoutAuth>
  )
}

/**
 * Uniao de credenciais (US3). Chega-se aqui pelo 409 do login com Google.
 *
 * Ate esta tela concluir, NADA foi gravado no servidor -- e o que sustenta a
 * invariante do Principio I.
 */
export default function UnirContas() {
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
