# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere a [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
- **Plano técnico da spec 001 gerado** (`/speckit-plan`, 2026-08-30):
  `specs/001-contas-autenticacao/{plan,research,data-model,quickstart}.md` e
  `contracts/auth-api.md`. A spec passou a **Aprovada** (Ícaro, 2026-08-30). O plano foi
  escrito sobre o **estado verificado** da instalação (Boost + `composer --dry-run`), não
  sobre suposição, e isso mudou três coisas:
  - **`laravel/socialite` não instala neste projeto sem `-W`.** Todas as versões até a
    v5.30.1 exigem `guzzle ^6|^7` e o projeto está em **guzzle 8.1.0** (transitivo do
    Laravel 13). Decisão: aceitar o downgrade para guzzle 7.15.5 — verificado que **nada
    exige guzzle 8** (framework aceita `^7.8.2||^8.0`, boost `^7.9|^8.0`, flysystem só
    conflita com `<7.0`). A constituição obriga Socialite no Stack; a alternativa exigiria
    emenda. Reversível. Bônus: sem `-W` a resolução cai numa faixa do `firebase/php-jwt`
    sob advisory de segurança; com `-W` trava a v7.1.0, limpa.
  - **O Sanctum não tem expiração deslizante.** A `'expiration'` é prazo absoluto desde a
    criação, e a D7 pediu 30 dias **de inatividade**. O plano implementa o deslizamento com
    middleware próprio sobre `expires_at`, mantendo `'expiration' => null`.
  - **Ressalva sobre a D2 registrada.** A doc oficial do Sanctum instalado desaconselha
    token de API para SPA de primeira parte. Ícaro **reconfirmou** a D2 com o risco à vista
    e definiu a guarda do token (`localStorage`); as razões do Bora (Princípio IV e
    ADR-0003) e as mitigações obrigatórias ficaram escritas em `research.md` §3 e no
    Complexity Tracking do `plan.md`.
  Também levantado: três pacotes exigidos pela constituição **não estão instalados**
  (`socialite`, `spatie/laravel-permission`, `spatie/laravel-activitylog`), o `web/` **não
  tem nenhuma ferramenta de teste**, e a rota `/api/user` está **fora do versionamento**
  `/api/v1` — o plano corrige as três coisas.
- **Spec 001 — Fundação de Contas e Autenticação** escrita e **aprovada no portão
  `spec-check`** (2026-08-29): `specs/001-contas-autenticacao/spec.md`. Cobre RN-PLAT-001
  (conta única multi-papel) e RN-PLAT-002 (login Google/e-mail, união de credenciais),
  com as telas Entrar, Criar conta, Unir contas, Esqueci minha senha e Redefinir senha,
  seção "Tela e Experiência" completa (360px, polegar, estados, acessibilidade, testes em
  360 e 1280 com axe) e mapa de testes por regra e princípio — incluindo os que provam os
  bloqueios dos Princípios I (nenhuma conta paralela) e II (nada atrás de pagamento).
  As **oito decisões que a bloqueavam** foram tomadas pelo Ícaro na sessão e registradas
  na spec (D1–D8): união confirmada por senha com link como plano B; token Bearer + CORS
  de origens explícitas; OpenAPI via Scramble; shadcn/ui + Vitest/Testing Library/axe +
  Playwright; verificação de e-mail sem bloquear; recuperação de senha no escopo; sessão
  de 30 dias renovada no uso; Resend como e-mail transacional (emenda constitucional
  pendente para formalizar — ver backlog). Catálogo `plataforma.md` (RN-PLAT-002 sem
  PENDENTE), ADR-0002 e ADR-0003 (action items fechados) e backlog sincronizados no mesmo
  commit.

### Fixed
- **Tasks do `web/` falhavam com `UnauthorizedAccess`** (E-006): task `shell` no Windows
  roda em PowerShell e o `npm` do PATH resolve para `npm.ps1`, bloqueado porque a
  ExecutionPolicy da máquina é `Restricted` (LocalMachine — verificado). Corrigido com
  `npm.cmd` (batch, não passa pela ExecutionPolicy) nas tasks `web: dev` e `web: build`,
  em vez de afrouxar a ExecutionPolicy global. Correção feita por outro agente a pedido do
  Ícaro; registrada no error-log nesta sessão, quando se descobriu que o comentário do
  `tasks.json` apontava para `E-005` — número já ocupado. Ponteiro corrigido para `E-006`.
- **Achado de ambiente registrado** (E-007): dentro de sessão aberta **antes** da correção
  do PATH (E-004), `php` e `composer` continuam falhando, porque processo herda o ambiente
  de quando nasceu. O E-004 está resolvido — conferido no registro da máquina. Contorno
  para sessão em andamento documentado no error-log e no `quickstart.md` da spec 001.
- **Doc do método apontava comandos inexistentes** (E-005): `CLAUDE.md` e
  `development-workflow.md` diziam `/specify`, `/plan` e `/tasks`, mas as skills
  instaladas pelo Spec Kit 0.15.1 são `speckit-specify`, `speckit-plan` e `speckit-tasks`
  (não há `.claude/commands/`). Corrigido para os nomes reais; `/spec-check` e
  `/doc-sync` já estavam certos.

### Added
- **Spike descartável de frontend concluído** (BORA-32, 2026-08-29). No `api/`,
  `php artisan install:api` (Sanctum 4.3.3, `routes/api.php`, migration
  `personal_access_tokens`) e o endpoint **andaime** `GET /api/v1/eventos` com 3 eventos
  fixos, sem banco e sem auth (`app/Http/Controllers/Spike/EventoSpikeController.php`).
  No `web/`, a página `/eventos` renderizada no servidor, com um componente cliente só
  para provar CORS. **Código descartável, fora da Definition of Done** (Princípio XI):
  não é feature, não tem teste e **não define padrão de tela** — isso é da spec 001.
  As quatro perguntas do spike foram respondidas com evidência (comando + saída no
  comentário de fechamento da BORA-32):
  1. **SSR confirmado** — os três nomes aparecem no HTML cru de
     `curl -s http://localhost:3000/eventos`. O `await fetch` fica direto no componente de
     página, **sem `<Suspense>`**: os docs do Next 16 instalado dizem que `fetch` não é
     cacheado por padrão e bloqueia a renderização, e Suspense mandaria o conteúdo por
     streaming, fora do HTML inicial.
  2. **CORS do navegador OK sem configurar nada** — `Access-Control-Allow-Origin: *` vem
     do default do framework (`paths => ["api/*"]`), com `HandleCors` já no stack global.
     **Isso não fecha** o item de backlog "Setup de CORS/Sanctum SPA": `allowed_origins: *`
     não convive com `supports_credentials: true`, que o Sanctum vai exigir na spec 001.
  3. **360px sem rolagem horizontal** (`scrollWidth == clientWidth == 360`, nenhum elemento
     estourando); idem a 1280.
  4. **Fronteira servidor/cliente entendida** — **o Next fica**; não se aciona a
     alternativa React Router v7 prevista no ADR-0003.
- `web/src/app/layout.tsx`: `lang="en"` → `lang="pt-BR"`, para o leitor de tela anunciar o
  idioma certo (`ux-requirements.md`, acessibilidade técnica).
- **Projetos `api/` e `web/` criados** (BORA-31, 2026-08-29). `api/`: Laravel 13.29.0 sobre
  PHP 8.4.15 do WAMP, MySQL 8.4.7 (base `bora`), migrações rodadas, tabelas em InnoDB.
  `web/`: Next 16.3.3, React 19.2.8, TypeScript 5, Tailwind 4, App Router com `src/` e alias
  `@/*` — `npm run build` verificado. Node atualizado de 18.18.0 para **24.19.0 LTS**
  (Next 16 exige ≥ 20.9.0). `artisan serve` verificado respondendo 200.
- **Redis ativo em desenvolvimento** (2026-08-29): servidor Redis 8.2.5 em
  `127.0.0.1:6379`; extensão `phpredis` 6.3.0 instalada no PHP 8.4 do WAMP (que não a
  trazia) e habilitada nos dois `php.ini`; `CACHE_STORE`, `SESSION_DRIVER` e
  `QUEUE_CONNECTION` passam de `database` para `redis`. Fecha a pendência aberta no setup e
  alinha o ambiente ao Stack da constituição. Verificado pela facade `Cache`.
- `.vscode/tasks.json` — task de build padrão **`Bora: dev`** sobe `api/` (8000) e `web/`
  (3000) e abre o navegador em `localhost:3000` só depois do Next sinalizar `Ready`; mais
  `api: migrar banco` e `web: build`. Os padrões de detecção vieram da saída real dos dois
  servidores, não de suposição.
- `laravel/boost` v2.7.0 em `require-dev` + `.mcp.json` — **apenas o servidor MCP**
  (`--mcp`), sem diretrizes e sem skills, para dar acesso verificável ao estado da aplicação
  (esquema, config, log, docs da versão instalada) sem importar orientações que conflitam
  com o método. Servidor testado respondendo ao `initialize` do protocolo MCP. A autoridade
  segue sendo `CLAUDE.md` da raiz + constituição.

### Fixed
- **`artisan install:api` revertia a instalação do Sanctum** (E-004): o `composer.bat` do
  Windows roda `php composer.phar`, e o único `php` no PATH da máquina era o do XAMPP
  8.2.4, que não satisfaz o `"php": "^8.3"` do projeto. Ícaro trocou a entrada do PATH de
  `C:\xampp\php` para `C:\wamp64\bin\php\php8.4.15`. Desinstalar o XAMPP foi avaliado e
  descartado — ver E-004.
- **Migrações quebravam por MyISAM** (E-003): o MySQL do WAMP tem
  `default_storage_engine = MyISAM`, que limita índice a 1000 bytes e não tem transação nem
  chave estrangeira. Corrigido com `'engine' => 'InnoDB'` em `api/config/database.php` — no
  projeto, não no servidor, para não afetar o outro sistema da mesma máquina.

### Added
- **ADR-0003 — framework do frontend: Next.js (App Router) + React + TypeScript**
  (2026-08-29, decisão BORA-28). Critérios que decidiram: SEO do catálogo público e
  acessibilidade, ambos eliminatórios. Regras vinculantes: catálogo público renderizado no
  servidor, área logada renderizada no cliente (evita SSR + sessão do Sanctum) e nenhuma
  regra de negócio no `web/`. Descartados: Astro + ilhas React, React Router v7, Nuxt/Vue,
  SPA pura e Flutter Web (SEO), Blade/Livewire/Inertia (Princípio IV).
- Spike descartável de frontend no M0 (BORA-32) antes da spec 001, para absorver a curva de
  Next/React/CORS fora da Definition of Done.
- Decisão da tecnologia do app mobile (Flutter, React Native ou PWA) registrada no backlog
  como **deliberadamente adiada para a Fase 3** (BORA-33). O Princípio IV mantém as três
  portas abertas sem custo; a escolha da web não dependeu dessa e não a antecipa.
- **Landing page registrada como entregável futuro** (backlog, 2026-08-29). Decisão do
  Ícaro: não se constrói agora — entra no **lançamento do MVP**, alinhada com o que
  estiver documentado como produção *naquele momento*. Ficam registrados o gatilho, os
  bloqueios (marca/INPI para material público, identidade visual PENDENTE, setup do `web/`
  que é action item da spec 001) e a recomendação de modularização (seções como
  componentes + copy em módulo tipado; sem CMS/blocos configuráveis; primitivos
  compartilhados em `web/src/components/ui`). Escopo de uma eventual página de
  pré-lançamento fica em aberto, a informar pelo Ícaro.

### Fixed
- **O portão `/spec-check` não fazia o que a documentação dizia que ele fazia.** A skill não
  mencionava `ux-requirements.md`, tela, acessibilidade ou mobile, e o
  `spec-template.md` era o padrão de fábrica do Spec Kit — sem nenhuma seção de tela (e com
  um exemplo de premissa "Mobile support is out of scope for v1", incompatível com os
  Princípios XI e XII). Na prática, nada obrigava a spec a especificar a tela. Corrigido nos
  três pontos: o template ganha a seção obrigatória **"Tela e Experiência"**, a skill
  `spec-check` ganha critérios Bloqueantes explícitos (tela declarada, comportamento a
  360px, polegar, estados, acessibilidade, testes em 360 e 1280) e o
  `development-workflow.md` passa a descrever o portão que existe de fato.

### Changed
- `ux-requirements.md` ganha a seção **"Dispositivo principal: o celular"** (2026-08-29):
  mobile-first deixa de ser adjetivo e vira requisito verificável — estilo base do celular,
  piso de 360px sem rolagem horizontal, ação principal ao alcance do polegar, nada
  dependente de `hover`, uma coluna no celular, e teste de tela em duas larguras (360 e
  1280). Vale inclusive para o painel do estabelecimento. `vision.md` alinhado.
- Constituição 1.1.1 → **1.2.0** (Emenda 2, 2026-08-29): o Stack Tecnológico Obrigatório
  deixa de ter o framework de frontend como PENDENTE e passa a exigir Next.js + React +
  TypeScript com as três regras acima. Nenhum princípio adicionado, redefinido ou removido.
  `CLAUDE.md`, `development-workflow.md`, `backlog.md`, ADR-0001 e ADR-0002 sincronizados.
- **Linear: o Bora passou a ter time próprio** — time `Bora`, key `BORA` (2026-08-29),
  revendo a decisão de usar o time do Nexa. No Linear o prefixo do identificador vem do
  time, não do projeto: dentro do time Nexa as issues saíam como `NEX-nn` e não
  identificavam o produto. As 31 issues migraram sem perda (projeto, marcos M0–M5,
  prioridades, status e label `decisao-pendente` preservados); a renumeração ficou
  invertida — `BORA-n` = `NEX-(40−n)`. `CLAUDE.md`, `development-workflow.md`,
  `backlog.md` e `linear-import.md` atualizados.
- **Repositório renomeado de `ibar` para `bora`** (`C:\wamp64\www\bora`), aposentando o
  codinome de trabalho. Referências atualizadas em `CLAUDE.md`, `vision.md`, `brand.md`,
  `development-workflow.md`, `linear-import.md` e ADR-0001; constituição 1.1.0 → **1.1.1**
  (PATCH, só redação do Escopo). O projeto no Linear já se chamava "Bora".
- Constituição 1.0.0 → **1.1.0** (Emenda 1, 2026-08-28): novos Princípios XI (Entrega
  Vertical com Validação Visual — feature pronta = API documentada + tela 100% no front +
  testes back/front aprovados + validação visual do Ícaro antes da próxima feature) e XII
  (Usabilidade Universal — design interativo, simples e acessível a todos os perfis,
  incluindo idosos); Princípio IV expandido (frontend desacoplado consumindo só a API
  pública; prontidão mobile — app quando o site tiver boa aceitação); Princípio IX
  expandido (testes automatizados também no frontend).
- **Bora** promovido de nome de trabalho a **nome oficial da solução** (registro INPI
  segue no backlog); iBar permanece só como codinome de repositório. `vision.md`,
  `brand.md`, `CLAUDE.md`, `development-workflow.md` e `linear-import.md` sincronizados.
- Design do Figma original oficialmente aposentado — substituído pelos requisitos de
  `docs/product/ux-requirements.md`.
- `development-workflow.md`: ciclo ganha o passo de validação visual e a Definition of
  Done da feature; estrutura passa a prever `api/` e `web/`.

### Added
- `docs/product/investment-plan.md` — orçamento de 18 meses (R$ 350 mil), cronograma
  M0–M5 em 12 meses, TAM/SAM/SOM, dados de campo das duas cidades (IBGE 2025, guia
  comercial) e a lista de premissas ainda não validadas. Base do pitch para anjo.
- Projeto **Bora** criado no Linear com marcos M0–M5, issues de setup e issues
  `decisao-pendente` espelhando o backlog (31 issues; hoje `BORA-1..BORA-31` — ver a
  migração de time em *Changed*).
- ADR-0002 — frontend desacoplado no mesmo repositório (`api/` + `web/`), comunicação
  exclusivamente via API pública.
- `docs/product/ux-requirements.md` — requisitos vinculantes de UX e acessibilidade
  (WCAG 2.1 AA, alvos de toque, linguagem simples, foco em idosos).
- Constituição 1.0.0 ratificada: Princípios I–X (conta única multi-papel, gratuidade do
  usuário final, conformidade de conteúdo de terceiros, API-first, segurança, assíncrono,
  simplicidade, auditoria, qualidade verificável, preservação de histórico) e stack
  (Laravel 13/MySQL/Redis, banco único).
- ADR-0001 — reuso da stack do Nexa sem multi-tenancy.
- Visão de produto aprovada (`docs/product/vision.md`): plataforma de três lados, ideia
  extraída do Figma "App Rolezeiros" + anotações; fases MVP → monetização → bilheteria.
- Modelo de negócio (`docs/product/monetization.md`): Freemium B2B em fases, gratuito
  para o usuário final.
- Estudo de marca (`docs/product/brand.md`): nome de trabalho **Bora** (registro
  PENDENTE), análise da paleta (contrastes medidos, recomendação dark-first) e avaliação
  da logo (conceito pin+nota mantido; execução a modernizar).
- Catálogo de regras: `plataforma.md` (RN-PLAT-001..006), `locais.md` (RN-LOCAL-001..004),
  `artistas.md` (RN-ART-001..003), `eventos.md` (RN-EVENTO-001..004), `avaliacoes.md`
  (RN-AVAL-001..004), `descoberta.md` (RN-DESC-001..006), `divisao-conta.md`
  (RN-CONTA-001).
- Método de desenvolvimento espelhado do Nexa (Spec Kit + skills `spec-check`,
  `domain-rule`, `adr-new`, `screen-help`, `doc-sync`) e rastreio no Linear
  (`docs/logs/linear-import.md` — aguardando autenticação do conector).
- Backlog com as decisões pendentes que bloqueiam spec; rascunho de modelo de dados.
- Telas originais do Figma preservadas em `docs/product/design/figma/`.
