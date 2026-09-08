# Feature Specification: Cadastro e Perfil de Estabelecimento

**Feature Branch**: `002-cadastro-perfil-local`

**Created**: 2026-09-08

**Status**: Draft — aguardando `/spec-check` e aprovação do Ícaro.

**Input**: Cadastro e perfil de estabelecimento (local). Segunda spec do Bora; destrava o
catálogo. Recorte acordado com o Ícaro em 2026-09-06 (`docs/logs/backlog.md` → "Próximo
passo").

> **As regras de negócio desta feature já estão decididas** e vivem no catálogo:
> `RN-LOCAL-001` a `RN-LOCAL-005` em `docs/domain/locais.md`. Esta spec **referencia os
> IDs**; não reescreve o texto das regras (`development-workflow.md` §6).

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Coloco um bar no Bora e vejo a página dele no ar (Priority: P1)

Uma pessoa com conta preenche um formulário curto — nome, endereço, categoria e telefone —
e, ao terminar, cai na **página pública do bar**, com um endereço próprio que ela pode
copiar e mandar no WhatsApp ou colar no Instagram.

O perfil nasce **não reivindicado** (`RN-LOCAL-005`) e **magro** (`RN-LOCAL-001`): sem
fotos, sem descrição, sem Instagram. A página diz isso com todas as letras, para ninguém
confundir "ainda não confirmado" com "dono desleixado".

**Why this priority**: é a menor fatia que entrega valor de verdade — **o bar passa a
existir na internet com link compartilhável** — e é a que obriga a fundação a existir. Sem
ela não há catálogo, e sem catálogo nenhuma outra história do Bora tem sobre o que operar.
Também é a fatia que exercita as duas receitas de tela mais importantes (Formulário e
Detalhe) e a fronteira servidor/cliente do ADR-0003.

**Independent Test**: entrar com uma conta, cadastrar um bar informando só os quatro campos
obrigatórios, ser levado à página pública dele, copiar o endereço, abrir esse endereço numa
janela anônima (sem sessão) e ver a mesma página, com o aviso de perfil não gerenciado.
Entrega valor sozinha: o bar está publicado e compartilhável.

**Acceptance Scenarios**:

1. **Given** uma pessoa autenticada, **When** ela envia o formulário com nome, endereço,
   pelo menos uma categoria e telefone, **Then** o perfil é criado no estado **não
   reivindicado** e ela é levada à página pública dele.
2. **Given** um perfil não reivindicado, **When** qualquer pessoa abre a página pública
   dele — inclusive sem estar autenticada, **Then** vê nome, endereço, categorias e
   telefone, e a linha *"Este perfil ainda não é gerenciado pelo estabelecimento — as
   informações podem estar incompletas"*.
3. **Given** um perfil não reivindicado, **When** a página é renderizada, **Then**
   **nenhum** campo rico aparece (sem fotos, sem descrição, sem Instagram) — nem vazio, nem
   como espaço reservado.
4. **Given** a página pública de um bar, **When** a pessoa toca na ação principal,
   **Then** o aplicativo de mapas do aparelho abre com aquele endereço (`RN-DESC-004`,
   Fase 1: link externo, sem custo de API).
5. **Given** alguém que chegou por link compartilhado, sem histórico de navegação, **When**
   olha a página, **Then** encontra um caminho **visível e nomeado** para o resto do Bora
   (`D20` — nunca `history.back()`).
6. **Given** o formulário preenchido com telefone inválido, **When** a pessoa envia,
   **Then** o erro aparece **no campo**, em português do dia a dia, dizendo o que fazer.
7. **Given** o formulário de cadastro, **When** a pessoa marca **"sou eu que gerencio este
   bar"**, **Then** o perfil é criado **e** o pedido de reivindicação é aberto junto, com
   confirmação de que será analisado — a aprovação continua manual e continua na P2.
8. **Given** que já existe um local com nome parecido no mesmo bairro, **When** a pessoa
   tenta cadastrar, **Then** o sistema **avisa antes de criar** e mostra o que encontrou,
   deixando a pessoa decidir se é o mesmo lugar ou não.

---

### User Story 2 - Reivindico o perfil do meu estabelecimento (Priority: P2)

O gestor do bar pede a reivindicação do perfil. O pedido entra numa fila; a plataforma
aprova **à mão** (`RN-LOCAL-005`, Fase 1). Aprovado, o perfil passa a **reivindicado**,
ganha o selo **"Perfil do estabelecimento"** e o gestor passa a poder editá-lo.

**Why this priority**: é o que separa "catálogo de terceiros" de "presença oficial", e é o
portão que a `RN-EVENTO-001` vai usar depois para permitir publicar evento. Não é P1 porque
o catálogo já tem valor sem ela — e porque, sem perfis criados, não há o que reivindicar.

**Independent Test**: com um perfil não reivindicado já existente, pedir a reivindicação
por uma conta, aprovar pelo caminho de administração, e verificar que o selo aparece na
página pública e que a conta aprovada passa a editar o perfil.

**Acceptance Scenarios**:

1. **Given** um perfil não reivindicado, **When** uma conta autenticada pede a
   reivindicação, **Then** o pedido fica **pendente** e a pessoa vê confirmação explícita
   de que o pedido foi registrado e será analisado.
2. **Given** um pedido pendente, **When** a plataforma aprova, **Then** o perfil passa a
   **reivindicado**, exibe o selo com ícone **e o texto "Perfil do estabelecimento"**, e a
   conta passa a ter papel de gestor vinculado àquele local.
3. **Given** um perfil criado por um terceiro e depois reivindicado pelo gestor legítimo,
   **When** a aprovação acontece, **Then** o perfil é **transferido, não recriado** —
   avaliações, comentários e histórico continuam ligados ao mesmo local (Princípio X).
4. **Given** qualquer criação, pedido ou aprovação, **When** a operação conclui, **Then**
   fica registrado quem fez, quando, e **por qual método a reivindicação foi aprovada**
   (Princípio VIII; o método é registrado porque vai mudar — BORA-49).
5. **Given** uma conta sem vínculo com o local, **When** tenta editar o perfil, **Then** a
   operação é recusada (Princípio V).
6. **Given** um pedido pendente, **When** a plataforma **recusa**, **Then** o motivo é
   registrado, o solicitante é avisado com o motivo **em linguagem simples** e um **caminho
   para falar com a plataforma**, e pode pedir de novo depois de resolver.
7. **Given** um pedido já pendente para um local, **When** outra conta pede a reivindicação
   do mesmo local, **Then** o segundo pedido **também é aceito** e os dois ficam pendentes,
   apresentados **lado a lado** a quem decide.
8. **Given** dois pedidos pendentes para o mesmo local, **When** um é aprovado, **Then** os
   demais são **encerrados como recusados**, com o motivo "o perfil foi reivindicado por
   outra pessoa", e cada solicitante é **avisado** — encerrar em silêncio é proibido pela
   régua.

---

### User Story 3 - Encontro bares na lista e filtro por categoria (Priority: P3)

O rolezeiro abre a lista de locais da cidade e filtra por categoria. Cada item mostra o
nome e o **bairro** (`RN-LOCAL-004`), para distinguir unidades e para responder à pergunta
geográfica que o produto faz.

**Why this priority**: transforma perfis soltos em catálogo navegável. Depois da P2 porque
uma lista de perfis não reivindicados vale menos que uma lista com presenças confirmadas.

**Independent Test**: com alguns locais cadastrados em categorias diferentes, abrir a
lista, aplicar cada filtro e verificar que o conjunto muda; conferir que nenhum item traz
informação que dependa de quem está olhando.

**Acceptance Scenarios**:

1. **Given** locais cadastrados, **When** o rolezeiro abre a lista, **Then** vê nome e
   bairro de cada um, em **uma coluna** no celular.
2. **Given** a lista aberta, **When** filtra por uma categoria, **Then** vê apenas os
   locais daquela categoria; o filtro tem rótulo de texto, nunca só ícone ou só cor.
3. **Given** a lista pública, **When** é renderizada, **Then** **nenhum** item exibe estado
   que dependa do usuário — sem coração de "salvo", sem "seguindo" (`D14`, `D15`).
4. **Given** nenhum local na categoria escolhida, **When** o filtro é aplicado, **Then** a
   tela **ensina** o que fazer, em vez de ficar em branco.

---

### User Story 4 - Enriqueço o perfil do meu estabelecimento (Priority: P4)

Com o perfil reivindicado, o gestor acrescenta fotos/logo, descrição e Instagram — os
campos que a `RN-LOCAL-003` prevê e que o perfil magro não tem.

**Why this priority**: é o **prêmio da reivindicação** (`RN-LOCAL-001`) — e é o incentivo
que faz o gestor querer reivindicar. Último porque o catálogo funciona sem ele: um perfil
magro já leva a pessoa até a porta do bar.

**Independent Test**: com um perfil reivindicado, acrescentar foto, descrição e Instagram,
e verificar que aparecem na página pública; com um perfil não reivindicado, verificar que
esses campos não são sequer oferecidos.

**Acceptance Scenarios**:

1. **Given** um perfil reivindicado, **When** o gestor adiciona foto, descrição e
   Instagram, **Then** os três aparecem na página pública.
2. **Given** um perfil **não** reivindicado, **When** qualquer pessoa tenta enriquecê-lo,
   **Then** os campos ricos não estão disponíveis (`RN-LOCAL-001`).
3. **Given** um envio de imagem, **When** o arquivo excede o tamanho ou o tipo permitido,
   **Then** a recusa explica o limite em linguagem humana (Princípio V).

---

### Edge Cases

- **Duas pessoas cadastram o mesmo bar.** Resolvido em 2026-09-08: o sistema **avisa na
  criação** quando já existe local parecido no mesmo bairro, e quem cadastra decide. O
  critério de "parecido" pode ser grosseiro e ainda pegar a maioria dos casos.
- **O aviso de duplicata é ignorado e o perfil repetido é criado mesmo assim.** Continua
  sendo caso de unificação manual — a prevenção reduz o volume, não o elimina.
- **Duas pessoas reivindicam o mesmo perfil.** O **comportamento mínimo** está definido
  (FR-022, FR-023): os dois pedidos ficam pendentes e são decididos juntos; aprovar um
  encerra o outro com aviso. O **critério de desempate** — como escolher entre dois pedidos
  plausíveis — continua fora desta spec: é a **BORA-50**.
- **Pedido recusado e refeito em looping.** A recusa permite novo pedido (FR-021), e nada
  hoje limita a frequência. Aceito na Fase 1, em que a fila é pequena e a aprovação é
  manual; vira problema quando o volume crescer, junto com a BORA-49.
- **O bar troca de dono.** Re-reivindicação de perfil já reivindicado também é BORA-50.
- **Perfil não reivindicado tenta publicar evento.** Bloqueado (`RN-EVENTO-001`). O evento
  em si é outra spec; aqui só o bloqueio precisa existir e ser testado.
- **Local encerra as atividades.** Não se exclui: **inativa** (Princípio X), e o sistema
  informa qual condição impede a exclusão quando alguém tentar.
- **Chegada fria por link compartilhado**, sem histórico e sem barra de navegação (a barra
  some no Detalhe, `D20`): a pessoa não pode ficar presa.
- **Rede lenta durante o envio do formulário**: toque duplo não pode criar dois perfis.
- **Zoom de 200% e fonte do sistema ampliada**: a fileira de ações do Detalhe reflui pelo
  conteúdo, nunca por `media query` de largura (`D12`).

---

## Requirements *(mandatory)*

### Functional Requirements

**Criação e estado do perfil**

- **FR-001**: O sistema MUST permitir que **qualquer conta autenticada** crie o perfil de
  um local (`RN-LOCAL-001`). Criar não exige vínculo com o estabelecimento.
- **FR-002**: O perfil MUST nascer no estado **não reivindicado** e **magro** — apenas
  nome, endereço, categoria(s) e telefone (`RN-LOCAL-001`, `RN-LOCAL-005`).
- **FR-003**: O sistema MUST aceitar **uma ou mais** categorias por local, **sem limite**,
  a partir da lista **bar, restaurante, casa de shows** (`RN-LOCAL-002`). A lista MUST ser
  **dado gerido pela plataforma**, nunca valor fixo em código.
- **FR-004**: O sistema MUST expor o perfil público em endereço próprio, **estável e
  compartilhável**, com caminho em português (`naming-conventions.md`).
- **FR-005**: O perfil público MUST declarar seu estado em texto: selo com ícone **e** o
  rótulo "Perfil do estabelecimento" quando reivindicado; a linha "Este perfil ainda não é
  gerenciado pelo estabelecimento — as informações podem estar incompletas" quando não
  (`RN-LOCAL-005`). Nunca só por ícone, nunca só por cor.
- **FR-006**: Listagens MUST exibir o **bairro** junto ao nome, derivado do endereço, sem
  campo próprio no formulário (`RN-LOCAL-004`).

**Reivindicação**

- **FR-007**: O sistema MUST permitir que uma conta autenticada **peça** a reivindicação de
  um perfil, e MUST confirmar visivelmente que o pedido foi registrado.
- **FR-008**: A aprovação na Fase 1 MUST ser **manual, pela plataforma** (`RN-LOCAL-005`).
  A **política** vive no domínio e o **método** é parâmetro — trocar o método MUST NOT
  exigir mudança de regra.
- **FR-009**: A aprovação MUST **transferir** o perfil, nunca recriá-lo: avaliações,
  comentários e histórico permanecem ligados ao mesmo local (Princípio X).
- **FR-010**: O sistema MUST registrar auditoria de quem criou, quem pediu, quem aprovou,
  quando, e **por qual método** a reivindicação foi aprovada (Princípio VIII).

**Autorização, ciclo de vida e fronteiras**

- **FR-011**: Somente conta com papel de **gestor vinculado àquele local** MUST poder
  editá-lo (Princípio V, `RN-LOCAL-001`).
- **FR-012**: O vínculo gestor↔local MUST ser **N:N** — uma conta gerencia vários locais,
  sempre a mesma conta, nunca contas paralelas (Princípio I, `RN-LOCAL-004`).
- **FR-013**: O sistema MUST recusar publicação de evento para local **não reivindicado**
  (`RN-EVENTO-001`). O evento em si está fora desta feature; **o bloqueio, não**.
- **FR-014**: Local com histórico MUST NOT ser excluído — é **inativado**, e o sistema MUST
  informar qual condição impede a exclusão (Princípio X). *(Como a FR-013, este é um
  **guarda**, não uma funcionalidade: garante que a operação seja recusada. Guarda não
  precisa de tela; funcionalidade precisa. A **tela** de inativar um local não faz parte
  desta feature — quando alguém for inativar pela interface, será outra spec, e aí o
  Princípio XI se aplica a ela.)*
- **FR-015**: Campos ricos — fotos/logo, descrição, Instagram (`RN-LOCAL-003`) — MUST estar
  disponíveis **apenas** para perfil reivindicado.
- **FR-016**: Uploads MUST ser validados por tipo e tamanho antes de serem servidos
  (Princípio V).
- **FR-017**: A lista pública MUST NOT conter nenhum controle ou informação cujo estado
  dependa de quem está olhando (`D15`) — o servidor não sabe quem é, e controle que finge
  saber mente.
- **FR-018**: Envio duplicado do formulário — toque duplo em rede lenta — MUST NOT criar
  dois perfis.
- **FR-019**: O formulário de cadastro MUST oferecer a opção **"sou eu que gerencio este
  bar"**; marcada, ela MUST abrir o **pedido** de reivindicação junto com a criação
  (`RN-LOCAL-005`). A **aprovação** permanece manual e permanece fora da P1 — a regra não
  muda, apenas deixa de exigir um segundo pedido de quem já se identificou.
- **FR-020**: Antes de criar, o sistema MUST avisar quando já existir local **parecido no
  mesmo bairro**, exibindo o que encontrou, e MUST deixar a decisão com quem cadastra
  (`RN-LOCAL-004`). Prevenir é barato; unificar depois é caro, porque o Princípio X proíbe
  apagar — histórico, avaliações e reivindicações teriam de ser costurados.
- **FR-021**: A recusa de um pedido MUST registrar o motivo, MUST **avisar o solicitante**
  com o motivo em linguagem simples e um **caminho para falar com a plataforma**, e MUST
  permitir novo pedido depois. Pedido que some da fila sem resposta é o silêncio que o
  `ux-requirements.md` proíbe.
- **FR-022**: Um segundo pedido para um local com pedido pendente MUST ser **aceito**; os
  pedidos MUST ficar todos pendentes e MUST ser apresentados juntos a quem decide. Perder o
  registro de quem pediu antes destruiria justamente o dado que o Princípio VIII manda
  guardar para arbitrar disputa. *(O **critério de desempate** continua fora desta spec —
  é a BORA-50; aqui se define só o comportamento mínimo.)*
- **FR-023**: Aprovar um pedido MUST encerrar os demais pendentes daquele local como
  recusados, com motivo registrado, **avisando cada solicitante** (decorre de FR-021 e
  FR-022 juntos).

### Key Entities

- **Local (estabelecimento)**: o bar ou restaurante físico. Nome, endereço completo (com
  bairro), telefone, categorias, estado de reivindicação, situação (ativo/inativo). Um por
  unidade física; sem entidade "rede" acima dele (`RN-LOCAL-004`).
- **Categoria de local**: rótulo gerido pela plataforma; lista inicial bar, restaurante,
  casa de shows. É **dado**, não código (`RN-LOCAL-002`).
- **Vínculo de gestão**: liga uma conta a um local com papel de gestor. **N:N**.
- **Pedido de reivindicação**: quem pediu, para qual local, quando, situação (pendente,
  aprovado, recusado), quem decidiu e **por qual método**.
- **Registro de auditoria**: já existe no projeto (Princípio VIII); esta feature passa a
  alimentá-lo com criação, pedido e aprovação.

---

## Cenários de Teste *(obrigatório — Princípio IX)*

> Seção acrescentada ao template depois que o `/spec-check` reprovou a primeira escrita
> desta spec: ela declarava só testes de tela, e o Princípio IX exige **backend e
> frontend**, mais um teste por `RN` referenciada e um teste que **prove o bloqueio** de
> cada princípio NON-NEGOTIABLE tocado.

### Toda `RN` referenciada tem teste que a exercita

| Regra | Teste que a exercita |
|---|---|
| `RN-LOCAL-001` | Conta **sem vínculo nenhum** cria um perfil com sucesso; o perfil criado **não aceita nem expõe** campos ricos (foto, descrição, Instagram) |
| `RN-LOCAL-002` | Aceita 1, 2 e 3 categorias no mesmo local; **recusa** categoria fora da lista; a lista vem de **dado**, e acrescentar uma categoria nova não exige mudar código |
| `RN-LOCAL-003` | Perfil **reivindicado** exibe foto, descrição, telefone clique-para-ligar, endereço e Instagram |
| `RN-LOCAL-004` | Listagem mostra o **bairro** derivado do endereço; **uma conta** gerencia **dois locais** (N:N); cadastro de nome parecido no mesmo bairro **dispara o aviso** |
| `RN-LOCAL-005` | Os dois estados aparecem com o texto certo; aprovação **transfere** o perfil e as avaliações continuam ligadas ao mesmo local; o registro guarda **por qual método** foi aprovado |
| `RN-EVENTO-001` | Local **não reivindicado** tem a publicação de evento **recusada** |
| `RN-DESC-004` | A ação "Como chegar" produz link para o app de mapas **com o endereço**, sem chamar provedor pago |

### Todo princípio NON-NEGOTIABLE tocado tem teste que prova o bloqueio

| Princípio | Teste que prova o bloqueio |
|---|---|
| **I** — conta única multi-papel | Uma conta vira gestora de **dois** locais **sem** que nenhuma segunda conta seja criada; o papel resolve a partir da conta existente |
| **II** — gratuidade do usuário final | Todo o catálogo público — perfil, lista, busca, "como chegar" — responde **sem sessão e sem qualquer cobrança** |
| **V** — autorização por padrão | Conta **sem vínculo** recebe recusa ao tentar editar; toda entrada passa por validação dedicada, e campo não declarado é ignorado |
| **VIII** — auditoria de escrita | Criação, pedido e aprovação **cada um** geram registro recuperável com quem, quando e método |
| **X** — histórico preservado | Local com histórico **não é excluído**; a recusa **informa qual condição** impede |
| **XI / XII** — entrega vertical e usabilidade | Ver "Testes de tela", abaixo — 360/1280, `axe`, fonte ampliada |

### Cenários de API (backend)

- **Criar**: sucesso; falta de cada campo obrigatório; categoria inexistente; sem
  autenticação; **duplicata detectada** (FR-020); envio repetido não cria dois (FR-018).
- **Pedir reivindicação**: sucesso; em perfil **já reivindicado**; com a opção "sou eu que
  gerencio" marcada no cadastro (FR-019).
- **Aprovar / recusar**: aprovação transfere e audita; **recusa registra motivo e dispara o
  aviso** (FR-021); **segundo pedido pendente é aceito** e os dois aparecem juntos
  (FR-022); aprovar um **encerra os demais como recusados, com aviso** (FR-023); tentativa
  por conta sem permissão de operação.
- **Ler perfil público**: sem token; perfil magro **não vaza** campo rico nem em resposta
  de API.
- **Editar**: só gestor vinculado; conta de outro local recusada.
- **Upload**: tipo não permitido; acima do tamanho.

---

## Tela e Experiência *(obrigatório neste projeto — Princípios XI e XII)*

Referência vinculante: `docs/product/ux-requirements.md` — **todos** os requisitos daquele
documento se aplicam a todas as telas abaixo. Decisões de estrutura já tomadas em
`docs/product/design-system.md` (D9, D10, D14, D15, D16, D20) são cumpridas, não
redecididas aqui.

### Telas entregues nesta feature

- **Cadastrar local** (P1) — ação principal: **cadastrar** (um botão). Receita
  **Formulário**. Caminho: a partir da área autenticada, no máximo 2 toques da home.
  Campos: nome, endereço, categoria(s), telefone. Nada além — o perfil é magro por regra.
- **Perfil público do local** (P1) — ação principal: **Como chegar** (`D16`). Receita
  **Detalhe**, renderizada **no servidor** (catálogo público, ADR-0003). Hierarquia de três
  níveis (`D20`): a ação principal embaixo; a fileira de ações diretas (ligar, salvar,
  seguir, convidar); e o conteúdo que se rola. **No Detalhe a barra de navegação some** e
  dá lugar à ação principal; o **"voltar" é link para destino nomeado**, nunca gesto do
  navegador.
- **Pedir reivindicação** (P2) — ação principal: **pedir a reivindicação**. Receita
  Formulário, curta.
- **Aprovar reivindicações** (P2) — ação principal: **aprovar**. Recusar é ação secundária e
  **exige motivo** (FR-021). Receita **Lista privada**, do operador da plataforma. Quando um
  local tem mais de um pedido, os pedidos aparecem **agrupados por local**, lado a lado, para
  a decisão ser tomada com os dois à vista (FR-022).
- **Aviso de resultado da reivindicação** (P2) — não é tela: é a mensagem que chega ao
  solicitante quando o pedido é aprovado ou recusado, com o motivo em linguagem simples e o
  caminho para falar com a plataforma. Entra aqui porque **o caminho até a resposta é parte
  da feature** — a lição do E-019.
- **Lista de locais** (P3) — ação principal: **não tem botão — o item é o alvo**, com altura
  ≥ 44px e a linha inteira clicável. Receita **Lista pública**. Filtro por categoria com
  rótulo de texto.
- **Editar perfil do local** (P4) — ação principal: **salvar**. Receita Formulário; só para
  perfil reivindicado.

> **Lição da spec 001 aplicada aqui de propósito** (E-019): listar a tela não basta — **o
> caminho até ela é parte da tela**. Cada tela acima declara por onde se chega.

### Comportamento no celular (dispositivo principal)

- **A 360px**: uma coluna em todas as telas. O formulário ocupa a largura com margens
  confortáveis. Na lista, cada local é uma linha com nome e bairro — nunca tabela, nunca
  rolagem lateral. No Detalhe, a foto/cabeçalho, depois a fileira de ações, depois o
  conteúdo.
- **Ação principal ao alcance do polegar**: no Formulário, o botão vem **abaixo dos
  campos**, na metade inferior. No Detalhe, **"Como chegar"** fica fixo na metade inferior,
  no lugar que a barra de navegação ocupava.
- **A partir de 768px**: a barra inferior vira **trilho lateral** (`D11`), e **no Detalhe o
  trilho permanece** — a barra some por falta de altura, e altura não falta a 1280 (`D20`).
  A 1280 o Detalhe usa o espaço extra para **mostrar mais** (conteúdo ao lado do bloco de
  contato), nunca para espalhar controles.
- **Refluxo por conteúdo**, nunca por `media query` de largura (`D12`): o aumento de fonte
  do sistema não muda a largura do viewport e escaparia dos testes de 360/1280.

### Estados obrigatórios

- **Carregando**: no Formulário, o botão assume o estado e fica desabilitado — é também o
  que impede a dupla submissão (FR-018). **A página pública renderizada no servidor não tem
  carregando na primeira pintura** (`D15`): ela chega pronta; o carregando existe só para
  interação (filtrar, carregar mais).
- **Vazio**: a lista sem resultados **ensina** — ex.: "Nenhum bar nesta categoria por aqui
  ainda. Conhece um? Cadastre em um minuto." Nunca tela em branco.
- **Erro**: erro de campo aparece **no campo**, em português do dia a dia ("Digite o
  telefone com DDD", nunca "campo inválido"). Erro geral vem em faixa no topo, dizendo **o
  que fazer**.
- **Sucesso**: ao cadastrar, a pessoa é levada à página pública do bar — a confirmação **é**
  o resultado visível, não um aviso que some.

### Acessibilidade (`docs/product/ux-requirements.md`)

- Fonte base ≥ 16px; alvos de toque ≥ 44px **por padrão do componente**, não por correção
  na chamada — é o que a fundação conserta (o `Button` atual é 32px).
- **Ícone nunca sozinho**: o selo de estado carrega o texto "Perfil do estabelecimento"; o
  filtro de categoria carrega rótulo; as ações da fileira do Detalhe carregam rótulo.
- **Informação nunca só por cor**: o estado do perfil é texto, não cor de borda.
- HTML semântico, foco visível, navegável por teclado, `aria` correto no selo e no filtro.
- Zoom de 200% sem quebra; nada depende de `hover` (a fileira de ações do Detalhe é toque).
- Qualquer transição respeita `prefers-reduced-motion`; nenhuma animação é necessária para
  entender o conteúdo.
- **Navegação rasa (~3 toques da home):** cadastrar, 2 toques; perfil público a partir da
  lista, 2; pedir reivindicação a partir do perfil, 1; lista, 1. O caminho de volta é sempre
  visível e **nomeado** (`D20`).
- **Ação de efeito público** (`ux-requirements.md`, "Feedback e confiança"): criar o perfil
  de um bar publica conteúdo. A régua é considerada cumprida **sem etapa extra de
  confirmação**, e a razão fica escrita para quem reler: a intenção é inequívoca (a pessoa
  preencheu um formulário chamado "Cadastrar local"), o **aviso de duplicata** já interrompe
  no único caso ambíguo (FR-020), e o resultado é a **própria página pública** — não há
  efeito escondido. Acrescentar um "tem certeza?" aqui custaria a meta de 2 minutos da
  SC-001 sem reduzir risco. **Julgamento, não fato** — se você discordar, é uma linha a mais.
- Contraste AA em todo texto e ícone informativo — **depende dos papéis semânticos de cor**,
  que são item da fundação.

### Testes de tela (Princípio IX)

- Larguras obrigatórias: **360 e 1280**. Acrescentar **390–430** no Detalhe, que é a tela
  mais densa.
- **Verificação automatizada de acessibilidade**: `axe` sem violações em toda tela entregue,
  como já é feito na spec 001.
- **Assertiva a mais, exigida pela D12**: rodar também com **fonte ampliada**, não só com
  largura reduzida — o cenário de fonte do sistema não dispara `media query` e passaria
  despercebido em 360/1280.
- Cenários de erro cobertos por teste de tela: telefone inválido; categoria não escolhida;
  envio duplo em rede lenta (FR-018); upload recusado por tipo/tamanho; edição por conta sem
  vínculo (FR-011); chegada fria em página pública sem histórico, verificando que o caminho
  de volta nomeado existe e funciona.
- **Testes de bloqueio e mapeamento por `RN`**: ver a seção **"Cenários de Teste"** acima —
  ela cobre backend e frontend, uma linha por `RN` referenciada e uma por princípio
  NON-NEGOTIABLE tocado. Esta lista aqui é só a parte de tela.
- **Cenários de tela que provam bloqueio**: local não reivindicado não oferece publicar
  evento; perfil magro não mostra campo rico nem vazio; a lista pública não renderiza
  nenhum controle dependente de quem olha (`D15`).

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Uma pessoa que nunca usou o Bora cadastra um bar **em menos de 2 minutos**,
  pelo celular, sem ajuda e sem instruções fora da tela.
- **SC-002**: Ao terminar o cadastro, a pessoa tem em mãos um **endereço compartilhável**
  que abre a página do bar para quem não tem conta.
- **SC-003**: Em **360 e 1280**, nenhuma tela da feature tem rolagem horizontal, elemento
  cortado ou violação de acessibilidade detectada automaticamente.
- **SC-004**: Quem abre a página de um bar por link compartilhado, sem histórico, **sempre**
  encontra um caminho visível para o resto do produto — 0% de becos sem saída.
- **SC-005**: Numa conferência informal com pessoas de fora, a maioria explica **sem ajuda**
  a diferença entre um perfil gerenciado pelo estabelecimento e um que ainda não é.
- **SC-006**: A página pública do bar é utilizável em **rede lenta e aparelho modesto** — o
  conteúdo essencial (nome, endereço, telefone, como chegar) aparece antes das imagens.
- **SC-007**: 100% das criações, pedidos e aprovações têm registro de auditoria recuperável
  com quem, quando e por qual método.

---

## Clarifications *(resolvidas com o Ícaro em 2026-09-08)*

Duas perguntas ficaram em aberto na primeira escrita, por afetarem **escopo** e não detalhe.
Regra do projeto: não inventar — perguntar (`CLAUDE.md`, Guardrails). As duas respostas
**refinam regra de domínio** e por isso foram escritas também em `docs/domain/locais.md`;
aqui ficam o registro e o porquê.

### Q1 — Criar o perfil já pede a reivindicação? → **Sim, se a pessoa se identificar**

A `RN-LOCAL-005` diz que o perfil nasce não reivindicado e que a reivindicação é ato
separado, aprovado à mão — mas não dizia o que acontece quando quem cadastra **é** o dono.
Pela letra da regra, ele veria "este perfil ainda não é gerenciado pelo estabelecimento" no
próprio bar e precisaria pedir de novo.

**Decidido:** o formulário oferece **"sou eu que gerencio este bar"**, e marcar isso abre o
pedido junto (FR-019). A aprovação continua manual e continua na P2 — **a regra não muda**,
só deixa de exigir um segundo passo de quem já se identificou, no momento de maior intenção.

Descartado: dar controle imediato a quem cria. Isso contradiria a `RN-LOCAL-005` e reabriria
a decisão da BORA-22 — a porta da verificação deixaria de ser a publicação para quem
chegasse primeiro, e exigiria emenda à regra.

### Q2 — Quem identifica duplicata, e quando? → **O sistema avisa na criação**

A `RN-LOCAL-004` manda unificar duplicata sem apagar, mas em voz passiva: *"duplicata
identificada é unificada"*, sem dizer quem nem quando.

**Decidido:** o sistema avisa antes de criar quando já existe local parecido **no mesmo
bairro**, e quem cadastra decide (FR-020). O critério pode ser grosseiro e ainda pegar a
maioria.

**O argumento que decidiu:** por causa do Princípio X, **unificar é caro** — não se apaga o
perfil repetido; histórico, avaliações e reivindicações precisam ser preservados e
costurados. Prevenir custa um aviso; consertar custa uma migração de dados com histórico.

---

## Assumptions

- **Endereço é texto estruturado, sem geocodificação nesta feature.** A `RN-DESC-004` já
  decidiu que a Fase 1 resolve "como chegar" com **link para o app de mapas do aparelho**,
  sem custo de API — e link com endereço em texto não precisa de coordenadas. Isso mantém a
  feature **livre do provedor de mapas**, que segue PENDENTE (BORA-8).
- **A cidade do local vem do próprio endereço.** A decisão de "cidade do usuário"
  (`RN-PLAT-006`, BORA-23) é da descoberta, não do cadastro, e não bloqueia esta feature.
- **A aprovação de reivindicação acontece por um caminho de operação da plataforma**, usado
  pelo Ícaro. Não é painel de administração completo — é a tela mínima para aprovar e
  recusar, coerente com "aprovação manual" da Fase 1.
- **Autenticação é reaproveitada da spec 001** — conta única multi-papel, sem tela nova de
  login.
- **O aviso de resultado da reivindicação (FR-021, FR-023) vai por e-mail**, reaproveitando
  o provedor já configurado na spec 001. Push segue PENDENTE (BORA-6) e **não** é dependência
  desta feature — se fosse, a spec voltaria a travar numa decisão que não é dela.
- **A tela desta feature depende da fundação** (kit de UI com a régua embutida, tipografia
  e papéis semânticos de cor). Sem isso não há contraste AA verificável nem alvo de 44px por
  padrão. Por isso a fundação é **fase bloqueante**, não história — ver abaixo.
- **A identidade visual segue PENDENTE** (`brand.md`, BORA-25). A feature adota os papéis
  semânticos de cor definidos na fundação; a troca dos valores finais não deve exigir mexer
  em tela, porque **nenhuma tela escreve cor literal**.

---

## Nota de sequenciamento — a fundação é fase, não história

O recorte acordado em 2026-09-06 manda para uma **Phase 2 — Foundational (Blocking
Prerequisites)** do `tasks-template.md` — que bloqueia todas as histórias — o seguinte:

1. **O portão de conformidade de tela primeiro** (camada 4 do `design-system.md`, decisão
   D6/D4). Rodá-lo contra as telas já validadas da spec 001 é **o teste do próprio portão**:
   se não acusar nada, o portão é fraco, e isso se descobre no dia um.
2. **Kit de UI com a régua embutida no componente** — hoje o `Button` do shadcn é 32px e
   cada chamada corrige na mão com `min-h-11`, o que quebra em silêncio no dia em que
   alguém esquecer.
3. **Tipografia e papéis semânticos de cor** — as duas lacunas abertas do `design-system`.
4. **Shell de consumo** (camada 2).
5. **Retrofit das telas de conta da spec 001** para o kit novo.
6. **Modelo e migrations de local.**

> **Registrado de propósito:** o assistente ia propor a fundação **como P1**. O
> `spec-template.md` não permite — cada história tem de entregar, sozinha, um MVP viável
> com valor a usuário, e fundação não entrega. A fase Foundational faz o mesmo trabalho com
> melhor garantia: o template afirma *"No user story work can begin until this phase is
> complete"*. Isso torna a decisão D6 estruturalmente segura, em vez de dependente de
> disciplina.

**A P1 não precisa do shell de gestão completo.** A `D10` prevê Agenda + Perfil, mas
*Agenda* só faz sentido quando houver eventos — outra spec. Aqui o shell de gestão nasce
só com o Perfil.
