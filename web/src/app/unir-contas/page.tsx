'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { FormularioUnirContas } from '@/components/auth/FormularioUnirContas'
import { LayoutAuth } from '@/components/auth/LayoutAuth'
import { Aviso } from '@/components/ui/aviso'
import { lerUniaoPendente, type UniaoPendente } from '@/lib/sessao'

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
export default function UnirContas() {
  const [pendente, setPendente] = useState<UniaoPendente | null>(null)
  const [procurando, setProcurando] = useState(true)

  // Leitura em efeito, nunca durante a renderizacao: sessionStorage nao existe
  // no servidor, e ler no JSX causaria divergencia de hidratacao (E-015).
  useEffect(() => {
    setPendente(lerUniaoPendente())
    setProcurando(false)
  }, [])

  if (procurando) {
    return (
      <LayoutAuth titulo="Unir contas">
        <Aviso tipo="informacao">Carregando…</Aviso>
      </LayoutAuth>
    )
  }

  // Sem pedido guardado: aba nova, pedido velho ou visita direta. Explica e
  // devolve o caminho, em vez de mostrar formulario que falharia.
  if (!pendente) {
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
      <FormularioUnirContas token={pendente.token} email={pendente.email} />
    </LayoutAuth>
  )
}
