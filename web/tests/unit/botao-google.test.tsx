import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { axe } from 'jest-axe'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { BotaoGoogle } from '@/components/auth/BotaoGoogle'

function responderCom(status: number, corpo: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    headers: new Headers(),
    json: async () => corpo,
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
    render(<BotaoGoogle />)

    expect(screen.getByRole('button', { name: 'Entrar com Google' })).toBeInTheDocument()
  })

  it('busca a URL de autorização na API e navega para ela', async () => {
    const fetchMock = responderCom(200, {
      data: { url: 'https://accounts.google.com/o/oauth2/v2/auth?state=abc', state: 'abc' },
    })
    vi.stubGlobal('fetch', fetchMock)

    // jsdom não navega; observa-se a atribuição.
    const location = { href: '' }
    Object.defineProperty(window, 'location', { value: location, writable: true })

    render(<BotaoGoogle />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    await waitFor(() =>
      expect(location.href).toBe('https://accounts.google.com/o/oauth2/v2/auth?state=abc'),
    )
    expect(fetchMock.mock.calls[0][0]).toContain('/auth/google/url')
  })

  it('mostra estado de carregando e evita duplo disparo', async () => {
    let liberar: (v: unknown) => void = () => {}
    vi.stubGlobal('fetch', vi.fn().mockReturnValue(new Promise((r) => { liberar = r })))

    render(<BotaoGoogle />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    const botao = await screen.findByRole('button', { name: 'Abrindo o Google…' })
    expect(botao).toBeDisabled()
    expect(botao).toHaveAttribute('aria-busy', 'true')

    liberar({ ok: true, status: 200, headers: new Headers(), json: async () => ({ data: { url: '#' } }) })
  })

  it('mostra mensagem humana quando a API falha', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    render(<BotaoGoogle />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    const aviso = await screen.findByRole('alert')
    expect(aviso).toHaveTextContent(/conexão/i)
    expect(aviso.textContent).not.toMatch(/failed to fetch/i)
  })

  it('volta a permitir tentar depois de uma falha', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    render(<BotaoGoogle />)
    await userEvent.click(screen.getByRole('button', { name: 'Entrar com Google' }))

    await screen.findByRole('alert')
    expect(screen.getByRole('button', { name: 'Entrar com Google' })).toBeEnabled()
  })

  it('não acusa violação no axe', async () => {
    vi.stubGlobal('fetch', vi.fn())
    const { container } = render(<BotaoGoogle />)

    expect(await axe(container)).toHaveNoViolations()
  })
})
