'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { buttonVariants } from '@/components/ui/button'
import { cn } from '@/lib/utils'

/**
 * Caminho visível para definir a primeira senha (US2-5, FR-012).
 *
 * Existe porque a tela `/definir-senha` estava implementada e **inalcançável**:
 * nada no produto levava até ela. Quem nascia do Google não tinha senha, e ao
 * tentar entrar por e-mail e senha era corretamente mandado de volta ao Google
 * — um beco sem saída na direção de ganhar uma senha.
 *
 * Não fica no cabeçalho de propósito: a 360px a barra já carrega o nome e
 * "Sair", e um terceiro item apertaria os alvos abaixo dos 44px exigidos pelo
 * `ux-requirements.md`. Em faixa de largura total o texto ainda explica o
 * porquê, que é o requisito de "cada tela se explica sozinha".
 *
 * Some na própria `/definir-senha`: oferecer ali o caminho para onde a pessoa
 * já está seria ruído.
 */
export function SetPasswordNotice() {
  const pathname = usePathname()

  if (pathname === '/definir-senha') return null

  return (
    <section
      aria-label="Formas de entrar na sua conta"
      className="flex flex-col gap-3 border-b bg-muted px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <p className="text-base">
        Você entra pelo Google. Se quiser, defina uma senha para também entrar com seu
        e-mail.
      </p>

      <Link
        href="/definir-senha"
        className={cn(
          buttonVariants({ variant: 'default' }),
          'min-h-11 w-full justify-center text-base sm:w-auto sm:px-6',
        )}
      >
        Definir senha
      </Link>
    </section>
  )
}
