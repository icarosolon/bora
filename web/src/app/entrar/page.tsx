import Link from 'next/link'
import { BotaoGoogle } from '@/components/auth/BotaoGoogle'
import { FormularioEntrar } from '@/components/auth/FormularioEntrar'
import { LayoutAuth } from '@/components/auth/LayoutAuth'

export const metadata = { title: 'Entrar — Bora' }

/**
 * Tela Entrar. Ação principal: entrar.
 *
 * Componente de SERVIDOR: só estrutura e texto. O que é interativo (e o que
 * toca no token) fica nos componentes cliente. A fronteira é explícita de
 * propósito — nenhum segredo a atravessa.
 *
 * Ordem dos caminhos: Google primeiro, por ser o de menor fricção; e-mail e
 * senha logo abaixo, separados por um divisor com texto (não só por linha —
 * informação nunca é passada apenas por forma).
 */
export default function Entrar() {
  return (
    <LayoutAuth
      titulo="Entrar"
      subtitulo="Que bom te ver de novo."
      rodape={
        <p>
          Ainda não tem conta?{' '}
          <Link href="/criar-conta" className="font-medium underline underline-offset-4">
            Criar conta
          </Link>
        </p>
      }
    >
      <div className="flex flex-col gap-6">
        <BotaoGoogle />

        <div className="flex items-center gap-3" aria-hidden="true">
          <span className="h-px flex-1 bg-border" />
          <span className="text-sm text-muted-foreground">ou</span>
          <span className="h-px flex-1 bg-border" />
        </div>

        <FormularioEntrar />

        <p className="text-base">
          <Link
            href="/esqueci-senha"
            className="inline-flex min-h-11 items-center underline underline-offset-4"
          >
            Esqueci minha senha
          </Link>
        </p>
      </div>
    </LayoutAuth>
  )
}
