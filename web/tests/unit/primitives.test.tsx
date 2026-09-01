import { render, screen } from '@testing-library/react'
import { axe } from 'jest-axe'
import { describe, expect, it } from 'vitest'
import { Alert } from '@/components/ui/alert'
import { Field } from '@/components/ui/field'

describe('primitivas acessiveis da fundacao', () => {
  it('associa o rotulo ao campo', () => {
    render(<Field label="E-mail" type="email" />)
    expect(screen.getByLabelText('E-mail')).toBeInTheDocument()
  })

  it('anuncia o erro do campo e marca como invalido', () => {
    render(<Field label="Senha" error="A senha precisa de pelo menos 8 caracteres." />)
    expect(screen.getByRole('alert')).toHaveTextContent('pelo menos 8 caracteres')
    expect(screen.getByLabelText('Senha')).toHaveAttribute('aria-invalid', 'true')
  })

  it('liga a dica ao campo por aria-describedby', () => {
    render(<Field label="E-mail" hint="Use um e-mail que voce acessa." />)
    const input = screen.getByLabelText('E-mail')
    expect(input.getAttribute('aria-describedby')).toBeTruthy()
  })

  it('usa role alert para erro e status para sucesso', () => {
    const { unmount } = render(<Alert kind="error">Falhou</Alert>)
    expect(screen.getByRole('alert')).toBeInTheDocument()
    unmount()
    render(<Alert kind="success">Pronto</Alert>)
    expect(screen.getByRole('status')).toBeInTheDocument()
  })

  it('nao acusa violacao no axe', async () => {
    const { container } = render(
      <form>
        <Field label="E-mail" type="email" hint="Use um e-mail que voce acessa." />
        <Field label="Senha" type="password" error="Senha muito curta." />
        <Alert kind="error">Confira os campos destacados.</Alert>
      </form>,
    )
    expect(await axe(container)).toHaveNoViolations()
  })
})
