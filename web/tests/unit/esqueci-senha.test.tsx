import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EsqueciSenha from '@/app/esqueci-senha/page'

vi.mock('next/navigation', () => ({ useRouter: () => ({ push: vi.fn(), replace: vi.fn() }) }))

const MENSAGEM_NEUTRA =
  'Se este e-mail estiver cadastrado, você receberá um link para redefinir a senha.'

function responderCom(status: number, corpo: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(),
    json: async () => corpo,
  })
}

describe('tela de esqueci minha senha', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('mostra a mensagem neutra da API após o envio', async () => {
    vi.stubGlobal('fetch', responderCom(200, { message: MENSAGEM_NEUTRA }))

    render(<EsqueciSenha />)
    await userEvent.type(screen.getByLabelText('E-mail'), 'maria@exemplo.com')
    await userEvent.click(await screen.findByRole('button', { name: 'Enviar link' }))

    await waitFor(() =>
      expect(screen.getByText(/Se este e-mail estiver cadastrado/)).toBeInTheDocument(),
    )
  })

  it('o estado de sucesso ensina o próximo passo', async () => {
    // ux-requirements.md: estado de sucesso ensina, nunca só confirma.
    vi.stubGlobal('fetch', responderCom(200, { message: MENSAGEM_NEUTRA }))

    render(<EsqueciSenha />)
    await userEvent.click(await screen.findByRole('button', { name: 'Enviar link' }))

    await waitFor(() => expect(screen.getByText(/spam/i)).toBeInTheDocument())
    expect(screen.getByText(/1 hora/)).toBeInTheDocument()
  })

  it('não diz que o e-mail não existe', async () => {
    // A tela repete a API; interpretar aqui desfaria a não-enumeração.
    vi.stubGlobal('fetch', responderCom(200, { message: MENSAGEM_NEUTRA }))

    render(<EsqueciSenha />)
    await userEvent.type(screen.getByLabelText('E-mail'), 'ninguem@exemplo.com')
    await userEvent.click(await screen.findByRole('button', { name: 'Enviar link' }))

    await waitFor(() => expect(screen.getByText(/Se este e-mail estiver/)).toBeInTheDocument())
    expect(screen.queryByText(/não encontrad|não existe/i)).not.toBeInTheDocument()
  })

  it('mostra erro no campo quando o e-mail é malformado', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(422, {
        message: 'Confira os campos destacados.',
        errors: { email: ['Digite um e-mail válido, como nome@exemplo.com.'] },
      }),
    )

    render(<EsqueciSenha />)
    await userEvent.click(await screen.findByRole('button', { name: 'Enviar link' }))

    await waitFor(() =>
      expect(screen.getByLabelText('E-mail')).toHaveAttribute('aria-invalid', 'true'),
    )
    expect(screen.getByText(/e-mail válido/)).toBeInTheDocument()
  })

  it('mostra quanto esperar quando o limite é excedido', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(429, { message: 'Muitas tentativas. Aguarde 1 minuto e tente de novo.' }),
    )

    render(<EsqueciSenha />)
    await userEvent.click(await screen.findByRole('button', { name: 'Enviar link' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/Aguarde 1 minuto/)
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<EsqueciSenha />)

    expect(await axe(container)).toHaveNoViolations()
  })
})
