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
| **BORA-44** | T120 — dar caminho visível à tela "Definir senha" (novo, M1, **Done**), relacionada à BORA-34 e à BORA-40 |
| BORA-9 (`RN-DESC-003` — salvar vs. seguir) | → **Done**, rótulo `decisao-pendente` removido. Decidido pelo Ícaro em 2026-09-02 na sessão paralela (commit `1a22661`): são **duas ações distintas**. O quadro estava atrasado em relação ao catálogo |
| **BORA-45** | cancelamento de evento notifica quem apenas **salvou**? (novo, M3, `decisao-pendente`) — pendência que **nasceu** da decisão da BORA-9: a `RN-EVENTO-004` diz "notifica quem salvou/segue", texto escrito antes da separação |

**Marco M1 — Contas em 100%.**

**Sobre a BORA-44, e vale registrar porque é correção de leitura minha.** Eu tinha deixado
a T120 sem issue, alegando a convenção emendada em 2026-09-01. O Ícaro pediu que fosse
criada — e ele está certo: a convenção diz "uma issue **por marco de trabalho real**, não
uma por linha do `tasks.md`", e a T120 **é** um marco de trabalho real (defeito próprio,
correção própria, commit próprio, testes próprios). O que a convenção recusa é issue por
linha de tarefa e importação retroativa em massa, não issue fechada para trabalho que de
fato aconteceu. **Apliquei a regra mais apertada do que ela é escrita.** A convenção não
mudou; a leitura dela é que estava errada.

**O E-019 continua sem issue**, e aí a convenção se aplica direto: erro só vira issue
quando deixa trabalho pendente. Ele está resolvido; o que ele deixou pendente é o portão,
e isso é a BORA-40.


## Conferência periódica do quadro inteiro (2026-09-02)

Primeira auditoria completa, a pedido do Ícaro, cruzando **três fontes**: as 47 issues do
quadro, a tabela "Decisões pendentes" do `backlog.md` e os marcadores `PENDENTE` reais nos
catálogos de `docs/domain/`.

**Resultado: 4 divergências, todas corrigidas na hora.** As três primeiras são linhas de
backlog que nunca viraram issue — a importação inicial cobriu BORA-1..29 e **nada garantiu
que linhas acrescentadas depois virassem issue**.

| divergência | correção |
|---|---|
| Túnel HTTPS estava no backlog desde 2026-08-31, sem issue | **BORA-46** (M0, `decisao-pendente`) |
| "Contagem real de casas com música ao vivo" sem issue | **BORA-47** (M5, `decisao-pendente`) |
| "Validar premissas do orçamento" sem issue | **BORA-48** (M5, `decisao-pendente`) |
| **Direção inversa:** BORA-26 aberta e rotulada `decisao-pendente`, **sem linha no backlog** — a linha saiu quando a D2 decidiu a metade do CORS, e a metade viva (padrão de documentação OpenAPI por feature) ficou sem rastro no repositório | issue renomeada para dizer só o que resta, com a metade decidida marcada como tal; linha correspondente devolvida à tabela do backlog |

**Achado adjacente, fora do quadro:** a `RN-EVENTO-004` (`docs/domain/eventos.md`) ainda diz
"notifica quem salvou/segue os envolvidos" — frase escrita **antes** de a `RN-DESC-003`
separar as duas ações. O PENDENTE da separação tinha sido anotado em `descoberta.md`, e a
regra afetada ficou sem aviso: quem lesse `eventos.md` sozinho leria uma promessa que o
produto talvez não vá cumprir. `PENDENTE` cruzado acrescentado ali.

**O que os catálogos NÃO revelaram:** todos os `PENDENTE` de `docs/domain/` têm issue
correspondente. A divergência estava só entre backlog e quadro, nunca entre catálogo e
quadro.

**Sobre a periodicidade.** O passo 7 do `doc-sync` (criado hoje) só age sobre regras que a
sessão **tocou** — não pega linha de backlog acrescentada sem issue, que foi exatamente o
buraco desta auditoria. Uma conferência completa como esta continua sendo necessária de
tempos em tempos; **quando, é chamada do Ícaro** — não há automação e não se inventa
cadência aqui.

## Estado

- [x] Projeto criado no Linear — **Bora** (lead Ícaro)
- [x] Time próprio criado — **Bora**/`BORA`, único time do projeto (2026-08-29)
- [x] Marcos criados — M0 a M5
- [x] Issues de decisão — BORA-1..BORA-29 (label `decisao-pendente`; uma por linha
      da tabela do backlog; gateway de pagamento — BORA-1 — sem marco por ser Fase 3)
- [x] Issues de setup — BORA-31 (repositório api/+web/), BORA-30 (Spec 001)

## Sincronização de 2026-09-03 — BORA-22 decidida

Decisão de verificação de propriedade do estabelecimento tomada pelo Ícaro e escrita em
`docs/domain/locais.md` (`RN-LOCAL-001` atualizada, **`RN-LOCAL-005`** criada) e em
`docs/domain/eventos.md` (`RN-EVENTO-001` ganhou o portão de publicação).

Passo 7 do `doc-sync` aplicado na íntegra:

| Ação | Issue |
|---|---|
| Fechada, rótulo `decisao-pendente` removido, descrição com a decisão e o porquê | **BORA-22** |
| Linha tirada da tabela "Decisões pendentes" do `backlog.md` | — |
| Criada para a pendência que a decisão gerou | **BORA-49** — método automático sucessor da aprovação manual (bloqueada pela BORA-6) |
| Criada para a pendência que a decisão gerou | **BORA-50** — desempate entre duas reivindicações do mesmo local |

As duas linhas novas entraram na tabela do `backlog.md` no lugar da que saiu — decidir
abriu duas perguntas, e é isso que o passo 7 existe para não deixar cair no chão.

**Efeito no caminho crítico:** a spec de cadastro de local passa de **três** decisões
bloqueantes para **duas** (BORA-21 categorias e BORA-20 redes/franquias). Nem a BORA-49 nem
a BORA-50 bloqueiam — a primeira é da Fase 2, a segunda é regra de arbitragem que pode
entrar depois do cadastro existir.
