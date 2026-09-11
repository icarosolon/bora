# Domínio — Locais (bares e restaurantes)

ID: `RN-LOCAL-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-LOCAL-001 — Cadastro self-service e gestão do próprio perfil

O estabelecimento se cadastra sozinho e gerencia o próprio perfil (fotos/logo, descrição,
telefone, endereço, categorias, Instagram). Só contas com papel de gestor vinculado ao
local editam o local.

**Qualquer conta pode CRIAR o perfil de um local** (decidido pelo Ícaro em 2026-09-03).
Criar e controlar são coisas separadas: o perfil nasce **não reivindicado** e só passa a
ser controlado por alguém pela reivindicação (`RN-LOCAL-005`). O motivo do corte é o dano
real: perfil errado é constrangimento corrigível, **evento falso faz a pessoa atravessar a
cidade para nada** — então a porta da verificação fica na **publicação**, não no cadastro.
Assim o catálogo enche na velocidade das pessoas, e não na velocidade da verificação.

**O perfil não reivindicado é propositalmente magro** (ratificado pelo Ícaro em
2026-09-06): nome, endereço, categoria e telefone, e nada além — **sem fotos, descrição ou
Instagram**. Encolhe a superfície de vandalismo, evita que um estranho tenha controle
editorial sobre o bar de outro, e faz da riqueza do perfil (`RN-LOCAL-003`) um **prêmio da
reivindicação**, não o estado inicial.

Nota: o perfil magro sozinho é **ambíguo** — página pelada tanto pode significar "ninguém
confirmou este bar" quanto "o dono é desleixado". Quem desfaz a ambiguidade é o rótulo de
estado da `RN-LOCAL-005`.

---

## RN-LOCAL-002 — Categorias de local

Todo local tem uma ou mais categorias (ex.: bar, restaurante, choperia, petiscaria). A
lista de categorias é gerida pela plataforma (dado, não hardcode) e alimenta filtros e
personalização (`RN-DESC-001`, `RN-DESC-002`).

**Lista inicial, fechada pelo Ícaro em 2026-09-05** (fecha a BORA-21):

| Categoria |
|---|
| **Bar** |
| **Restaurante** |
| **Casa de shows** |

**Sem limite de categorias por local** (fecha a BORA-51): o local marca quantas das três
fizerem sentido. Com apenas três opções, marcar todas já é o teto e o filtro continua
separando o restaurante puro do bar puro — um limite seria regra a escrever, testar e
explicar para um problema que a lista curta já resolve.

**O que ficou de fora, e por quê** — o critério foi *tipo de noite*, não tipo de comida ou
de bebida:

- **Choperia e petiscaria** → são **bar**. Ninguém escolhe "hoje quero petiscaria, não
  bar"; categoria é botão de filtro, e filtro que não exclui nada é decoração.
- **Espetaria e churrascaria** → são **restaurante**, pela mesma régua.
- **Sorveteria, café e afins** → não entram: só é categoria o lugar onde **cabe música ao
  vivo**. O Bora não é guia de restaurante.

**Sobreposição é normal e esperada:** "tem muito restaurante que é bar e restaurante
também" (Ícaro). É o que o "uma ou mais" acima significa na prática.

**Por que a lista é curta de propósito:** categoria é dado, então **acrescentar depois é
trivial**, enquanto fundir ou dividir uma categoria depois que centenas de locais já se
marcaram é migração e re-etiquetagem. Errar para menos é o erro barato.

**Duas ressalvas honestas, registradas para quem reler:**

1. **A lista foi estimada, não medida.** A contagem em campo das casas com música ao vivo
   em Juazeiro e Petrolina é a BORA-47 e **não foi feita**. Aceitável porque categoria é
   dado e é barato corrigir — mas não é conhecimento verificado do mercado.
2. **Opinião do assistente, não verificada:** com "bar" provavelmente perto de 80% do
   catálogo, o filtro por categoria vai filtrar pouco, e é o **gênero musical** que deve
   fazer o trabalho pesado da descoberta. Se isso se confirmar, o cuidado maior pertence à
   BORA-19 (gêneros) e à `RN-DESC-001`, não a esta regra. Revisitar quando a spec do feed
   chegar.

---

## RN-LOCAL-003 — Perfil público com contato e localização

O perfil público exibe: nome, imagem, descrição, categorias, telefone (clique-para-ligar),
endereço completo com geolocalização (alimenta a rota — `RN-DESC-004`), Instagram, likes e
avaliações (`RN-AVAL-001`), e a agenda de eventos do local.

---

## RN-LOCAL-004 — Um local, um perfil

Cada estabelecimento físico tem exatamente um perfil. Duplicata identificada é unificada
(histórico preservado — `RN-PLAT-005`), nunca apagada.

**Quem identifica duplicata, e quando** (decidido pelo Ícaro em 2026-09-08): **o sistema
avisa antes de criar**, quando já existe local parecido **no mesmo bairro**, mostrando o que
encontrou — e quem cadastra decide se é o mesmo lugar. O critério de "parecido" pode ser
grosseiro e ainda pegar a maioria dos casos. O aviso **não impede** a criação: perfil
repetido criado mesmo assim continua sendo caso de unificação manual; a prevenção reduz o
volume, não o elimina.

Por que na criação e não depois: **unificar é caro por causa do Princípio X.** Não se apaga
o perfil repetido — histórico, avaliações e reivindicações precisam ser preservados e
costurados. Prevenir custa um aviso; consertar custa uma migração de dados com histórico.

**Rede ou franquia = um perfil por unidade física** (confirmado pelo Ícaro em 2026-09-03,
fecha a BORA-20). **Não existe entidade "rede"** na Fase 1: as unidades são perfis
independentes, sem nível acima delas.

Por que não um perfil só com vários endereços: quebraria três regras já escritas — o "como
chegar" não saberia para qual endereço ir (`RN-DESC-004`), o evento perderia a unidade a que
pertence (`RN-EVENTO-001`) e a `RN-LOCAL-003` amarra endereço único à rota. E, do lado do
rolezeiro, ver as unidades separadas é o comportamento **correto**: ele quer a que está
perto dele.

**Um gestor pode gerir vários locais.** O vínculo gestor↔local é **N:N** — decorre do
Princípio I (uma pessoa, uma conta, papéis vinculados): o dono da rede gerencia as unidades
com a mesma conta, nunca com contas paralelas. Escrito aqui porque implementar o vínculo
como um-para-um passaria despercebido até a primeira rede aparecer.

**Unidades se desambiguam pelo bairro, sem campo novo** (ratificado pelo Ícaro em
2026-09-08). O endereço completo já é obrigatório (`RN-LOCAL-003`) e o bairro está nele,
então as listagens exibem "Bar do Zé · Centro" a partir de dado que já existe — sem
acrescentar campo ao formulário (a régua manda formulário mínimo) e sem depender de o gestor
lembrar de desambiguar. Vale para **todo o catálogo**, não só para redes: "onde tem rolê
hoje" é pergunta geográfica, e o bairro na listagem é informação útil sempre. Se duas
unidades caírem no mesmo bairro — raro —, o gestor desambigua no próprio nome.

**Consequência de Fase 2, registrada para não virar surpresa:** sem entidade "rede", o plano
pago é **por perfil** — uma rede de três unidades pagaria três planos. Isso é assunto da
BORA-4 (preço dos planos), não desta regra. Tecnicamente não custa adiar: agrupar perfis
numa rede depois é acrescentar um vínculo opcional, não remodelar.

---

## RN-LOCAL-005 — Reivindicação de perfil de local

Decidida pelo Ícaro em 2026-09-03 (fecha a BORA-22). Separa **criar** de **controlar**
(`RN-LOCAL-001`).

**Estados do perfil.** Todo local está em um de dois estados:

- **Não reivindicado** — criado por qualquer conta. Aparece no catálogo e na busca, mas
  **não publica evento**.
- **Reivindicado** — tem gestor vinculado e verificado. Só neste estado o local publica
  evento (`RN-EVENTO-001`).

**Método de verificação — Fase 1: aprovação manual da plataforma.** A reivindicação é
aprovada à mão, coerente com a implantação assistida já assumida em
`docs/product/vision.md`. **Quem aprova** é conta com o papel de operação da `RN-PLAT-007`,
que **não se autoatribui**: até essa regra ser escrita, em 2026-09-10, a aprovação manual
pressupunha um autorizador que o catálogo nunca definia. Escolhido também por sequência: os métodos automáticos dependem
de provedor de SMS/voz que segue PENDENTE (BORA-6), e adotá-los agora reabriria aquela
decisão e voltaria a travar a spec de cadastro de local.

Descartados para a Fase 1, com o motivo registrado:

- **Código no telefone do local** — sinal forte e escalável, mas depende da BORA-6; e boa
  parte dos bares usa celular pessoal ou só WhatsApp. É o sucessor natural.
- **Documento (CNPJ, contrato social, alvará)** — fricção que mata o self-service, guarda
  de documento é dado sensível e passivo de LGPD (Princípio III), e **boa parte do bar
  pequeno em Juazeiro e Petrolina opera como MEI ou informal**: exigir documento excluiria
  parte do mercado-alvo.

**A política é do domínio; o método é parâmetro.** Trocar aprovação manual por método
automático não pode exigir mudança de regra — só de configuração e de adaptador.

**Transferência preserva histórico** (Princípio X). Quando o gestor legítimo reivindica um
perfil criado por terceiro, o perfil é **transferido, nunca recriado**: avaliações,
comentários e histórico de eventos continuam ligados ao mesmo local. Quem criou perde o
controle editorial; o registro de que criou não é apagado.

**Auditoria** (Princípio VIII): ficam registrados quem criou o perfil, quem reivindicou,
quem aprovou, quando, e **por qual método a reivindicação foi aprovada** — este último
porque o método vai mudar, e disputa entre um dono e quem cadastrou antes dele é cenário
certo, não hipotético.

**Quem cadastra pode se identificar como gestor no mesmo ato** (decidido pelo Ícaro em
2026-09-08): o formulário de cadastro oferece **"sou eu que gerencio este bar"**, e marcar
isso **abre o pedido de reivindicação junto com a criação**. A **aprovação continua manual**
e continua sendo ato separado — a regra acima não muda; o que some é o segundo pedido
exigido de quem já se identificou, no momento de maior intenção.

Isso **não** dá controle a quem cria: o perfil ainda nasce não reivindicado e só muda de
estado com aprovação. Dar controle imediato a quem cadastra foi descartado por contradizer
esta regra e reabrir a decisão da BORA-22 — a porta da verificação deixaria de ser a
publicação para quem chegasse primeiro.

**Os dois estados são visíveis para o rolezeiro** (decidido pelo Ícaro em 2026-09-06):

| Estado | O que a tela mostra |
|---|---|
| **Reivindicado** | selo com ícone **+ o texto "Perfil do estabelecimento"** |
| **Não reivindicado** | linha curta: *"Este perfil ainda não é gerenciado pelo estabelecimento — as informações podem estar incompletas."* |

**Por que "Perfil do estabelecimento" e não "Verificado".** O rótulo tem de dizer o que o
processo **de fato** verificou. Na Fase 1 a verificação é aprovação manual, **sem
documento** — "verificado", no sentido que WhatsApp e Instagram consagraram, promete
identidade conferida contra documento, o que aqui não acontece. Rótulo que promete demais
**amplifica o estrago** quando alguém passa pela aprovação: a pessoa confia mais justamente
no caso em que deveria confiar menos. E, na prática, o que o rolezeiro quer saber não é "esta
identidade foi verificada" — é **"esta informação veio do bar?"**, que é exatamente o que o
processo entrega. Revisitar a palavra quando o método endurecer (BORA-49).

**Selo sozinho não vale.** O `ux-requirements.md` é vinculante: *"ícone nunca sozinho para
ação importante"* e *"informação nunca transmitida só por cor"*. O selo carrega rótulo de
texto — o que, de quebra, é o que o torna honesto.

**Observação registrada (opinião, não medida):** o valor de sinal dos dois rótulos
**inverte com o tempo**. No começo quase todo perfil é não reivindicado, então o selo é raro
e informa muito, e o aviso está em toda parte. Quando a maioria estiver reivindicada, é o
aviso que passa a informar. Os dois existem desde o início; o que deve mudar depois é o peso
visual de cada um.

PENDENTE: quando a aprovação manual deixar de escalar, qual método automático a substitui e
qual o gatilho da troca? Depende da BORA-6 (provedor). Ver BORA-49. **Quando isso mudar,
revisar também o rótulo acima** — método mais forte pode passar a comportar a palavra
"verificado".

**O pedido carrega evidência** (decidido pelo Ícaro em 2026-09-09). Aprovação manual é
**julgamento**, e julgamento precisa de matéria: o pedido traz **nome de quem pede**,
**função no estabelecimento**, **melhor horário para contato** e **a quem perguntar**. A
verificação é ligar para o **telefone que já consta no perfil público** — nunca para um
número informado pelo próprio solicitante, que provaria apenas que ele tem telefone.

Estes campos e não outros porque o sucessor automático (BORA-49) é **código no telefone do
local**: a evidência de hoje é **o mesmo sinal, feito à mão**, e sobrevive à troca de método
em vez de virar dado órfão.

**Limite declarado:** se o telefone do perfil estiver errado ou ninguém atender, o caminho
não conclui. Na Fase 1 não trava — a aprovação é manual e cabe confirmar por fora, com o
registro dizendo que foi assim. Quando automatizar, vira bloqueio e precisa de saída própria.

**Recusa e pedidos concorrentes** (decidido pelo Ícaro em 2026-09-08):

- **Recusar registra o motivo e avisa** o solicitante, com o motivo em linguagem simples e
  um **caminho para falar com a plataforma**; ele pode pedir de novo depois de resolver.
  Pedido que some da fila sem resposta é o silêncio que o `ux-requirements.md` proíbe — e,
  na Fase 1 assistida, a recusa é o único momento em que a plataforma diz "não" a um bar que
  o Ícaro conhece pessoalmente; ela precisa virar conversa, não porta fechada.
- **Segundo pedido para local com pedido pendente é aceito.** Os pedidos ficam todos
  pendentes e são apresentados **juntos** a quem decide. Perder o registro de quem pediu
  antes destruiria o dado que o Princípio VIII manda guardar justamente para arbitrar
  disputa.
- **Aprovar um pedido encerra os demais** daquele local como recusados, com motivo
  registrado e **aviso a cada solicitante** — decorre dos dois itens acima.

PENDENTE: **critério de desempate** entre dois pedidos plausíveis para o mesmo local, e o
que acontece com quem perde além do aviso. O comportamento mínimo acima já está definido; o
que falta é a regra de arbitragem. Ver BORA-50.
