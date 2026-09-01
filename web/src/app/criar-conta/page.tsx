import Link from 'next/link'
import { SignUpForm } from '@/components/auth/SignUpForm'
import { AuthLayout } from '@/components/auth/AuthLayout'

export const metadata = { title: 'Criar conta — Bora' }

/** Tela Criar conta. Acao principal: criar a conta. */
export default function SignUpPage() {
  return (
    <AuthLayout
      title="Criar conta"
      subtitle="E de graca, e sempre vai ser."
      footer={
        <p>
          Ja tem conta?{' '}
          <Link href="/entrar" className="font-medium underline underline-offset-4">
            Entrar
          </Link>
        </p>
      }
    >
      <SignUpForm />
    </AuthLayout>
  )
}
