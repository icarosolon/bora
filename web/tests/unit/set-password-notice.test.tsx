import { render, screen, waitFor } from '@testing-library/react'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { AccountHeader } from '@/components/auth/AccountHeader'

let pathname = '/'

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn(), replace: vi.fn() }),
  usePathname: () => pathname,
}))

function respondWithAccount(signsInWith: string[]) {
  return vi.fn().mockResolvedValue({
    ok: true,
    status: 200,
    headers: new Headers(),
    json: async () => ({
      data: {
        id: 1,
        name: 'Maria',
        email: 'maria@exemplo.com',
        email_verified: true,
        signs_in_with: signsInWith,
      },
    }),
  })
}

const NOTICE = 'Definir senha'

describe('caminho para definir a primeira senha (US2-5, FR-012)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('bora.session.token', 'token-de-teste')
    pathname = '/'
  })

  it('oferece definir senha quando a conta só entra pelo Google', async () => {
    vi.stubGlobal('fetch', respondWithAccount(['google']))

    render(<AccountHeader />)

    const link = await screen.findByRole('link', { name: NOTICE })
    expect(link).toHaveAttribute('href', '/definir-senha')
  })

  it('não oferece quando a conta já tem senha', async () => {
    vi.stubGlobal('fetch', respondWithAccount(['password', 'google']))

    render(<AccountHeader />)

    // Espera o cabeçalho resolver a sessão antes de afirmar a ausência.
    await screen.findByRole('button', { name: 'Sair' })
    expect(screen.queryByRole('link', { name: NOTICE })).not.toBeInTheDocument()
  })

  it('não oferece para quem não entrou', async () => {
    localStorage.clear()
    vi.stubGlobal('fetch', vi.fn())

    render(<AccountHeader />)

    await screen.findByRole('link', { name: 'Entrar' })
    expect(screen.queryByRole('link', { name: NOTICE })).not.toBeInTheDocument()
  })

  it('não se oferece dentro da própria tela de definir senha', async () => {
    pathname = '/definir-senha'
    vi.stubGlobal('fetch', respondWithAccount(['google']))

    render(<AccountHeader />)

    await screen.findByRole('button', { name: 'Sair' })
    expect(screen.queryByRole('link', { name: NOTICE })).not.toBeInTheDocument()
  })

  it('se cala quando a API não informa as formas de entrar', async () => {
    // Resposta sem `signs_in_with`: na dúvida não se pede senha a quem talvez
    // já tenha uma.
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        headers: new Headers(),
        json: async () => ({
          data: { id: 1, name: 'Maria', email: 'maria@exemplo.com', email_verified: true },
        }),
      }),
    )

    render(<AccountHeader />)

    await screen.findByRole('button', { name: 'Sair' })
    expect(screen.queryByRole('link', { name: NOTICE })).not.toBeInTheDocument()
  })

  it('não tem violação de acessibilidade', async () => {
    vi.stubGlobal('fetch', respondWithAccount(['google']))

    const { container } = render(<AccountHeader />)
    await screen.findByRole('link', { name: NOTICE })

    await waitFor(async () => {
      expect(await axe(container)).toHaveNoViolations()
    })
  })
})
