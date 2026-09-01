import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { SignInForm } from '@/components/auth/SignInForm'

const push = vi.fn()
vi.mock('next/navigation', () => ({ useRouter: () => ({ push }) }))

function respondWith(status: number, body: unknown, headers: Record<string, string> = {}) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(headers),
    json: async () => body,
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
      respondWith(200, {
        data: { account: { id: 1, name: 'Maria' }, token: 'tok123', expires_at: '2026-09-29T00:00:00-03:00' },
      }),
    )

    render(<SignInForm />)
    await userEvent.type(screen.getByLabelText('E-mail'), 'maria@exemplo.com')
    await userEvent.type(screen.getByLabelText('Senha'), 'senhaforte1')
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    await waitFor(() => expect(localStorage.getItem('bora.session.token')).toBe('tok123'))
  })

  it('mostra a mensagem única da API e oferece recuperar a senha', async () => {
    // A mensagem vem da API para site e app dizerem a mesma coisa, e não
    // revela se o erro foi no e-mail ou na senha.
    vi.stubGlobal(
      'fetch',
      respondWith(401, {
        message: 'E-mail ou senha não conferem. Confira e tente de novo, ou use "Esqueci minha senha".',
      }),
    )

    render(<SignInForm />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    const alert = await screen.findByRole('alert')
    expect(alert).toHaveTextContent(/não conferem/)
    expect(alert).toHaveTextContent(/Esqueci minha senha/)
    expect(localStorage.getItem('bora.session.token')).toBeNull()
  })

  it('mostra quanto esperar quando o limite é excedido', async () => {
    vi.stubGlobal(
      'fetch',
      respondWith(
        429,
        { message: 'Muitas tentativas. Aguarde 1 minuto e tente de novo.' },
        { 'Retry-After': '60' },
      ),
    )

    render(<SignInForm />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/Aguarde 1 minuto/)
  })

  it('orienta a entrar com Google quando a conta não tem senha', async () => {
    vi.stubGlobal(
      'fetch',
      respondWith(401, { message: 'Esta conta entra com o Google. Toque em "Entrar com Google".' }),
    )

    render(<SignInForm />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/Google/)
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<SignInForm />)
    expect(await axe(container)).toHaveNoViolations()
  })
})
