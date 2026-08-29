# Bora — Contexto do projeto (Claude Code)

Carregado automaticamente em toda sessão. Manter curto e atual.

## Produto
**Bora** (nome oficial da solução; registro INPI PENDENTE — o codinome de trabalho "iBar"
foi aposentado em 2026-08-28, junto com o repositório) é uma **plataforma web de três
lados**: público (rolezeiros), estabelecimentos
(bares e restaurantes) e artistas/bandas, em torno de eventos de música ao vivo. Responde
"onde tem rolê hoje?". Lançamento como **site** (login Google ou cadastro próprio); o
**app mobile será lançado quando o site tiver boa aceitação**, consumindo a mesma API sem
mudança estrutural no backend. Este repositório abriga **API (`api/`) e frontend
(`web/`), desacoplados** — ver ADR-0002. Validação em **Juazeiro-BA e Petrolina-PE**;
cadastro é self-service e aberto.
Visão aprovada em `docs/product/vision.md` (2026-08-28) — leia antes de assumir escopo.
Modelo de negócio: **gratuito para o usuário final; Freemium B2B em fases** —
`docs/product/monetization.md`.

## Método — Spec Kit é a espinha dorsal (igual ao Nexa)
- Fonte única da spec: Spec Kit → `specs/NNN-feature/{spec,plan,tasks}.md`.
- Fluxo: constituição → `/speckit-specify` → `/spec-check` → `/speckit-plan` → `/speckit-tasks` → implementar → `/doc-sync`.
  O `/doc-sync` atualiza a doc e **commita**; o `git push` é sempre manual do Ícaro.
- Constituição ratificada e **vinculante**: `.specify/memory/constitution.md` (muda só por emenda).
- Skills complementares: `spec-check` (portão), `domain-rule`, `adr-new`, `screen-help`, `doc-sync`.
- Rastreio de trabalho no **Linear**: **time próprio "Bora"** (key `BORA` — as issues saem
  como `BORA-nn`), projeto "Bora". Estrutura em `docs/logs/linear-import.md`.
- Método completo: @docs/development-workflow.md

## Arquitetura
- **A constituição é a autoridade** (`.specify/memory/constitution.md`): conta única
  multi-papel, gratuidade do usuário final, conformidade de conteúdo de terceiros (LGPD +
  ToS — scraping proibido), API-first com **frontend desacoplado consumindo só a API
  pública** e **prontidão mobile**, segurança por padrão, assíncrono, auditoria de
  escrita, histórico preservado, **entrega vertical com validação visual (XI)**,
  **usabilidade universal (XII)** — stack ratificada (Laravel 13, MySQL, **banco único —
  sem multi-tenancy**, ADR-0001; front separado no mesmo repo, ADR-0002; **`web/` em
  Next.js + React + TypeScript**, ADR-0003 — catálogo público renderizado no servidor,
  área logada no cliente, zero regra de negócio no front).
- Complementos: **portas & adapters** (domínio nunca importa SDK externo); **Clean Code**
  (funções pequenas, SRP, erro explícito, núcleo testável sem framework).

## Entrega de feature (Princípio XI — inegociável)
Feature pronta = API completa e documentada **+ tela 100% no frontend** (conforme
`docs/product/ux-requirements.md`) **+ testes automatizados de back e front aprovados**
**+ validação visual do Ícaro**. Não abrir a próxima feature antes da validação da atual.
Nada é "pronto só no backend".

## Onde ficam as regras de negócio
- No código: entidades (invariantes) + casos de uso. Bordas não têm regra.
- Transversal: `docs/domain/<contexto>.md`, ID `RN-<CTX>-NNN`; specs referenciam o ID.
  Catálogos: `plataforma.md` (RN-PLAT), `locais.md` (RN-LOCAL), `artistas.md` (RN-ART),
  `eventos.md` (RN-EVENTO), `avaliacoes.md` (RN-AVAL), `descoberta.md` (RN-DESC),
  `divisao-conta.md` (RN-CONTA).
- Configurável: política no domínio, parâmetro como dado (nunca hardcoded).

## Testes e cenários de falha (Princípio IX)
- Nenhuma spec vai para implementação sem cenários de teste declarados — **incluindo
  caminhos de erro e limites**.
- Toda `RN-<CTX>-NNN` tem ao menos um teste; todo princípio NON-NEGOTIABLE tocado tem teste
  que **prove o bloqueio**.
- **Obrigação ativa:** cenário de falha previsível não coberto → **pare e avise antes de
  implementar**. O Ícaro decide; você não escolhe sozinho.

## Guardrails e acordo de trabalho (inegociável)
- **Nunca invente** regra, dado ou comportamento. Ausente = PENDENTE + pergunta. Regras de
  negócio se confirmam com o Ícaro.
- **Afirmação sobre comportamento exige abrir o arquivo que o executa.** Dizer que uma
  regra "é obrigatória", que uma skill "cobra X" ou que uma ferramenta "faz Y" só é
  permitido depois de ler o arquivo/rodar a checagem. Sem isso, a resposta é "não
  verifiquei" — nunca a versão otimista. (Origem: E-002.)
- **Previsão contrariada pelo resultado é dita em voz alta**, inclusive quando o desfecho
  foi bom. Relatar só o resultado favorável e omitir que a previsão falhou esconde do Ícaro
  que o modelo mental usado nas próximas decisões está errado.
- **Separar fato de julgamento** em documento que será relido meses depois (ADR, spec):
  o que foi medido/verificado, o que é opinião do assistente, o que falta medir.
- **Na dúvida, pare e pergunte antes de seguir.**
- **Sinceridade acima de agradar:** aponte risco/erro mesmo que contrarie; postura de mentor.
- **Local/dev nunca executa contra banco de produção.**
- **Toda alteração atualiza a documentação no mesmo commit.**

## Documentação
- Constituição: `.specify/memory/constitution.md`
- Specs: `specs/NNN-feature/`
- Método: `docs/development-workflow.md`
- Visão / negócio / marca: `docs/product/{vision,monetization,brand}.md`
- Regras (catálogo): `docs/domain/`
- ADRs: `docs/adr/`
- Backlog / erros / Linear: `docs/logs/`
- Telas originais do Figma: `docs/product/design/figma/`
