'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { callApi } from '@/lib/api'
import { SESSION_EVENT, forgetToken, readToken } from '@/lib/session'

type Account = {
  id: number
  name: string
  email: string
  email_verified: boolean
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
export function AccountHeader() {
  const router = useRouter()
  const [account, setAccount] = useState<Account | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    let active = true

    function checkSession() {
      if (!readToken()) {
        if (active) {
          setAccount(null)
          setLoading(false)
        }
        return
      }

      callApi<Account>('/eu', { authenticated: true }).then((r) => {
        if (!active) return
        setAccount(r.kind === 'ok' ? r.data : null)
        setLoading(false)
      })
    }

    checkSession()

    /*
     * Este componente vive no LAYOUT RAIZ: ele monta uma vez e **não remonta**
     * em navegação client-side. Sem escutar a mudança de sessão, entrar por
     * e-mail e senha guardava o token e navegava para a home, mas a barra
     * continuava mostrando "Entrar" até recarregar a página — parecia que o
     * login tinha falhado, quando tinha dado certo.
     */
    window.addEventListener(SESSION_EVENT, checkSession)

    // `storage` dispara nas OUTRAS abas: sair numa aba atualiza as demais, em
    // vez de deixar uma barra mentindo que a pessoa continua dentro.
    window.addEventListener('storage', checkSession)

    return () => {
      active = false
      window.removeEventListener(SESSION_EVENT, checkSession)
      window.removeEventListener('storage', checkSession)
    }
  }, [])

  async function signOut() {
    await callApi('/sessoes/atual', { method: 'DELETE', authenticated: true })
    forgetToken()
    setAccount(null)
    router.push('/entrar')
  }

  // Enquanto não se sabe, não pisca "Entrar" para quem está logado.
  if (loading) return <header className="min-h-14" />

  return (
    <header className="flex min-h-14 items-center justify-between gap-4 border-b px-4 py-2">
      <Link href="/" className="text-lg font-semibold">
        Bora
      </Link>

      {account ? (
        <div className="flex items-center gap-3">
          <span className="text-base">{account.name}</span>
          <button
            type="button"
            onClick={signOut}
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
