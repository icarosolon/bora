/**
 * Moldura das telas de conta (entrar, criar conta, unir contas, senha).
 *
 * Mobile-first LITERAL, como manda o ux-requirements.md: o estilo base é o do
 * celular e as media queries só AMPLIAM.
 *
 * - **360px (piso)**: uma coluna, largura total com margens confortáveis,
 *   nenhuma rolagem horizontal.
 * - **≥ 640px**: vira cartão centralizado com largura máxima legível. O
 *   computador mostra melhor, não espalha controles.
 *
 * Componente de servidor de propósito: é só estrutura, não tem estado nem
 * token. O que é interativo mora nos formulários, que são de cliente — a
 * fronteira fica explícita e nenhum segredo a atravessa.
 */
type AuthLayoutProps = {
  title: string
  subtitle?: string
  children: React.ReactNode
  footer?: React.ReactNode
}

export function AuthLayout({ title, subtitle, children, footer }: AuthLayoutProps) {
  return (
    <main className="flex min-h-dvh flex-col px-4 py-8 sm:items-center sm:justify-center">
      <div className="w-full sm:max-w-md sm:rounded-lg sm:border sm:p-8 sm:shadow-sm">
        <header className="mb-6">
          <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
          {subtitle && (
            <p className="mt-2 text-base text-muted-foreground">{subtitle}</p>
          )}
        </header>

        {children}

        {footer && <footer className="mt-6 text-base">{footer}</footer>}
      </div>
    </main>
  )
}
