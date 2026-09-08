# Specification Quality Checklist: Cadastro e Perfil de Estabelecimento

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-08
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs) — **com ressalva declarada**, ver Notes
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] **No [NEEDS CLARIFICATION] markers remain** — resolvidas com o Ícaro em 2026-09-08 (Q1 e Q2)
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification — **com a mesma ressalva**, ver Notes

## Notes

### A ressalva sobre "no implementation details"

Este item do checklist genérico **conflita com o template deste projeto**, e o conflito é
deliberado, não descuido.

O `spec-template.md` do Bora tem uma seção obrigatória própria — **"Tela e Experiência"** —
imposta pelos Princípios XI e XII da constituição, e ela **exige** detalhe que o checklist
genérico chamaria de implementação: comportamento a 360px, larguras de teste, ferramenta de
verificação de acessibilidade ("Verificação automatizada de acessibilidade:
[ferramenta/asserção]"), estados de carregando/vazio/erro/sucesso. O `spec-check` do projeto
**reprova** a spec que não tenha isso.

Onde a spec cita `axe`, larguras, fronteira servidor/cliente do ADR-0003 ou o `Button` de
32px, é porque a seção pede — ou porque a informação é **restrição herdada já decidida**
(ADR, `design-system.md`), não escolha de implementação sendo feita aqui.

Fora dessa seção, a spec se mantém em WHAT/WHY: os Functional Requirements não nomeiam
tecnologia, e os Success Criteria são todos observáveis pelo usuário.

**Julgamento, não fato:** considero o item cumprido no espírito. Quem discordar tem
argumento legítimo — por isso está escrito aqui em vez de silenciosamente marcado.

### As duas perguntas, resolvidas em 2026-09-08

Ambas afetavam **escopo**, não detalhe, e por isso não foram resolvidas por palpite (regra
do projeto: nunca inventar; ausente = PENDENTE + pergunta).

- **Q1 — criar o perfil já pede a reivindicação?** → **Sim, se a pessoa se identificar.** O
  formulário oferece "sou eu que gerencio este bar" e marcar isso abre o pedido junto
  (FR-019). A aprovação continua manual e continua na P2.
- **Q2 — quem identifica duplicata, e quando?** → **O sistema avisa na criação**, quando há
  local parecido no mesmo bairro (FR-020). Decidiu o argumento de que, pelo Princípio X,
  unificar é caro: não se apaga o repetido, costura-se histórico.

As duas **refinam regra de domínio** e foram escritas também em `docs/domain/locais.md`
(`RN-LOCAL-004` e `RN-LOCAL-005`) — spec referencia regra, não a substitui
(`development-workflow.md` §6).

### O `/spec-check` reprovou a primeira escrita — e o que mudou

Rodado em 2026-09-08 contra o template e contra o `ux-requirements.md`. **Veredito inicial:
NÃO**, com cinco bloqueantes. Registrado aqui porque o portão pegar a spec de quem o
executou é o sinal de que ele funciona — se tivesse passado de primeira, ele seria fraco.

| # | Bloqueante | Resolução |
|---|---|---|
| B1 | `RN` referenciada sem teste que a exercite — só a `RN-EVENTO-001` tinha | Nova seção **"Cenários de Teste"**, com uma linha por `RN` |
| B2 | Princípios I, V e VIII tocados sem teste que prove o bloqueio | Tabela de princípio → teste de bloqueio na mesma seção |
| B3 | A spec só declarava testes de tela; o Princípio IX exige backend **e** frontend | Lista de cenários de API acrescentada |
| B4 | A **recusa** de reivindicação não existia na spec | FR-021, com motivo, aviso e caminho humano (decisão do Ícaro) |
| B5 | Segundo pedido pendente para o mesmo local era indefinido | FR-022 e FR-023 (decisão do Ícaro) |

Avisos também fechados: FR-014 declarada explicitamente como **guarda, não funcionalidade**
(guarda não precisa de tela, e a mesma lógica já valia para a FR-013); `prefers-reduced-motion`
e contagem de toques acrescentados; e a exigência de confirmação para **ação de efeito
público** respondida **por escrito, com o argumento**, em vez de ignorada — julgamento
declarado, não fato.

**Veredito após as correções: SIM** — pronta para `/speckit-plan`, sujeita à sua aprovação
(quem aprova é o Ícaro, não o portão).
