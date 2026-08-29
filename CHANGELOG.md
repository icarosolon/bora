# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere a [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Changed
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
- Projeto **Bora** criado no Linear (time Nexa) com marcos M0–M5, issues de setup
  (NEX-9, NEX-10) e issues `decisao-pendente` espelhando o backlog (NEX-11..NEX-39).
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
