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

### Emenda de 2026-09-01 — granularidade das issues de tarefa

**A convenção acima não foi cumprida na spec 001, e isso passou a ser deliberado.** As 119
tarefas nunca viraram issues; foram rastreadas no próprio `tasks.md` e no histórico do git.
Quando isso foi percebido (o quadro estava parado em 2026-08-29, com a spec já em 117/119),
o Ícaro decidiu **não** importar retroativamente: criar 119 issues já fechadas é cerimônia
sem informação nova.

**A regra que passa a valer:**

- Uma spec vira **uma issue por marco de trabalho real** — tipicamente uma por user story,
  mais as que sobram no fim (validação visual, fechamento) —, não uma por linha do
  `tasks.md`. O `tasks.md` continua sendo a lista fina; o Linear mostra o que está em jogo.
- **Exceção:** se uma spec for tocada por mais de uma pessoa ao mesmo tempo, aí sim vale
  issue por tarefa, porque o Linear passa a servir para dividir trabalho e não só para
  informar estado. Hoje é uma pessoa só.
- Issues de erro `E-NNN`: criar **apenas** quando o erro deixar trabalho pendente. E-005 a
  E-018 foram resolvidos na mesma sessão em que apareceram e vivem no `error-log.md` — não
  há o que rastrear.

**Por que escrito:** convenção que ninguém cumpre e ninguém revoga vira ruído — a próxima
pessoa não sabe se o quadro está incompleto ou se a regra mudou. Emendar de propósito é
melhor que fingir que se cumpre.

## Sincronização de 2026-09-01

O quadro estava **três dias defasado** (última atualização 2026-08-29, com todo o trabalho
das specs feito entre 30/08 e 01/09). Ajustado:

| issue | ação |
|---|---|
| BORA-30 (spec 001 — rodar `/specify`) | → **Done**, marco M1 |
| BORA-24 (confirmação ao unir credenciais) | → **Done**, marco M1, rótulo `decisao-pendente` removido — decidido pela D1 e implementado na US3 |
| BORA-26 (CORS/Sanctum/OpenAPI) | **mantida aberta**, com comentário do que já existe. O pedaço "padrão de documentação por feature" é julgamento do Ícaro |
| **BORA-34** | T118 — validação visual no celular (novo) |
| **BORA-35** | T119 — fechamento da spec 001 (novo) |
| **BORA-36** | envio real de e-mail: Resend, remetente, domínio (novo, M5) |
| **BORA-37** | alarme sobre `failed_jobs` (novo, M5) |
| **BORA-38** | emenda constitucional da D8 (novo, M0) |


## Sincronização de 2026-09-02 — fechamento da spec 001

| issue | ação |
|---|---|
| BORA-34 (T118 — validação visual) | → **Done**. Descrição reescrita com o que de fato aconteceu: percurso de conta validado **no celular**, Google e a faixa "Definir senha" **no computador**, e o defeito E-019 que a validação encontrou |
| BORA-35 (T119 — fechamento) | → **Done**. Descrição com os dois commits (`7f8a6ac`, `5a78aa4`) e o achado do `signs_in_with` fora do contrato |
| **BORA-39** | ratificar e commitar o `design-system.md` (novo, M2) — hoje sem rastreio no git |
| **BORA-40** | `spec-check`: cobrar o caminho até a tela e os campos do payload (novo, M2) — causa raiz do E-019 |
| **BORA-41** | "excluir minha conta": direito de eliminação da LGPD não existe (novo, M5, `decisao-pendente`) |
| **BORA-42** | `npm run lint` com 7 erros pré-existentes (novo, M2) |
| **BORA-43** | a home ainda é o scaffold do Next (novo, M3) |

**Marco M1 — Contas em 100%.**

**A T120 não virou issue, de propósito.** Ela nasceu e morreu dentro da T118, e a convenção
emendada em 2026-09-01 diz que uma spec vira issue **por marco de trabalho real**, não por
linha do `tasks.md` — criar issue já fechada é cerimônia sem informação nova. O registro
dela vive no `tasks.md`, no `E-019` e na descrição da BORA-34.

**O E-019 também não virou issue**, pela mesma convenção: erro só vira issue quando deixa
trabalho pendente. Ele está resolvido; o que ele deixou pendente é o portão, e isso é a
BORA-40.

## Estado

- [x] Projeto criado no Linear — **Bora** (lead Ícaro)
- [x] Time próprio criado — **Bora**/`BORA`, único time do projeto (2026-08-29)
- [x] Marcos criados — M0 a M5
- [x] Issues de decisão — BORA-1..BORA-29 (label `decisao-pendente`; uma por linha
      da tabela do backlog; gateway de pagamento — BORA-1 — sem marco por ser Fase 3)
- [x] Issues de setup — BORA-31 (repositório api/+web/), BORA-30 (Spec 001)
