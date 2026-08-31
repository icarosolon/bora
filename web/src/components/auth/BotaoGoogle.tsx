'use client'

import { useState } from 'react'
import { Aviso } from '@/components/ui/aviso'
import { Button } from '@/components/ui/button'
import { chamarApi } from '@/lib/api'
import { useHidratado } from '@/lib/hidratacao'

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
export function BotaoGoogle({ rotulo = 'Entrar com Google' }: { rotulo?: string }) {
  const [indo, setIndo] = useState(false)
  const [erro, setErro] = useState<string | null>(null)

  // Antes da hidratação o `onClick` não existe e o toque seria silenciosamente
  // ignorado — a pessoa aperta e nada acontece. Ver `useHidratado`.
  const pronto = useHidratado()

  async function ir() {
    if (indo) return

    setIndo(true)
    setErro(null)

    const resultado = await chamarApi<{ url: string; state: string }>('/auth/google/url')

    if (resultado.tipo !== 'ok') {
      setErro(resultado.mensagem)
      setIndo(false)
      return
    }

    // Navegação de página inteira, não `router.push`: o destino é o Google.
    window.location.href = resultado.dados.url
  }

  return (
    <div className="flex flex-col gap-3">
      {erro && <Aviso tipo="erro">{erro}</Aviso>}

      <Button
        type="button"
        variant="outline"
        onClick={ir}
        disabled={indo || !pronto}
        aria-busy={indo || !pronto}
        className="min-h-11 w-full text-base"
      >
        {indo ? 'Abrindo o Google…' : pronto ? rotulo : 'Carregando…'}
      </Button>
    </div>
  )
}
