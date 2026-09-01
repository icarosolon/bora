import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { MergeAccountsForm } from '@/components/auth/MergeAccountsForm'

const replace = vi.fn()
vi.mock('next/navigation', () => ({ useRouter: () => ({ replace, push: vi.fn() }) }))

function respondWith(status: number, body: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(),
    json: async () => body,
  })
}

const props = { token: 'tok-uniao', email: 'maria@exemplo.com' }

describe('tela de unir contas', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('explica o que vai acontecer antes de pedir a senha', () => {
    // ux-requirements.md: a tela se explica sozinha. Pedir senha logo após um
    // login com Google, sem justificar, assusta.
    vi.stubGlobal('fetch', vi.fn())
    render(<MergeAccountsForm {...props} />)

    const explanation = screen.getByRole('status')
    expect(explanation).toHaveTextContent('maria@exemplo.com')
    expect(explanation).toHaveTextContent(/mesma conta/i)
    expect(explanation).toHaveTextContent(/nada é duplicado/i)
  })

  it('oferece o plano B desde o começo, sem exigir errar antes', () => {
    vi.stubGlobal('fetch', vi.fn())
    render(<MergeAccountsForm {...props} />)

    expect(screen.getByRole('button', { name: 'Receber link por e-mail' })).toBeInTheDocument()
  })

  it('une e guarda o token quando a senha está certa', async () => {
    vi.stubGlobal(
      'fetch',
      respondWith(200, {
        message: 'Pronto — agora você pode entrar com Google ou com sua senha.',
        data: { account: { id: 1, name: 'Maria' }, token: 'tok-sessao', expires_at: '2026-09-30T00:00:00-03:00' },
      }),
    )

    render(<MergeAccountsForm {...props} />)
    await userEvent.type(screen.getByLabelText('Sua senha do Bora'), 'senhaforte1')
    await userEvent.click(await screen.findByRole('button', { name: 'Unir e entrar' }))

    await waitFor(() => expect(localStorage.getItem('bora.session.token')).toBe('tok-sessao'))
    expect(replace).toHaveBeenCalled()
  })

  it('mostra a mensagem da API quando a senha está errada e aponta o plano B', async () => {
    vi.stubGlobal(
      'fetch',
      respondWith(401, {
        message: 'Senha não confere. Tente de novo ou receba um link por e-mail.',
      }),
    )

    render(<MergeAccountsForm {...props} />)
    await userEvent.click(await screen.findByRole('button', { name: 'Unir e entrar' }))

    const alert = await screen.findByRole('alert')
    expect(alert).toHaveTextContent(/não confere/)
    expect(alert).toHaveTextContent(/link por e-mail/)
    expect(localStorage.getItem('bora.session.token')).toBeNull()
  })

  it('explica quando o pedido expirou', async () => {
    vi.stubGlobal(
      'fetch',
      respondWith(410, { message: 'Este link expirou ou já foi usado. Entre com o Google de novo para recomeçar.' }),
    )

    render(<MergeAccountsForm {...props} />)
    await userEvent.click(await screen.findByRole('button', { name: 'Unir e entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/expirou/)
  })

  it('confirma o envio do link do plano B', async () => {
    const fetchMock = respondWith(200, {
      message: 'Enviamos um link para o seu e-mail. Ele vale por 1 hora.',
    })
    vi.stubGlobal('fetch', fetchMock)

    render(<MergeAccountsForm {...props} />)
    await userEvent.click(screen.getByRole('button', { name: 'Receber link por e-mail' }))

    await waitFor(() =>
      expect(screen.getByText(/vale por 1 hora/)).toBeInTheDocument(),
    )
    expect(fetchMock.mock.calls[0][0]).toContain('/uniao-credenciais/link')
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<MergeAccountsForm {...props} />)

    expect(await axe(container)).toHaveNoViolations()
  })
})
