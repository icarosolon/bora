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

## Sincronização de 2026-09-03 — BORA-20 fechada, BORA-21 pela metade

| Ação | Issue |
|---|---|
| Fechada, rótulo removido, descrição com a decisão e o porquê | **BORA-20** (redes/franquias) |
| Linha tirada da tabela "Decisões pendentes" do `backlog.md` | — |
| Criada para a pendência que a conversa gerou | **BORA-51** — limite de categorias por local (já se sabe que tem de ser ≥ 2) |
| **Mantida aberta**, com o andamento registrado na descrição | **BORA-21** — a lista de categorias não fechou |

A BORA-21 é caso de **decisão parcial**: o Ícaro decidiu que choperia e petiscaria são
"bar" e confirmou que sobreposição é normal, mas a lista não foi fechada. Registrar o
andamento na issue e no catálogo — em vez de esperar a decisão inteira — é o que impede a
parte já decidida de se perder entre sessões.

**Efeito no caminho crítico:** a spec de cadastro de local passa de **duas** decisões
bloqueantes para **uma** (BORA-21). Nem a BORA-51 nem as BORA-49/50 bloqueiam.

## Sincronização de 2026-09-05 — BORA-21 e BORA-51 fechadas

| Ação | Issue |
|---|---|
| Fechada, rótulo removido, descrição com a lista e o critério | **BORA-21** (categorias de local) |
| Fechada, rótulo removido — **deixou de existir** quando a lista encolheu para três | **BORA-51** (limite de categorias) |
| Duas linhas tiradas da tabela "Decisões pendentes" do `backlog.md` | — |
| Nenhuma issue nova | a decisão não gerou pendência |

Primeira vez em que o passo 7 **fecha** uma issue sem criar outra: decidir nem sempre abre
pergunta nova. A BORA-51 é o caso interessante — foi criada em 2026-09-03 por uma previsão
do assistente (gestor marcaria tudo e degradaria o filtro) e fechada dois dias depois sem
virar regra, porque a lista curta eliminou o problema em vez de resolvê-lo.

**Efeito no caminho crítico:** a spec de cadastro/perfil de local passa de **uma** decisão
bloqueante para **zero**. As três (BORA-22, BORA-20, BORA-21) caíram entre 03 e 05 de
setembro. A spec está liberada para `/speckit-specify`.

## Sincronização de 2026-09-06 — estados visíveis do perfil

Regra alterada nesta sessão: **`RN-LOCAL-005`** — o "perfil magro" saiu de PROPOSTA para
regra, e entraram os dois **estados visíveis** (selo "Perfil do estabelecimento" no
reivindicado; linha explicativa no não reivindicado).

Passo 7 do `doc-sync` conferido:

| Verificação | Resultado |
|---|---|
| Issue da regra alterada | **BORA-22** já estava fechada em 2026-09-03; a alteração de hoje refina a regra que ela decidiu, não reabre a decisão |
| Rótulo `decisao-pendente` pendurado | nenhum |
| Linha na tabela "Decisões pendentes" | nenhuma a tirar |
| Issue nova | **nenhuma** — a decisão não abriu pergunta; abriu um *gatilho de revisão* |

**Gatilho de revisão anotado na BORA-49**, em vez de virar issue própria: quando o método
de verificação endurecer, revisar se o rótulo passa a comportar a palavra "verificado". É
dependência de uma issue existente, não trabalho novo — criar issue para isso seria
cerimônia sem informação nova.

Duplicata corrigida no `backlog.md`: a BORA-22 aparecia duas vezes no "Próximo passo",
escrita por duas sessões diferentes. Ficou a versão mais completa.

## Sincronização de 2026-09-08 — spec 002 escrita

Regras refinadas nesta sessão: **`RN-LOCAL-004`** (quem identifica duplicata e quando) e
**`RN-LOCAL-005`** (criar pode abrir o pedido; recusa com motivo e aviso; pedidos
concorrentes).

| Ação | Issue |
|---|---|
| Criada e fechada, seguindo o padrão da BORA-30 (uma issue por spec escrita) | **BORA-52** — spec 002 |
| **Escopo reduzido**, não fechada: o comportamento mínimo saiu para a spec; resta só o critério de arbitragem | **BORA-50** |
| Já fechadas, refinadas sem reabrir | BORA-22, BORA-20, BORA-21 |
| Rótulo `decisao-pendente` pendurado | nenhum novo |

**Caso novo para o passo 7:** uma decisão pode **encolher** uma issue em vez de fechá-la ou
de criar outra. A BORA-50 nasceu como "desempate entre duas reivindicações"; a spec 002
definiu o comportamento mínimo (os dois pedidos ficam na fila, aprovar um encerra o outro
com aviso) e sobrou só a pergunta de **arbitragem**. Reescrever o corpo da issue — separando
"já resolvido" de "o que falta" — evita que alguém releia daqui a meses e refaça o que já
está especificado.

## Sincronização de 2026-09-09 — revisão da spec 002

Regra refinada: **`RN-LOCAL-005`** ganhou a **evidência do pedido** (nome, função, melhor
horário, a quem perguntar; verificação pelo telefone do perfil).

| Ação | Issue |
|---|---|
| Descrição atualizada com a revisão e a nova ordem das histórias | **BORA-52** |
| Sem mudança — o limite do telefone errado reforça a dependência já registrada | **BORA-49** |
| Issue nova | **nenhuma** — o limite conhecido foi anotado dentro da BORA-49, que já é dona do assunto |

**O que esta rodada ensinou sobre o portão**, e vale mais que a sincronização em si: o
`/spec-check` tinha dado **SIM**, e a releitura crítica ainda achou cinco problemas. Portão
verifica **presença** de seção e **cobertura** de regra; não verifica se o recorte faz
sentido nem se a fatia entrega o que promete. Os dois são necessários, e nenhum substitui o
outro — como o E-012 já tinha ensinado sobre rede de proteção.

## Sincronização de 2026-09-09 — tipografia e papéis de cor

Nenhuma `RN` foi tocada: D21 e D22 são decisões do `design-system.md`, não regras de
domínio. O passo 7 se aplica ao quadro mesmo assim.

| Ação | Issue |
|---|---|
| Descrição atualizada — as duas maiores lacunas fecharam; sobrou medir os rótulos, ratificar quatro propostas e verificar o `next/font` | **BORA-39** |
| Sem mudança, mas agora é a dependência **nomeada** do que falta: nome da família e valores das cores | **BORA-25** |
| Issue nova | **nenhuma** |

A separação que se manteve em todas estas decisões — **estrutura agora, valor com o
redesenho** — é o que permite a BORA-39 avançar sem a BORA-25. Vale registrar porque foi ela
que evitou o bloqueio circular entre design-system e identidade visual.

## Sincronização de 2026-09-09 (segunda) — propostas despachadas

Nenhuma `RN` tocada: as quatro são decisões do `design-system.md`.

| Ação | Issue |
|---|---|
| Descrição atualizada — as quatro propostas saíram; sobrou medir os rótulos e verificar o `next/font` | **BORA-39** |
| Sem mudança — a "Ferramenta" e a D18 voltam quando ela for decidida | **BORA-5** (divisão de conta) |
| Issue nova | **nenhuma** |

**Padrão que vale registrar:** duas das quatro propostas **não sobreviveram à releitura na
forma em que estavam** — a D13 fechou em três arquétipos e a D17 foi reescrita. Levar
proposta antiga à mesa sem reler é como pedir ratificação de coisa que o autor já não
defende. A marcação de origem (Ícaro / proposta / derivada) serviu exatamente para isso:
tornou visível o que ainda não tinha sido examinado.

## Sincronização de 2026-09-09 (terceira) — ritual de abertura

Nenhuma `RN` tocada: a sessão criou a skill `session-open` e o `.gitattributes`, mudança de
**método e infra**, não de domínio.

| Ação | Issue |
|---|---|
| Conferência do quadro contra o catálogo — 32 issues `decisao-pendente`, todas em Backlog, nenhuma correspondendo a decisão tomada nesta sessão nem em paralelo | **nenhuma alteração** |
| Issue nova | **nenhuma** — ver abaixo |

**Por que a pendência nova não virou issue.** O `.gitattributes` abriu uma pergunta sem
dono: renormalizar ou não os arquivos já versionados. Ela foi registrada no `backlog.md`,
em *Infra do método*, ao lado das outras duas pendências de método (trailer
`Co-Authored-By` fixado na skill, e a `speckit-taskstoissues` que cria issue no GitHub) —
**nenhuma das duas tem issue no Linear**. Seguiu-se o precedente em vez de inaugurar
exceção. Se o Ícaro preferir que pendência de método também apareça no quadro, as três
entram juntas, e aí a regra passa a valer para as próximas.

**O que esta conferência não fez:** auditar as 32 issues uma a uma contra os sete catálogos
de `docs/domain/`. Foi verificado que nada **desta** sessão deveria fechar issue. A auditoria
completa continua sendo trabalho próprio — é ela que pegaria um caso como o do
`RN-DESC-003`, decidido em sessão paralela.

## Sincronização de 2026-09-09 (quarta) — tarefas da spec 002

**Nenhuma `RN` tocada.** A sessão rodou o `/speckit-tasks` e corrigiu artefatos da spec 002;
as três decisões tomadas — cinco tabelas, três rotas públicas, e onde vive a entrada de
aprovar reivindicações — são de **contagem e de tela**, não de regra de domínio. Nenhum
catálogo de `docs/domain/` mudou.

| Ação | Issue |
|---|---|
| Conferência do quadro contra o catálogo — **32 issues `decisao-pendente`, todas em Backlog**, nenhuma correspondendo a decisão desta sessão nem de sessão paralela | **nenhuma alteração** |
| Issue fechada | **nenhuma** |
| Issue nova | **nenhuma criada nesta sessão** — ver abaixo |

**Verificado, não presumido:** as 32 issues foram listadas pelo conector do Linear (time
`Bora`, rótulo `decisao-pendente`) e conferidas uma a uma contra o que esta sessão decidiu.
São as mesmas 32 da sincronização anterior. As três da spec 002 que aparecem no quadro —
BORA-49 (método automático de verificação), BORA-50 (desempate entre reivindicações) e
BORA-8 (provedor de mapas) — continuam **fora do escopo por decisão**, exatamente como a
`research.md` registra; nada nesta sessão as resolveu.

**Duas pendências novas ficaram sem issue, e isso é escolha a confirmar.** A geração das
tarefas abriu duas perguntas que estão registradas no `backlog.md`, em *Abertas pela spec
002*: **como uma conta ganha a permissão de operação** (bloqueia a US3 — o `RolesSeeder`
cria só `rolezeiro`) e **o `cover_path` que existe no modelo e em nenhum outro lugar**. Não
viraram issue porque **o Ícaro ainda não decidiu**, e o padrão do quadro é issue de decisão
nascer com o enunciado da escolha, não da descoberta. **A primeira é séria:** bloqueia uma
história inteira, e não é pendência de método como as três de *Infra do método* — se o
critério do quadro é "o que bloqueia spec vira issue", ela deveria virar. Fica proposta,
não executada.

**O que esta conferência não fez:** auditar as 32 issues contra os sete catálogos de
`docs/domain/`. Foi verificado que nada **desta** sessão deveria fechar issue. A auditoria
completa segue por fazer — é ela que pegaria um caso como o do `RN-DESC-003`.

## Sincronização de 2026-09-11 (quinta) — RN-PLAT-007, papel de operação

**Houve regra nova**, então esta é a primeira sincronização em que o passo 7 do `/doc-sync`
tem trabalho de verdade desde a `RN-DESC-003`. O quadro foi consultado pelo conector do
Linear — **não presumido**.

| Ação | Issue |
|---|---|
| Conferência do quadro contra o catálogo — **32 issues `decisao-pendente`**, todas em Backlog | **nenhuma alteração** |
| Issue fechada pela `RN-PLAT-007` | **nenhuma** — ver abaixo |
| Issue nova, pela pendência que a decisão gerou | **BORA-53** — *Revogar o papel de operação: a `RN-PLAT-007` só define a concessão*, rótulo `decisao-pendente`, projeto Bora, Backlog |

**Por que nenhuma issue fechou.** A `RN-PLAT-007` não tinha issue. A pergunta que ela
responde — *como uma conta ganha a permissão de operação?* — foi **aberta em 2026-09-09**,
ao gerar as tarefas, e **decidida em 2026-09-10**, na sessão seguinte. Nasceu e morreu entre
duas sessões, sem nunca chegar ao quadro. As 32 issues foram conferidas uma a uma contra a
regra nova; nenhuma trata de papel de operação.

**Isto é o inverso do risco que o passo 7 existe para pegar.** As duas falhas anteriores
(`RN-PLAT-002` e `RN-DESC-003`) foram decisão tomada e quadro desatualizado. Aqui o quadro
nunca soube da pergunta. **Opinião, não regra:** decisão que vive menos de um dia
provavelmente não precisa de issue, mas decisão que **bloqueia história** — e esta bloqueava
a US3 — talvez devesse nascer no quadro mesmo assim, para ficar visível enquanto está
aberta. Fica como observação para o Ícaro; não mudei o critério por conta própria.

**A BORA-53 e o que ela cobre.** A `RN-PLAT-007` define a **concessão** do papel e não diz
nada sobre a **revogação**: não existe `bora:revoke-operator`, e o catálogo não diz se
revogar apaga o vínculo ou o encerra preservando histórico (Princípio X, `RN-PLAT-005`), se
a revogação é auditável, nem o que acontece com o registro de **quem aprovou** reivindicação
(`RN-LOCAL-005`). **Não bloqueia a spec 002**, que concede e não revoga; é pré-requisito da
futura **spec de operação**, que a própria `RN-PLAT-007` nomeia e que ainda não tem issue.
A linha entrou também na tabela "Decisões pendentes" do `backlog.md`.

**O que esta conferência não fez:** auditar as 32 issues contra os sete catálogos de
`docs/domain/`. Foi verificado o que **esta** regra nova toca. A auditoria completa segue
por fazer.

## Sincronização de 2026-09-14 (sexta) — portão de conformidade de tela (T001–T008)

**Nenhuma `RN` tocada.** A sessão implementou a Phase 1 e a camada 4 da Phase 2 da spec 002:
árvore de pastas, linha de base das suítes, e o portão de conformidade de tela rodado contra
as telas da spec 001. É infraestrutura de teste — nenhum catálogo de `docs/domain/` mudou, e
o `git diff` dos dois commits não toca `api/`, `docs/domain/`, `contracts/` nem
`data-model.md`.

| Ação | Issue |
|---|---|
| Conferência do quadro contra o catálogo — **33 issues `decisao-pendente`**, todas em Backlog (as 32 da sincronização anterior mais a **BORA-53**, criada nela) | **nenhuma alteração** |
| Issue fechada | **nenhuma** — nenhuma regra foi decidida |
| Issue nova | **nenhuma criada** — duas propostas, abaixo |

**Verificado, não presumido:** as 33 foram listadas pelo conector do Linear (time `Bora`,
rótulo `decisao-pendente`) e conferidas contra o que esta sessão produziu. Nenhuma trata de
portão de tela, severidade de lint ou convenção de diretório vazio.

### Duas pendências novas ficaram sem issue — de novo por escolha, não por esquecimento

1. **Severidade da regra de lint da T009** — a regra que proíbe cor literal em tela entra
   como **erro agora**, aceitando o lint vermelho por cor até a T021, ou como **aviso agora,
   promovido a erro na T021**? **Esta bloqueia o resto da Phase 2 da spec 002**, e com ela
   as quatro histórias. Já está na tabela de decisões pendentes do `backlog.md`, com a
   medição: a regra pega 10 ocorrências, 9 em `page.tsx` e 1 em `alert.tsx:23`.
2. **Convenção para diretório vazio no Git** (`.gitkeep` ou nada). Não bloqueia nada hoje.

**Por que não viraram issue.** É a terceira sincronização seguida em que a mesma pergunta
aparece, e ela continua sem resposta do Ícaro: *pergunta aberta vira issue, ou só decisão
tomada vira?* O padrão observado no quadro é que a issue de decisão nasce com o **enunciado
da escolha**, não com a descoberta — e as duas acima já têm enunciado. A de 2026-09-09
deixou registrado que "o que bloqueia spec deveria virar issue"; a de 2026-09-11 observou o
inverso, que decisão de vida curta talvez não precise de issue. **Nas duas o critério ficou
como proposta e o Ícaro não o ratificou**, então continuo sem executá-lo por conta própria.

**A diferença desta vez é o custo de errar.** A pendência nº 1 **para o trabalho**: sem ela
não há T009, e sem T009 não há kit, shell, retrofit nem história. Se o critério for "o que
bloqueia spec vira issue", ela é o exemplo mais claro que já apareceu. **Fica proposta;
basta o Ícaro dizer "cria" e ela entra com o rótulo `decisao-pendente`.**

**O que esta conferência não fez, de novo:** auditar as 33 issues contra os sete catálogos
de `docs/domain/`. Foi verificado que nada **desta** sessão deveria fechar issue — o que é
barato, porque esta sessão não decidiu regra nenhuma. A auditoria completa, que é a que
pegaria um caso como o do `RN-DESC-003`, **segue por fazer**.

**As 159 tarefas da spec 002 continuam sem virar issue.** A convenção do topo deste arquivo
diz que cada spec aprovada gera issues a partir do `tasks.md` com título `T001: <descrição>`;
para a spec 002 isso **nunca foi feito**. Não foi feito agora também: são 159 issues de uma
vez, e isso é decisão do Ícaro sobre como ele quer acompanhar o trabalho, não do assistente.
Enquanto não for feito, **o quadro não mostra o andamento da spec 002** — quem olhar só o
Linear não vê que T001–T008 estão prontas.

