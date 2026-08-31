import Link from 'next/link'
import { FormularioEntrar } from '@/components/auth/FormularioEntrar'
import { LayoutAuth } from '@/components/auth/LayoutAuth'

export const metadata = { title: 'Entrar — Bora' }

/**
 * Tela Entrar. Acao principal: entrar.
 *
 * Componente de SERVIDOR: so estrutura e texto. O que e interativo (e o que
 * toca no token) fica no FormularioEntrar, que e de cliente. A fronteira e
 * explicita de proposito — nenhum segredo a atravessa.
 */
export default function Entrar() {
  return (
    <LayoutAuth
      titulo="Entrar"
      subtitulo="Que bom te ver de novo."
      rodape={
        <p>
          Ainda nao tem conta?{' '}
          <Link href="/criar-conta" className="font-medium underline underline-offset-4">
            Criar conta
          </Link>
        </p>
      }
    >
      <FormularioEntrar />

      <p className="mt-4 text-base">
        <Link
          href="/esqueci-senha"
          className="inline-flex min-h-11 items-center underline underline-offset-4"
        >
          Esqueci minha senha
        </Link>
      </p>
    </LayoutAuth>
  )
}
