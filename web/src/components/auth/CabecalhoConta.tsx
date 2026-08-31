'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { chamarApi } from '@/lib/api'
import { EVENTO_SESSAO, esquecerToken, lerToken } from '@/lib/sessao'

type Conta = {
  id: number
  nome: string
  email: string
  email_verificado: boolean
}

/**
 * Estado autenticado no cabeçalho (US1-3).
 *
 * Componente de cliente porque o token só existe no navegador — e ele NUNCA
 * pode vir do servidor por prop, sob pena de acabar serializado no HTML.
 *
 * Acessibilidade: "Sair" é botão com **rótulo de texto**, não ícone solto
 * (ux-requirements.md: ícone nunca sozinho para ação importante).
 */
export function CabecalhoConta() {
  const router = useRouter()
  const [conta, setConta] = useState<Conta | null>(null)
  const [carregando, setCarregando] = useState(true)

  useEffect(() => {
    let ativo = true

    function conferirSessao() {
      if (!lerToken()) {
        if (ativo) {
          setConta(null)
          setCarregando(false)
        }
        return
      }

      chamarApi<Conta>('/eu', { autenticado: true }).then((r) => {
        if (!ativo) return
        setConta(r.tipo === 'ok' ? r.dados : null)
        setCarregando(false)
      })
    }

    conferirSessao()

    /*
     * Este componente vive no LAYOUT RAIZ: ele monta uma vez e **não remonta**
     * em navegação client-side. Sem escutar a mudança de sessão, entrar por
     * e-mail e senha guardava o token e navegava para a home, mas a barra
     * continuava mostrando "Entrar" até recarregar a página — parecia que o
     * login tinha falhado, quando tinha dado certo.
     */
    window.addEventListener(EVENTO_SESSAO, conferirSessao)

    // `storage` dispara nas OUTRAS abas: sair numa aba atualiza as demais, em
    // vez de deixar uma barra mentindo que a pessoa continua dentro.
    window.addEventListener('storage', conferirSessao)

    return () => {
      ativo = false
      window.removeEventListener(EVENTO_SESSAO, conferirSessao)
      window.removeEventListener('storage', conferirSessao)
    }
  }, [])

  async function sair() {
    await chamarApi('/sessoes/atual', { metodo: 'DELETE', autenticado: true })
    esquecerToken()
    setConta(null)
    router.push('/entrar')
  }

  // Enquanto não se sabe, não pisca "Entrar" para quem está logado.
  if (carregando) return <header className="min-h-14" />

  return (
    <header className="flex min-h-14 items-center justify-between gap-4 border-b px-4 py-2">
      <Link href="/" className="text-lg font-semibold">
        Bora
      </Link>

      {conta ? (
        <div className="flex items-center gap-3">
          <span className="text-base">{conta.nome}</span>
          <button
            type="button"
            onClick={sair}
            className="min-h-11 rounded-md border px-4 text-base focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            Sair
          </button>
        </div>
      ) : (
        <Link
          href="/entrar"
          className="flex min-h-11 items-center rounded-md border px-4 text-base focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        >
          Entrar
        </Link>
      )}
    </header>
  )
}
