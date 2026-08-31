import 'vitest'

/**
 * Declara o matcher do jest-axe para o Vitest.
 *
 * `expect.extend(toHaveNoViolations)` funciona em runtime, mas o TypeScript não
 * sabe disso sozinho — e `npm run build` roda type-check, então sem esta
 * declaração o build quebra mesmo com todos os testes passando.
 */
declare module 'vitest' {
  interface Assertion<T = unknown> {
    toHaveNoViolations(): T
  }
  interface AsymmetricMatchersContaining {
    toHaveNoViolations(): void
  }
}
