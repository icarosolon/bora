'use client'

import { useState } from 'react'
import { Alert } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { callApi } from '@/lib/api'
import { useHydrated } from '@/lib/hydration'

/**
 * "Entrar com Google" (US2).
 *
 * Fica **acima** do formulário de e-mail/senha porque é o caminho de menor
 * fricção — quem tem conta Google resolve em dois toques.
 *
 * Acessibilidade: **rótulo de texto**, não ícone solto
 * (`ux-requirements.md`: ícone nunca sozinho para ação importante). O desenho
 * é o mesmo botão das outras ações, com alvo de toque ≥ 44px.
 *
 * O passo 1 do OAuth é feito pela API: ela devolve a URL de autorização e o
 * `state`. O front só navega — nenhuma regra aqui (Princípio IV).
 */
export function GoogleButton({ label = 'Entrar com Google' }: { label?: string }) {
  const [redirecting, setRedirecting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Antes da hidratação o `onClick` não existe e o toque seria silenciosamente
  // ignorado — a pessoa aperta e nada acontece. Ver `useHydrated`.
  const ready = useHydrated()

  async function go() {
    if (redirecting) return

    setRedirecting(true)
    setError(null)

    const result = await callApi<{ url: string; state: string }>('/auth/google/url')

    if (result.kind !== 'ok') {
      setError(result.message)
      setRedirecting(false)
      return
    }

    // Navegação de página inteira, não `router.push`: o destino é o Google.
    window.location.href = result.data.url
  }

  return (
    <div className="flex flex-col gap-3">
      {error && <Alert kind="error">{error}</Alert>}

      <Button
        type="button"
        variant="outline"
        onClick={go}
        disabled={redirecting || !ready}
        aria-busy={redirecting || !ready}
        className="min-h-11 w-full text-base"
      >
        {redirecting ? 'Abrindo o Google…' : ready ? label : 'Carregando…'}
      </Button>
    </div>
  )
}
