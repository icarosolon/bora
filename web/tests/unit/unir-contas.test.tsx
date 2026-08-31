import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { FormularioUnirContas } from '@/components/auth/FormularioUnirContas'

const replace = vi.fn()
vi.mock('next/navigation', () => ({ useRouter: () => ({ replace, push: vi.fn() }) }))

function responderCom(status: number, corpo: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(),
    json: async () => corpo,
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
    render(<FormularioUnirContas {...props} />)

    const explicacao = screen.getByRole('status')
    expect(explicacao).toHaveTextContent('maria@exemplo.com')
    expect(explicacao).toHaveTextContent(/mesma conta/i)
    expect(explicacao).toHaveTextContent(/nada é duplicado/i)
  })

  it('oferece o plano B desde o começo, sem exigir errar antes', () => {
    vi.stubGlobal('fetch', vi.fn())
    render(<FormularioUnirContas {...props} />)

    expect(screen.getByRole('button', { name: 'Receber link por e-mail' })).toBeInTheDocument()
  })

  it('une e guarda o token quando a senha está certa', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(200, {
        message: 'Pronto — agora você pode entrar com Google ou com sua senha.',
        data: { conta: { id: 1, nome: 'Maria' }, token: 'tok-sessao', expira_em: '2026-09-30T00:00:00-03:00' },
      }),
    )

    render(<FormularioUnirContas {...props} />)
    await userEvent.type(screen.getByLabelText('Sua senha do Bora'), 'senhaforte1')
    await userEvent.click(await screen.findByRole('button', { name: 'Unir e entrar' }))

    await waitFor(() => expect(localStorage.getItem('bora.sessao.token')).toBe('tok-sessao'))
    expect(replace).toHaveBeenCalled()
  })

  it('mostra a mensagem da API quando a senha está errada e aponta o plano B', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(401, {
        message: 'Senha não confere. Tente de novo ou receba um link por e-mail.',
      }),
    )

    render(<FormularioUnirContas {...props} />)
    await userEvent.click(await screen.findByRole('button', { name: 'Unir e entrar' }))

    const aviso = await screen.findByRole('alert')
    expect(aviso).toHaveTextContent(/não confere/)
    expect(aviso).toHaveTextContent(/link por e-mail/)
    expect(localStorage.getItem('bora.sessao.token')).toBeNull()
  })

  it('explica quando o pedido expirou', async () => {
    vi.stubGlobal(
      'fetch',
      responderCom(410, { message: 'Este link expirou ou já foi usado. Entre com o Google de novo para recomeçar.' }),
    )

    render(<FormularioUnirContas {...props} />)
    await userEvent.click(await screen.findByRole('button', { name: 'Unir e entrar' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/expirou/)
  })

  it('confirma o envio do link do plano B', async () => {
    const fetchMock = responderCom(200, {
      message: 'Enviamos um link para o seu e-mail. Ele vale por 1 hora.',
    })
    vi.stubGlobal('fetch', fetchMock)

    render(<FormularioUnirContas {...props} />)
    await userEvent.click(screen.getByRole('button', { name: 'Receber link por e-mail' }))

    await waitFor(() =>
      expect(screen.getByText(/vale por 1 hora/)).toBeInTheDocument(),
    )
    expect(fetchMock.mock.calls[0][0]).toContain('/uniao-credenciais/link')
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<FormularioUnirContas {...props} />)

    expect(await axe(container)).toHaveNoViolations()
  })
})
