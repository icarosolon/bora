# Linear — estrutura do projeto (criada em 2026-08-28)

Decisão do Ícaro (2026-08-29, revendo a de 2026-08-28): o Bora tem **time próprio no
Linear** — workspace `icasst`, time `Bora`, key **`BORA`**. Motivo: no Linear o prefixo do
identificador vem do time, não do projeto; com o Bora dentro do time Nexa as issues saíam
como `NEX-nn` e não dava para saber de qual produto era a tarefa. **Importação executada em
2026-08-28** e **migrada de time em 2026-08-29** — projeto:
<https://linear.app/icasst/project/bora-f0ad76fe7e09>. Este arquivo registra a estrutura e
as convenções contínuas.

## Migração NEX → BORA (2026-08-29)

Trocar o time do projeto no Linear moveu as 31 issues automaticamente, preservando projeto,
marcos, prioridades, status e a label `decisao-pendente`. A numeração foi refeita pelo
Linear **em ordem inversa** à criação: `BORA-n` corresponde a `NEX-(40−n)` — ou seja,
`BORA-1` = NEX-39 (gateway de pagamento) e `BORA-31` = NEX-9 (setup do repositório). Os
identificadores antigos continuam resolvendo por redirect do Linear.

## Projeto

- **Nome:** Bora (nome oficial da solução desde 2026-08-28; o codinome iBar foi aposentado
  — o repositório passou a se chamar `bora` em 2026-08-28)
- **Descrição:** Plataforma web que conecta público, bares/restaurantes e artistas em
  torno de eventos de música ao vivo. Gratuito para o usuário final; Freemium B2B. API e
  frontend desacoplados no mesmo repositório; app mobile quando o site tiver boa
  aceitação. Validação em Juazeiro-BA e Petrolina-PE. Método Spec Kit — repositório é a
  fonte da verdade (`C:\wamp64\www\bora`). Entrega vertical: feature pronta = API
  documentada + tela + testes (back e front) + validação visual do Ícaro.
- **Marcos (milestones):**
  1. **M0 — Fundação** (constituição ratificada ✔, decisões de base do backlog, setup do
     repositório Laravel)
  2. **M1 — Contas** (spec 001: conta única multi-papel, login Google/e-mail)
  3. **M2 — Catálogo** (locais + artistas: cadastro, perfis públicos, categorias/gêneros)
  4. **M3 — Eventos** (criação pelo local, convite e confirmação do artista, feed do dia)
  5. **M4 — Descoberta** (busca, filtros, salvos, rotas, avaliações)
  6. **M5 — Lançamento assistido** (onboarding Juazeiro/Petrolina, divisão de conta,
     notificações)

## Issues iniciais

### Rotuladas `decisão` (uma issue por linha da tabela do backlog)
Criar uma issue por item de `docs/logs/backlog.md` → "Decisões pendentes que bloqueiam
spec", com título `Decisão: <item>`, descrição apontando a RN/doc de origem, e o marco
correspondente ao que ela bloqueia.

### De método/setup (marco M0)
- `Setup: repositório Laravel 13 conforme constituição (ADR-0001)`
- `Setup: autenticar conector Linear e validar fluxo tasks→issues`
- `Decisão: registro de marca Bora (INPI) + domínio` (prioridade alta)
- `Spec 001: contas e autenticação — rodar /specify` (primeira spec — ver backlog)

### Convenção contínua (igual ao Nexa)
- Cada spec aprovada gera issues a partir do `tasks.md` com título `T001: <descrição>`
  (deduplicar por ID `T\d{3}` antes de criar).
- Issues de erro referenciam `E-NNN` do error-log.

## Estado

- [x] Projeto criado no Linear — **Bora** (lead Ícaro)
- [x] Time próprio criado — **Bora**/`BORA`, único time do projeto (2026-08-29)
- [x] Marcos criados — M0 a M5
- [x] Issues de decisão — BORA-1..BORA-29 (label `decisao-pendente`; uma por linha
      da tabela do backlog; gateway de pagamento — BORA-1 — sem marco por ser Fase 3)
- [x] Issues de setup — BORA-31 (repositório api/+web/), BORA-30 (Spec 001)
