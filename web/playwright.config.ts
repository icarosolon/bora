import { defineConfig, devices } from '@playwright/test'

// Testes de tela da spec 001.
// ux-requirements.md: o celular e o dispositivo principal e TODA tela roda em
// pelo menos DUAS larguras — 360 (piso, aparelho modesto) e 1280 (computador).
// O teste falha se houver rolagem horizontal a 360px.
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
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
})
