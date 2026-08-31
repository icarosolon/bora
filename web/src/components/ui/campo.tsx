'use client'

import { useId } from 'react'
import { cn } from '@/lib/utils'

/**
 * Campo de formulário acessível (ux-requirements.md).
 *
 * O que este componente garante, e por quê:
 *
 * - **Rótulo sempre visível e associado** ao input pelo `id`. Placeholder como
 *   único rótulo some quando a pessoa começa a digitar — é a falha clássica que
 *   derruba quem tem pouca familiaridade com formulário.
 * - **Erro anunciado a leitor de tela** via `aria-describedby` + `role="alert"`,
 *   e ligado ao campo por `aria-invalid`.
 * - **Alvo de toque ≥ 44px** (`min-h-11`) e **fonte ≥ 16px** (`text-base`) —
 *   abaixo de 16px o iOS dá zoom automático ao focar, o que desloca a tela.
 * - **Foco visível** com anel de contraste; nunca `outline: none` solto.
 */
type CampoProps = {
  rotulo: string
  erro?: string
  dica?: string
} & Omit<React.ComponentProps<'input'>, 'id'>

export function Campo({ rotulo, erro, dica, className, ...props }: CampoProps) {
  const id = useId()
  const idErro = `${id}-erro`
  const idDica = `${id}-dica`

  const descritoPor = [erro ? idErro : null, dica ? idDica : null]
    .filter(Boolean)
    .join(' ')

  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-base font-medium">
        {rotulo}
      </label>

      {dica && (
        <p id={idDica} className="text-sm text-muted-foreground">
          {dica}
        </p>
      )}

      <input
        id={id}
        aria-invalid={erro ? true : undefined}
        aria-describedby={descritoPor || undefined}
        className={cn(
          'min-h-11 w-full rounded-md border bg-background px-3 py-2 text-base',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
          'disabled:cursor-not-allowed disabled:opacity-60',
          erro ? 'border-destructive' : 'border-input',
          className,
        )}
        {...props}
      />

      {erro && (
        // role="alert" faz o leitor de tela anunciar o erro assim que aparece,
        // sem a pessoa precisar navegar até o campo para descobrir o problema.
        <p id={idErro} role="alert" className="text-sm font-medium text-destructive">
          {erro}
        </p>
      )}
    </div>
  )
}
