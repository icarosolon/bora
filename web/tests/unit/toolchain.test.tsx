import { render, screen } from '@testing-library/react'
import { axe } from 'jest-axe'
import { describe, expect, it } from 'vitest'
import { Button } from '@/components/ui/button'

// Teste de fumaça da cadeia de testes de front (decisao D4 da spec 001).
// Nao testa regra de negocio: prova que Vitest + jsdom + Testing Library + axe
// + o alias @/ + as primitivas do shadcn funcionam juntos. Se este quebrar,
// o problema e de ferramenta, nao de tela.
describe('cadeia de testes do front', () => {
  it('renderiza uma primitiva e a encontra por papel acessivel', () => {
    render(<Button>Entrar</Button>)
    expect(screen.getByRole('button', { name: 'Entrar' })).toBeInTheDocument()
  })

  it('roda o axe e nao acusa violacao', async () => {
    const { container } = render(<Button>Entrar</Button>)
    expect(await axe(container)).toHaveNoViolations()
  })
})
