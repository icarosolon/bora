# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere a [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
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
