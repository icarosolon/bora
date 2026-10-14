import { defineConfig, devices } from '@playwright/test'

// Testes de tela da spec 001 e portao de conformidade da spec 002.
// ux-requirements.md: o celular e o dispositivo principal e TODA tela roda em
// pelo menos DUAS larguras — 360 (piso, aparelho modesto) e 1280 (computador).
// O teste falha se houver rolagem horizontal a 360px.

/*
 * Os dois projetos NOVOS (celular-390 e fonte-ampliada-360) rodam so o portao,
 * e nao as suites da spec 001.
 *
 * Nao e economia de tempo, e fidelidade do contrato: as suites da spec 001
 * foram escritas para DUAS larguras e e assim que estao validadas. Solta-las em
 * quatro configuracoes redefiniria, no mesmo dia, a linha de base que a T003
 * acabou de registrar e que a T025 vai usar para conferir o retrofit.
 *
 * Nada de cobertura se perde: o portao visita TODAS as telas da spec 001, entao
 * o cenario de fonte ampliada alcanca cada uma delas por essa via.
 */
const SO_O_PORTAO = /gate[\\/].*\.spec\.ts$/

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  reporter: 'list',
  use: {
    baseURL: process.env.BASE_URL ?? 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  projects: [
    {
      // Piso de largura. Mobile-first e literal: esta e a configuracao que
      // reprova primeiro.
      name: 'celular-360',
      use: { ...devices['Pixel 7'], viewport: { width: 360, height: 740 } },
    },
    {
      name: 'computador-1280',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } },
    },
    {
      // Faixa 390-430 do ux-requirements.md ("celular comum"). Exigida no
      // Detalhe por ser a tela mais densa; o portao a roda em TODAS as telas
      // para nao depender de alguem lembrar de ligar por tela.
      name: 'celular-390',
      testMatch: SO_O_PORTAO,
      use: { ...devices['Pixel 7'], viewport: { width: 390, height: 844 } },
    },
    {
      /*
       * FONTE DO SISTEMA AMPLIADA — nao e zoom de navegador, e o D12 registrou
       * exatamente essa confusao:
       *
       *   zoom do navegador a 200%  -> o viewport de 360px vira ~180px de
       *                                layout, e a fonte continua 16px;
       *   fonte do sistema a 200%   -> o viewport CONTINUA 360px, e a fonte
       *                                vira 32px.
       *
       * O segundo NAO dispara nenhuma media query de largura. Por isso ele
       * precisa de um projeto proprio com o MESMO viewport de 360: um cenario
       * que estreita a janela nao cobre o outro, e achar que cobre e o ponto
       * cego que a D12 mandou fechar.
       *
       * A ampliacao em si nao cabe aqui — nao existe opcao de configuracao do
       * Playwright para o tamanho de fonte padrao do navegador. Ela e aplicada
       * no proprio teste, por CDP (`Page.setFontSizes`), que e o que o
       * navegador muda quando o sistema operacional pede fonte maior. O portao
       * reconhece este projeto pelo NOME; ver `tests/e2e/gate/fonte.ts`.
       */
      name: 'fonte-ampliada-360',
      testMatch: SO_O_PORTAO,
      use: { ...devices['Pixel 7'], viewport: { width: 360, height: 740 } },
    },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
})
