import Link from 'next/link'
import { GoogleButton } from '@/components/auth/GoogleButton'
import { SignInForm } from '@/components/auth/SignInForm'
import { AuthLayout } from '@/components/auth/AuthLayout'

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
export default function SignInPage() {
  return (
    <AuthLayout
      title="Entrar"
      subtitle="Que bom te ver de novo."
      footer={
        <p>
          Ainda não tem conta?{' '}
          <Link href="/criar-conta" className="font-medium underline underline-offset-4">
            Criar conta
          </Link>
        </p>
      }
    >
      <div className="flex flex-col gap-6">
        <GoogleButton />

        <div className="flex items-center gap-3" aria-hidden="true">
          <span className="h-px flex-1 bg-border" />
          <span className="text-sm text-muted-foreground">ou</span>
          <span className="h-px flex-1 bg-border" />
        </div>

        <SignInForm />

        <p className="text-base">
          <Link
            href="/esqueci-senha"
            className="inline-flex min-h-11 items-center underline underline-offset-4"
          >
            Esqueci minha senha
          </Link>
        </p>
      </div>
    </AuthLayout>
  )
}
