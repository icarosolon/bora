import Link from 'next/link'
import { FormularioCriarConta } from '@/components/auth/FormularioCriarConta'
import { LayoutAuth } from '@/components/auth/LayoutAuth'

export const metadata = { title: 'Criar conta — Bora' }

/** Tela Criar conta. Acao principal: criar a conta. */
export default function CriarConta() {
  return (
    <LayoutAuth
      titulo="Criar conta"
      subtitulo="E de graca, e sempre vai ser."
      rodape={
        <p>
          Ja tem conta?{' '}
          <Link href="/entrar" className="font-medium underline underline-offset-4">
            Entrar
          </Link>
        </p>
      }
    >
      <FormularioCriarConta />
    </LayoutAuth>
  )
}
