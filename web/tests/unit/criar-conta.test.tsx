import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { FormularioCriarConta } from '@/components/auth/FormularioCriarConta'

const push = vi.fn()
vi.mock('next/navigation', () => ({ useRouter: () => ({ push }) }))

function responderCom(status: number, corpo: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(),
    json: async () => corpo,
  })
}

describe('formulário de criar conta', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('mostra o erro de cada campo no lugar certo', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(422, {
        message: 'Confira os campos destacados.',
        errors: {
          email: ['Digite um e-mail válido, como nome@exemplo.com.'],
          senha: ['A senha precisa de pelo menos 8 caracteres.'],
        },
      }),
    )

    render(<FormularioCriarConta />)
    await userEvent.click(screen.getByRole('button', { name: 'Criar conta' }))

    await waitFor(() => {
      expect(screen.getByLabelText('E-mail')).toHaveAttribute('aria-invalid', 'true')
    })
    expect(screen.getByText(/e-mail válido/)).toBeInTheDocument()
    expect(screen.getByText(/pelo menos 8 caracteres/)).toBeInTheDocument()
  })

  it('guarda o token e navega quando dá certo', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(201, {
        message: 'Conta criada! Boas-vindas ao Bora.',
        data: { conta: { id: 1, nome: 'Maria' }, token: 'abc123', expira_em: '2026-09-29T00:00:00-03:00' },
      }),
    )

    render(<FormularioCriarConta />)
    await userEvent.type(screen.getByLabelText('E-mail'), 'maria@exemplo.com')
    await userEvent.type(screen.getByLabelText('Senha'), 'senhaforte1')
    await userEvent.click(screen.getByRole('button', { name: 'Criar conta' }))

    await waitFor(() => expect(localStorage.getItem('bora.sessao.token')).toBe('abc123'))
    expect(push).toHaveBeenCalled()
  })

  it('mostra mensagem humana quando a rede falha', async () => {
    // Nunca "Failed to fetch" na cara da pessoa (ux-requirements.md).
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    render(<FormularioCriarConta />)
    await userEvent.click(screen.getByRole('button', { name: 'Criar conta' }))

    const aviso = await screen.findByRole('alert')
    expect(aviso).toHaveTextContent(/conexão/i)
    expect(aviso.textContent).not.toMatch(/failed to fetch/i)
  })

  it('desabilita o botão durante o envio, impedindo dupla submissão', async () => {
    let liberar: (v: unknown) => void = () => {}
    const pendente = new Promise((r) => {
      liberar = r
    })
    vi.stubGlobal('fetch', vi.fn().mockReturnValue(pendente))

    render(<FormularioCriarConta />)
    const botao = screen.getByRole('button', { name: 'Criar conta' })
    await userEvent.click(botao)

    await waitFor(() => expect(screen.getByRole('button', { name: 'Criando…' })).toBeDisabled())

    liberar({ ok: true, status: 201, headers: new Headers(), json: async () => ({ data: { token: 'x' } }) })
  })

  it('os campos têm rótulo visível e dica associada', () => {
    vi.stubGlobal('fetch', vi.fn())
    render(<FormularioCriarConta />)

    expect(screen.getByLabelText('E-mail')).toBeInTheDocument()
    expect(screen.getByLabelText('Senha')).toBeInTheDocument()
    expect(screen.getByLabelText('Como você quer ser chamado')).toBeInTheDocument()
    expect(screen.getByText(/e-mail que você acessa/)).toBeInTheDocument()
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<FormularioCriarConta />)
    expect(await axe(container)).toHaveNoViolations()
  })
})
