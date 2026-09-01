# Bora — Convenção de nomenclatura

Status: **vinculante**. Adotada em 2026-08-31, aplicada retroativamente à spec 001 no mesmo
dia (nada havia ido para produção). Vale para toda spec daqui em diante.

## A regra, em uma linha

**Identificador é em inglês. Prosa é em português. A única exceção é o caminho da URL.**

## O que é identificador (→ inglês)

Tudo que o compilador, o banco ou o framework lê como nome:

| Onde | Exemplo |
|---|---|
| Classes, interfaces, traits, enums | `AccountController`, `EmailToken`, `IdentityProvider` |
| Arquivos e diretórios de código | `app/UseCases/Account/RegisterAccount.php`, `src/lib/session.ts` |
| Métodos, funções, propriedades, variáveis | `hasPassword()`, `$plainTextToken`, `useHydrated()` |
| Constantes e chaves de configuração | `EmailToken::EMAIL_VERIFICATION`, `bora.session.lifetime_days` |
| Variáveis de ambiente | `BORA_SESSION_LIFETIME_DAYS` |
| Tabelas, colunas e índices | `social_accounts`, `expires_at`, `last_seen_at` |
| **Campos do JSON** da API (entrada e saída) | `{ "name": ..., "password": ..., "expires_at": ... }` |
| Chaves de erro de validação | `errors: { password: [...] }` |
| Nomes de método de teste | `rejects_a_second_account_with_the_same_email` |
| Chaves de `localStorage` / `sessionStorage` | `bora.session.token`, `bora.merge.pending` |
| Nomes de evento de auditoria e de fila | `account_created`, `email-sending` |

## O que é prosa (→ português)

Tudo que uma **pessoa** lê:

| Onde | Exemplo |
|---|---|
| Comentários e docblocks | `/** A conta única do Bora (RN-PLAT-001). */` |
| Mensagens da API | `"E-mail ou senha não conferem."` |
| Textos de tela, rótulos, títulos | `label="Como você quer ser chamado"` |
| Descrições de teste em string | `it('mostra o erro no campo quando a API recusa')` |
| Rótulos de campo em mensagem de validação | `['password' => 'senha']` no `attributes()` |
| Toda a documentação (`docs/`, `specs/`) | este arquivo |

> **Por que o nome do teste em PHP é inglês e a descrição em Playwright/Vitest é
> português?** Porque em PHP o nome do teste é um **identificador**
> (`public function rejects_...`) e em JS é uma **string**. A regra é mecânica de
> propósito: quem for aplicá-la não precisa julgar.

## A exceção: o caminho da URL

Os caminhos das rotas ficam **em português**:

```text
POST   /api/v1/contas
POST   /api/v1/sessoes
DELETE /api/v1/sessoes/atual
POST   /api/v1/email/verificar/reenviar
POST   /api/v1/uniao-credenciais/link/confirmar
```

Vale igualmente para as rotas do `web/` (`/entrar`, `/criar-conta`, `/esqueci-senha`).

**Por quê.** O caminho é endereço: a pessoa lê na barra, copia, compartilha, e ele aparece
em busca. É produto, não código — e produto do Bora fala português. O corpo do JSON,
ninguém vê fora do código.

**O que NÃO é exceção:** o corpo e a query da requisição. `POST /sessoes` recebe
`{ "email": ..., "password": ... }`. Manter o corpo em português obrigaria uma tradução de
borda para sempre — `validated('senha')` alimentando `$password`, `'nome' => $this->name`
em cada Resource — e criaria a chance permanente de a chave do erro de validação
dessincronizar do nome do campo, o que faz a tela perder o destaque no campo certo, em
silêncio.

## Vocabulário do produto continua em português

Termos que **são** do produto e não têm equivalente — `rolezeiro`, `rolê` — ficam como
estão, inclusive como valor em banco (`bora.account.initial_role => 'rolezeiro'`). Traduzir
isso não é padronizar; é apagar a voz do produto. Ver `docs/product/vision.md`.

## Nomes de ação de controller

Ação de controller é método, então é inglês — inclusive quando a rota é portuguesa:

```php
Route::post('/senha/esqueci', [PasswordController::class, 'forgot']);
Route::post('/email/verificar/reenviar', [EmailVerificationController::class, 'resend']);
```

Quando o recurso encaixa no CRUD do Laravel, usar os nomes canônicos (`index`, `store`,
`show`, `update`, `destroy`) em vez de inventar sinônimo.

## Como isto é verificado

Não há checagem automática hoje — é revisão humana, apoiada por esta página. **Se a falta
de automação começar a doer**, o lugar de resolver é uma regra de lint (PHP CS Fixer /
ESLint), não uma checklist a mais na spec. Registrado como observação honesta, não como
promessa.
