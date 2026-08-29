# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere a [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

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
