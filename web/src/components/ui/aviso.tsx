'use client'

import { cn } from '@/lib/utils'

/**
 * Faixa de mensagem no topo do formulário — erro geral, sucesso ou informação.
 *
 * Usada para o que não pertence a um campo específico: provedor indisponível,
 * limite de tentativas, confirmação de ação concluída.
 *
 * Acessibilidade: `role="alert"` para erro (interrompe e anuncia na hora),
 * `role="status"` para o resto (anuncia sem interromper). E a informação nunca
 * é passada só por cor — sempre há texto (ux-requirements.md).
 */
type AvisoProps = {
  tipo: 'erro' | 'sucesso' | 'informacao'
  children: React.ReactNode
  className?: string
}

const estilos = {
  erro: 'border-destructive/50 bg-destructive/10 text-destructive',
  sucesso: 'border-emerald-600/50 bg-emerald-600/10 text-emerald-800 dark:text-emerald-300',
  informacao: 'border-input bg-muted text-foreground',
} as const

export function Aviso({ tipo, children, className }: AvisoProps) {
  return (
    <p
      role={tipo === 'erro' ? 'alert' : 'status'}
      className={cn('rounded-md border px-3 py-2 text-base', estilos[tipo], className)}
    >
      {children}
    </p>
  )
}
