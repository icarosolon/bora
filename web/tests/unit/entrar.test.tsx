import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { FormularioEntrar } from '@/components/auth/FormularioEntrar'

const push = vi.fn()
vi.mock('next/navigation', () => ({ useRouter: () => ({ push }) }))

function responderCom(status: number, corpo: unknown, cabecalhos: Record<string, string> = {}) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(cabecalhos),
    json: async () => corpo,
  })
}

describe('formulário de entrar', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('entra e guarda o token', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(200, {
        data: { conta: { id: 1, nome: 'Maria' }, token: 'tok123', expira_em: '2026-09-29T00:00:00-03:00' },
      }),
    )

    render(<FormularioEntrar />)
    await userEvent.type(screen.getByLabelText('E-mail'), 'maria@exemplo.com')
    await userEvent.type(screen.getByLabelText('Senha'), 'senhaforte1')
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    await waitFor(() => expect(localStorage.getItem('bora.sessao.token')).toBe('tok123'))
  })

  it('mostra a mensagem única da API e oferece recuperar a senha', async () => {
    // A mensagem vem da API para site e app dizerem a mesma coisa, e não
    // revela se o erro foi no e-mail ou na senha.
    vi.stubGlobal(
      'fetch',
      responderCom(401, {
        message: 'E-mail ou senha não conferem. Confira e tente de novo, ou use "Esqueci minha senha".',
      }),
    )

    render(<FormularioEntrar />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    const aviso = await screen.findByRole('alert')
    expect(aviso).toHaveTextContent(/não conferem/)
    expect(aviso).toHaveTextContent(/Esqueci minha senha/)
    expect(localStorage.getItem('bora.sessao.token')).toBeNull()
  })

  it('mostra quanto esperar quando o limite é excedido', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(
        429,
        { message: 'Muitas tentativas. Aguarde 1 minuto e tente de novo.' },
        { 'Retry-After': '60' },
      ),
    )

    render(<FormularioEntrar />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/Aguarde 1 minuto/)
  })

  it('orienta a entrar com Google quando a conta não tem senha', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(401, { message: 'Esta conta entra com o Google. Toque em "Entrar com Google".' }),
    )

    render(<FormularioEntrar />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/Google/)
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<FormularioEntrar />)
    expect(await axe(container)).toHaveNoViolations()
  })
})
