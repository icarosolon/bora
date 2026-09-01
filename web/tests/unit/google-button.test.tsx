import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { GoogleButton } from '@/components/auth/GoogleButton'

function respondWith(status: number, body: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(),
    json: async () => body,
  })
}

describe('botão de entrar com Google', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('tem rótulo de texto, não só ícone', () => {
    // ux-requirements.md: ícone nunca sozinho para ação importante.
    vi.stubGlobal('fetch', vi.fn())
    render(<GoogleButton />)

    expect(screen.getByRole('button', { name: 'Entrar com Google' })).toBeInTheDocument()
  })

  it('busca a URL de autorização na API e navega para ela', async () => {
    const fetchMock = respondWith(200, {
      data: { url: 'https://accounts.google.com/o/oauth2/v2/auth?state=abc', state: 'abc' },
    })
    vi.stubGlobal('fetch', fetchMock)

    // jsdom não navega; observa-se a atribuição.
    const location = { href: '' }
    Object.defineProperty(window, 'location', { value: location, writable: true })

    render(<GoogleButton />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    await waitFor(() =>
      expect(location.href).toBe('https://accounts.google.com/o/oauth2/v2/auth?state=abc'),
    )
    expect(fetchMock.mock.calls[0][0]).toContain('/auth/google/url')
  })

  it('mostra estado de carregando e evita duplo disparo', async () => {
    let release: (v: unknown) => void = () => {}
    vi.stubGlobal('fetch', vi.fn().mockReturnValue(new Promise((r) => { release = r })))

    render(<GoogleButton />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    const button = await screen.findByRole('button', { name: 'Abrindo o Google…' })
    expect(button).toBeDisabled()
    expect(button).toHaveAttribute('aria-busy', 'true')

    release({ ok: true, status: 200, headers: new Headers(), json: async () => ({ data: { url: '#' } }) })
  })

  it('mostra mensagem humana quando a API falha', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    render(<GoogleButton />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    const alert = await screen.findByRole('alert')
    expect(alert).toHaveTextContent(/conexão/i)
    expect(alert.textContent).not.toMatch(/failed to fetch/i)
  })

  it('volta a permitir tentar depois de uma falha', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    render(<GoogleButton />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    await screen.findByRole('alert')
    expect(screen.getByRole('button', { name: 'Entrar com Google' })).toBeEnabled()
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<GoogleButton />)

    expect(await axe(container)).toHaveNoViolations()
  })
})
