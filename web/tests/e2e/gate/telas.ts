import type { Page } from '@playwright/test'

/**
 * Catálogo das telas que o portão afere.
 *
 * São as telas **já entregues e já validadas pelo Ícaro** na spec 001. Rodar o
 * portão contra elas é o teste do próprio portão (D4): contrato escrito depois
 * do kit nasce moldado ao kit e não reprova ninguém (E-012).
 *
 * Cada tela declara o preparo mínimo para chegar ao estado ESTÁVEL em que o
 * usuário a vê. Estado que redireciona sozinho não é tela que se mede — é
 * passagem; por isso `/unir-contas/confirmar` entra pelo caminho do link
 * vencido, que é o único em que aquela rota para e mostra algo.
 */
export type Tela = {
  nome: string
  rota: string
  preparar?: (page: Page) => Promise<void>
}

const JSON_HEADER = { contentType: 'application/json' } as const

async function anonima(page: Page) {
  await page.route('**/api/v1/eu', (route) =>
    route.fulfill({
      status: 401,
      ...JSON_HEADER,
      body: JSON.stringify({ message: 'Faça login para continuar.' }),
    }),
  )
}

async function autenticada(page: Page, signsInWith: string[] = ['google']) {
  await page.addInitScript(() => {
    localStorage.setItem('bora.session.token', 'tok-portao')
  })
  await page.route('**/api/v1/eu', (route) =>
    route.fulfill({
      status: 200,
      ...JSON_HEADER,
      body: JSON.stringify({
        data: {
          id: 1,
          name: 'Maria',
          email: 'maria@exemplo.com',
          email_verified: true,
          signs_in_with: signsInWith,
        },
      }),
    }),
  )
}

export const TELAS: Tela[] = [
  {
    // Ainda é o scaffold do create-next-app, em inglês. Entra no portão de
    // propósito: é a rota raiz do produto, alcançável por qualquer um.
    nome: 'início',
    rota: '/',
    preparar: anonima,
  },
  { nome: 'entrar', rota: '/entrar', preparar: anonima },
  { nome: 'criar conta', rota: '/criar-conta', preparar: anonima },
  { nome: 'esqueci minha senha', rota: '/esqueci-senha', preparar: anonima },
  { nome: 'criar nova senha', rota: '/redefinir-senha?token=abc', preparar: anonima },
  {
    nome: 'confirmar e-mail',
    rota: '/verificar-email?token=abc',
    preparar: async (page) => {
      await anonima(page)
      await page.route('**/api/v1/email/verificar', (route) =>
        route.fulfill({
          status: 200,
          ...JSON_HEADER,
          body: JSON.stringify({ message: 'E-mail confirmado. Obrigado!' }),
        }),
      )
    },
  },
  {
    nome: 'unir contas',
    rota: '/unir-contas',
    preparar: async (page) => {
      await anonima(page)
      await page.addInitScript(() => {
        sessionStorage.setItem(
          'bora.merge.pending',
          JSON.stringify({ token: 'tok-uniao', email: 'maria@exemplo.com' }),
        )
      })
    },
  },
  {
    // O caminho de sucesso redireciona e não para em lugar nenhum; o do link
    // vencido é o estado em que esta rota de fato vira tela.
    nome: 'confirmação da união (link vencido)',
    rota: '/unir-contas/confirmar?token=velho',
    preparar: async (page) => {
      await anonima(page)
      await page.route('**/api/v1/uniao-credenciais/link/confirmar', (route) =>
        route.fulfill({
          status: 410,
          ...JSON_HEADER,
          body: JSON.stringify({ message: 'Este link expirou ou já foi usado.' }),
        }),
      )
    },
  },
  {
    nome: 'definir senha',
    rota: '/definir-senha',
    preparar: (page) => autenticada(page),
  },
]
