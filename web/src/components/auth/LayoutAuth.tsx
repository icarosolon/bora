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
type LayoutAuthProps = {
  titulo: string
  subtitulo?: string
  children: React.ReactNode
  rodape?: React.ReactNode
}

export function LayoutAuth({ titulo, subtitulo, children, rodape }: LayoutAuthProps) {
  return (
    <main className="flex min-h-dvh flex-col px-4 py-8 sm:items-center sm:justify-center">
      <div className="w-full sm:max-w-md sm:rounded-lg sm:border sm:p-8 sm:shadow-sm">
        <header className="mb-6">
          <h1 className="text-2xl font-semibold tracking-tight">{titulo}</h1>
          {subtitulo && (
            <p className="mt-2 text-base text-muted-foreground">{subtitulo}</p>
          )}
        </header>

        {children}

        {rodape && <footer className="mt-6 text-base">{rodape}</footer>}
      </div>
    </main>
  )
}
