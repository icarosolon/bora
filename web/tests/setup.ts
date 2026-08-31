import '@testing-library/jest-dom/vitest'
import { expect } from 'vitest'
import { toHaveNoViolations } from 'jest-axe'

// axe como asercao de primeira classe: `expect(await axe(container)).toHaveNoViolations()`.
// O criterio de aprovacao da spec 001 e ZERO violacoes nas cinco telas.
expect.extend(toHaveNoViolations)
