'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { MergeAccountsForm } from '@/components/auth/MergeAccountsForm'
import { AuthLayout } from '@/components/auth/AuthLayout'
import { Alert } from '@/components/ui/alert'
import { readPendingMerge, type PendingMerge } from '@/lib/session'

/**
 * Uniao de credenciais (US3). Chega-se aqui pelo 409 do login com Google.
 *
 * O pedido vem por `sessionStorage`, NAO pela URL: token em query string cai no
 * historico do navegador, no log de servidor e no cabecalho `Referer`. E a
 * mesma regra do token de sessao -- "token nunca em URL" -- aplicada sem
 * excecao (fechado no Polish da spec 001).
 *
 * Ate esta tela concluir, NADA foi gravado no servidor: e o que sustenta a
 * invariante do Principio I.
 */
export default function MergeAccountsPage() {
  const [pending, setPending] = useState<PendingMerge | null>(null)
  const [looking, setLooking] = useState(true)

  // Leitura em efeito, nunca durante a renderizacao: sessionStorage nao existe
  // no servidor, e ler no JSX causaria divergencia de hidratacao (E-015).
  useEffect(() => {
    setPending(readPendingMerge())
    setLooking(false)
  }, [])

  if (looking) {
    return (
      <AuthLayout title="Unir contas">
        <Alert kind="info">Carregando…</Alert>
      </AuthLayout>
    )
  }

  // Sem pedido guardado: aba nova, pedido velho ou visita direta. Explica e
  // devolve o caminho, em vez de mostrar formulario que falharia.
  if (!pending) {
    return (
      <AuthLayout title="Unir contas">
        <Alert kind="error">
          Este pedido expirou. Toque em &quot;Entrar com Google&quot; de novo para recomeçar.
        </Alert>
        <p className="mt-6 text-base">
          <Link href="/entrar" className="font-medium underline underline-offset-4">
            Voltar para entrar
          </Link>
        </p>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout title="Unir contas" subtitle="Falta só confirmar que é você.">
      <MergeAccountsForm token={pending.token} email={pending.email} />
    </AuthLayout>
  )
}
