import { render, screen } from '@testing-library/react'
import { axe } from 'jest-axe'
import { describe, expect, it } from 'vitest'
import { Aviso } from '@/components/ui/aviso'
import { Campo } from '@/components/ui/campo'

describe('primitivas acessiveis da fundacao', () => {
  it('associa o rotulo ao campo', () => {
    render(<Campo rotulo="E-mail" type="email" />)
    expect(screen.getByLabelText('E-mail')).toBeInTheDocument()
  })

  it('anuncia o erro do campo e marca como invalido', () => {
    render(<Campo rotulo="Senha" erro="A senha precisa de pelo menos 8 caracteres." />)
    expect(screen.getByRole('alert')).toHaveTextContent('pelo menos 8 caracteres')
    expect(screen.getByLabelText('Senha')).toHaveAttribute('aria-invalid', 'true')
  })

  it('liga a dica ao campo por aria-describedby', () => {
    render(<Campo rotulo="E-mail" dica="Use um e-mail que voce acessa." />)
    const input = screen.getByLabelText('E-mail')
    expect(input.getAttribute('aria-describedby')).toBeTruthy()
  })

  it('usa role alert para erro e status para sucesso', () => {
    const { unmount } = render(<Aviso tipo="erro">Falhou</Aviso>)
    expect(screen.getByRole('alert')).toBeInTheDocument()
    unmount()
    render(<Aviso tipo="sucesso">Pronto</Aviso>)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })

  it('nao acusa violacao no axe', async () => {
    const { container } = render(
      <form>
        <Campo rotulo="E-mail" type="email" dica="Use um e-mail que voce acessa." />
        <Campo rotulo="Senha" type="password" erro="Senha muito curta." />
        <Aviso tipo="erro">Confira os campos destacados.</Aviso>
      </form>,
    )
    expect(await axe(container)).toHaveNoViolations()
  })
})
