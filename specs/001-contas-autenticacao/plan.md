# Implementation Plan: Fundação de Contas e Autenticação

**Branch**: `main` (o diretório da spec é `001-contas-autenticacao`; não foi criada branch
própria — ver nota em Estrutura) | **Date**: 2026-08-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/001-contas-autenticacao/spec.md`

## Summary

Entregar a fundação de identidade do Bora: **uma conta por pessoa** (RN-PLAT-001), com
entrada por **e-mail/senha ou Google** e **união das duas credenciais na mesma conta**
(RN-PLAT-002), mais verificação de e-mail que não bloqueia e recuperação de senha — API
`/api/v1/...` documentada e **cinco telas** no `web/`.

Abordagem técnica, decidida sobre estado verificado (ver [research.md](./research.md)):
API Laravel 13 com **Sanctum em modo token Bearer**, expiração de 30 dias **deslizante**
por middleware próprio (o Sanctum não tem isso nativo), **Socialite** para o Google
(exige downgrade do guzzle 8→7, verificado como seguro), papéis via
`spatie/laravel-permission`, auditoria via `spatie/laravel-activitylog`, OpenAPI via
**Scramble**. No `web/`, **shadcn/ui** sobre Tailwind 4 e a suíte de testes de front criada
do zero (**Vitest + Testing Library + axe + Playwright**), com os formulários como
componentes cliente e o token **nunca** cruzando a fronteira servidor→cliente.

## Technical Context

**Language/Version**: PHP **8.4.15** (`api/`) · TypeScript 5 / Node **24.19.0 LTS**
(`web/`) — verificado

**Primary Dependencies**:
- `api/`: Laravel **13.29.0**, Sanctum **4.3.3** (instalados); **a instalar** —
  `laravel/socialite` v5.30.1 (com `-W`), `spatie/laravel-permission` 8.3.0,
  `spatie/laravel-activitylog` 5.1.0, `dedoc/scramble` v0.13.42
- `web/`: Next **16.3.3**, React **19.2.8**, Tailwind **4** (instalados); **a instalar** —
  shadcn/ui (Radix), Vitest, Testing Library, axe, Playwright

**Storage**: MySQL 8.4.7, banco único `bora`, InnoDB forçado em `config/database.php`
(E-003 — não desfazer). Redis para cache/sessão/fila. Tabelas existentes reaproveitadas:
`users`, `password_reset_tokens`, `personal_access_tokens`

**Testing**: `api/` — **PHPUnit 12.5.34** (é o que está instalado; não é Pest).
`web/` — Vitest + React Testing Library + axe (componente) e Playwright (e2e, 360 e 1280)

**Target Platform**: navegador móvel como alvo principal (piso **360px**), navegador
desktop (1280) — servidor Linux em produção (hospedagem PENDENTE, BORA-27)

**Project Type**: web — dois apps desacoplados no mesmo repositório (`api/` + `web/`,
ADR-0002)

**Performance Goals**: SC-001 (conta criada em < 2 min), SC-002 (login Google em < 30 s),
`ux-requirements.md` (funcionar em aparelho modesto e rede 3G)

**Constraints**: sem rolagem horizontal a 360px; WCAG 2.1 AA sem violações no `axe`;
nenhuma regra de negócio no `web/`; e-mails enviados em fila, nunca no ciclo da request;
token fora de log, de URL e de prop de componente cliente

**Scale/Scope**: 5 telas, ~12 endpoints, 3 tabelas novas + 3 de pacote; validação em duas
cidades

## Constitution Check

*GATE: avaliado antes da Phase 0 e reavaliado após a Phase 1 (resultado ao final).*

| Princípio | Como este plano cumpre | Status |
|---|---|---|
| **I. Conta única multi-papel** (NN) | E-mail normalizado com índice único; união de credenciais em vez de conta paralela; papéis via `spatie/laravel-permission` sobre a mesma conta, `rolezeiro` no cadastro. Testes provam o bloqueio nos dois caminhos (cadastro e Google) | PASS |
| **II. Gratuidade do usuário final** (NN) | Nenhum endpoint desta feature toca cobrança; teste percorre os fluxos provando que não há paywall | PASS |
| **III. Conteúdo de terceiros / LGPD** (NN) | Google entra **só** via OAuth oficial (Socialite); armazenados apenas nome, e-mail e ID do provedor. Nenhum scraping, nenhum dado de terceiro exibido | PASS |
| **IV. API-First e contrato estável** | Tudo sob `/api/v1/...` (inclusive corrigindo `/api/user`, hoje fora do prefixo); envelope `data`/`message`/`errors`; sempre API Resource; datas ISO 8601; `web/` consome só a API pública com **a mesma autenticação do futuro app** (Bearer) | PASS |
| **V. Segurança e autorização por padrão** | FormRequest com `authorize()`/`rules()` e `validated()` em toda entrada; `#[Fillable]` explícito (sintaxe do Laravel 13 já usada no `User`); rate limit; resposta neutra na recuperação; senha/token fora de log; CORS com origens explícitas | PASS **com risco aceito** — ver Complexity Tracking |
| **VI. Assíncrono para tarefas pesadas** | Os três e-mails (verificação, união, redefinição) saem por Job na fila Redis, nunca no ciclo da request | PASS |
| **VII. Simplicidade e camadas claras** | Controllers finos, um recurso por controller; Socialite e Resend atrás de **portas & adapters** (o domínio não importa SDK); casos de uso concentram a regra | PASS |
| **VIII. Auditoria de escrita** | `activitylog` em criação de conta, união, senha definida/trocada e verificação — registrando o **evento**, nunca os valores sensíveis | PASS |
| **IX. Qualidade verificável** | Mapa regra→teste já na spec; caminhos de erro e limites cobertos; testes de back **e** front; `axe` sem violações | PASS |
| **X. Preservação do histórico** (NN) | **Não tocado** — exclusão/anonimização está fora de escopo (declarado na spec) | N/A |
| **XI. Entrega vertical com validação visual** (NN) | A feature só fecha com API documentada + 5 telas no `web/` + testes dos dois lados + validação visual do Ícaro | PASS |
| **XII. Usabilidade universal** | Telas conforme `ux-requirements.md`: mobile-first literal, 360px sem rolagem lateral, ação principal ao alcance do polegar, estados obrigatórios, alvos ≥ 44px, foco visível, nada por `hover` | PASS |
| **Stack obrigatório** | Laravel 13 + MySQL + Redis + Sanctum ✓; `spatie/laravel-permission` e `activitylog` ✓; **Socialite ✓ com ressalva** (downgrade do guzzle — ver Complexity Tracking); Next + React + TS com área logada no cliente ✓ | PASS **com ressalva** |

**Gates que não passaram**: nenhum bloqueante. Duas ressalvas justificadas em Complexity
Tracking; ambas foram decididas pelo Ícaro com o trade-off à vista, não pelo assistente.

## Project Structure

### Documentation (this feature)

```text
specs/001-contas-autenticacao/
├── plan.md              # este arquivo
├── spec.md              # spec aprovada (portão spec-check: SIM)
├── research.md          # Phase 0 — estado verificado + decisões técnicas
├── data-model.md        # Phase 1 — entidades, invariantes, migrações
├── quickstart.md        # Phase 1 — como validar a feature ponta a ponta
├── contracts/
│   └── auth-api.md      # Phase 1 — contrato dos endpoints /api/v1
├── checklists/
│   └── requirements.md  # checklist de qualidade da spec
└── tasks.md             # Phase 2 — NÃO criado por /speckit-plan
```

### Source Code (repository root)

```text
api/
├── app/
│   ├── Domain/Account/                  # núcleo testável, sem framework (Princípio VII)
│   │   ├── Email.php                    # value object: normalização + validação
│   │   ├── PoliticaDeSessao.php         # prazo como parâmetro, não hardcoded
│   │   └── PoliticaDeSenha.php          # comprimento mínimo configurável
│   ├── UseCases/Account/                # casos de uso — onde a regra vive
│   │   ├── RegistrarConta.php
│   │   ├── AutenticarPorSenha.php
│   │   ├── AutenticarPorGoogle.php      # decide: entra, cria ou exige união
│   │   ├── UnirCredenciais.php          # RN-PLAT-002 / D1
│   │   ├── DefinirSenha.php             # conta nascida no Google (sessão ativa)
│   │   ├── SolicitarRedefinicaoDeSenha.php
│   │   ├── RedefinirSenha.php
│   │   └── VerificarEmail.php
│   ├── Ports/                           # portas — o domínio não importa SDK
│   │   ├── ProvedorDeIdentidade.php     # implementada por adapter do Socialite
│   │   └── EnviadorDeEmail.php          # implementada por adapter do Resend
│   ├── Adapters/
│   │   ├── Socialite/GoogleIdentidade.php
│   │   └── Email/                       # Resend em produção, log em dev
│   ├── Http/
│   │   ├── Controllers/Api/V1/Auth/     # controllers finos, um recurso cada
│   │   ├── Requests/Auth/               # FormRequest: authorize() + rules()
│   │   ├── Resources/                   # nunca Model serializado direto
│   │   └── Middleware/
│   │       └── RenovarExpiracaoDoToken.php   # janela deslizante (research §1)
│   ├── Jobs/                            # e-mails na fila (Princípio VI)
│   └── Models/{User,ContaSocial,TokenDeEmail}.php
├── database/migrations/                 # users (colunas novas), contas_sociais,
│                                        # tokens_de_email + tabelas dos pacotes
├── routes/api.php                       # tudo sob v1 (corrige /api/user)
├── config/{cors.php,sanctum.php,services.php,scramble.php}
└── tests/
    ├── Feature/Auth/                    # endpoints, incl. caminhos de erro
    └── Unit/Domain/                      # núcleo sem framework

web/
├── src/
│   ├── app/
│   │   ├── entrar/page.tsx
│   │   ├── criar-conta/page.tsx
│   │   ├── unir-contas/page.tsx
│   │   ├── esqueci-senha/page.tsx
│   │   └── redefinir-senha/page.tsx
│   ├── components/
│   │   ├── ui/                          # shadcn/ui — primitivas compartilhadas
│   │   └── auth/                        # formulários (componentes cliente)
│   └── lib/
│       ├── api.ts                       # cliente da API pública
│       └── sessao.ts                    # guarda do token (localStorage) — só no cliente
└── tests/
    ├── unit/                            # Vitest + Testing Library + axe
    └── e2e/                             # Playwright — 360 e 1280
```

**Structure Decision**: mantém a divisão `api/` + `web/` já existente (ADR-0002/0003). O
`api/` ganha `Domain/`, `UseCases/`, `Ports/` e `Adapters/` — as camadas que o Princípio VII
exige e que hoje não existem (o projeto está no esqueleto do Laravel). O `web/` ganha
`components/` e `tests/`, hoje inexistentes; os primitivos nascem em `components/ui`
compartilhados, como o backlog já recomendou para não criar um segundo design system.

**Nota sobre branch**: o Spec Kit reportou `BRANCH: 001-contas-autenticacao`, mas isso vem
do diretório da spec — **o repositório está em `main`** e nenhuma branch foi criada (não há
hook de git configurado; `.specify/extensions.yml` não existe). Criar branch é decisão do
Ícaro, não deste plano.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| **Token Bearer em `localStorage`**, contra a recomendação explícita da doc do Sanctum instalado ("You should not use API tokens to authenticate your own first-party SPA") | O Princípio IV exige que o site use **a mesma autenticação do futuro app mobile**; o modo SPA (cookie stateful) não serve para app. O ADR-0003 já ratificou "área logada no cliente, chamando a API com token". Decisão reconfirmada pelo Ícaro em 2026-08-30, com o risco de XSS à vista | **Modo SPA do Sanctum** (cookie `httpOnly`): mais seguro contra XSS, mas cria dois mecanismos de autenticação para a mesma API, exige site e API no mesmo domínio-raiz (hospedagem ainda indefinida) e reabre a armadilha SSR+sessão que o ADR-0003 evitou. **Cookie `httpOnly` via BFF no Next**: fecharia o XSS, mas transforma o Next em proxy da área logada, tensionando "proibido endpoint privado só do site". Mitigações obrigatórias em research.md §3 |
| **Downgrade de `guzzlehttp/guzzle` 8.1.0 → 7.15.5** | Único caminho para instalar `laravel/socialite`, que a **constituição obriga** no Stack. Verificado que nada no projeto exige guzzle 8: framework aceita `^7.8.2\|\|^8.0`, boost aceita `^7.9\|^8.0`, flysystem só conflita com `<7.0` | **OAuth do Google na mão** (sem Socialite): manteria guzzle 8, mas contraria o Stack ratificado (exigiria emenda) e amplia a superfície de erro de segurança. **Esperar o Socialite suportar guzzle 8**: bloquearia a US2 por prazo indeterminado. Reversível: quando o suporte sair, um `composer update` volta |

## Phases

- **Phase 0 — Pesquisa**: concluída → [research.md](./research.md). Nenhum
  `NEEDS CLARIFICATION` restou: os pontos abertos foram decididos pelo Ícaro (guarda do
  token) ou resolvidos por verificação (expiração deslizante, guzzle, CORS).
- **Phase 1 — Design e contratos**: concluída → [data-model.md](./data-model.md),
  [contracts/auth-api.md](./contracts/auth-api.md), [quickstart.md](./quickstart.md).
- **Phase 2 — Tarefas**: **não** é deste comando. Roda com `/speckit-tasks`.

### Constitution Check — reavaliação pós-Phase 1

Reavaliado após escrever modelo e contratos: **nenhum gate novo violado**. Dois pontos que
o design confirmou e que os testes precisam cobrir:

1. O envelope de resposta e o uso de API Resource valem **inclusive nos erros** (422 de
   validação e 429 de rate limit) — está no contrato.
2. A auditoria (Princípio VIII) registra **evento**, nunca valor sensível — está no modelo
   de dados, e a spec já previu o teste de "senha e token fora de log".
