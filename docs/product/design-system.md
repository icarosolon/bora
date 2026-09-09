# Bora — Sistema de Design (o "template" do projeto)

Status: **decisões travadas; norma ainda não escrita.** Este documento registra o que foi
decidido na sessão de **2026-09-01** sobre o que o Ícaro chama de "template" do projeto.
Ele **ainda não é vinculante** — vira norma quando as lacunas da seção *Em aberto* forem
fechadas e o Ícaro ratificar. A régua vinculante hoje continua sendo
`docs/product/ux-requirements.md`.

## Como ler este documento

Cada decisão traz a **origem**, porque isso muda o peso dela quando alguém reler daqui a
meses sem o contexto da conversa:

- **Ícaro** — decisão do dono do produto. Não se muda sozinho.
- **Proposta** — derivação do assistente, apresentada ao Ícaro e **ainda não objetada**.
  Vale até ele objetar; não tem o mesmo peso de uma decisão dele.
- **Derivada** — não é escolha de ninguém: decorre de regra já vinculante. Discutir isso é
  discutir a regra de origem, não este documento.

E, dentro do texto: **fato** é o que foi verificado no repositório (com caminho de
arquivo); **opinião** é julgamento do assistente; **falta medir** é o que ninguém mediu
ainda. A separação é exigência do `development-workflow.md` §7.

---

## D1 — "Template" são quatro camadas, não uma (origem: Ícaro)

A palavra era ambígua e foi desambiguada antes de qualquer proposta. O template do Bora é
o conjunto de **quatro camadas distintas**, que se resolvem em ordens diferentes:

| # | Camada | O que é |
|---|---|---|
| 1 | **Kit de UI / tema** | Tokens (cor, tipografia, espaçamento, raio) e componentes base com a régua de acessibilidade **embutida no componente**, não repetida na chamada |
| 2 | **Shell / moldura** | O esqueleto de página: quantas molduras existem, onde mora a navegação, o caminho de volta e a troca de papel |
| 3 | **Receitas de tela** | Conjunto pequeno e **fechado** de arquétipos, cada um já trazendo carregando/vazio/erro/sucesso. A lista definitiva está na **D13** — a enumeração provisória desta linha (que incluía "painel") foi corrigida ao abrir a camada |
| 4 | **Contrato + portão** | O documento normativo e o **teste automatizado** que reprova quem não cumpre |

## D2 — A camada de shell entra no escopo (origem: Ícaro)

O shell tinha ficado de fora da definição inicial e foi trazido de volta depois de dois
casos concretos: o caminho de volta do rolezeiro (feed → evento → local → **como volta?**)
e a troca de papel do gestor. Sem shell decidido uma vez, cada receita de tela decide de
novo, e a resposta muda de tela para tela.

**Fato:** o **E-016** foi um bug de shell puro — `AccountHeader` vive no layout raiz e
navegação client-side não remonta o layout, então a barra ficava congelada em "Entrar"
depois do login. Hoje o shell é implícito: um header solto em `web/src/app/layout.tsx` e
cada página se vira.

## D3 — Claro **e** escuro, seguindo o aparelho, sem alternador (origem: Ícaro)

O site respeita o `prefers-color-scheme` do aparelho e **não oferece controle de troca de
tema**. Zero interação e zero aprendizado — coerente com "ninguém aprende a usar o Bora";
quem quis escuro já configurou o celular uma vez.

Por que isso é decisão estrutural e não estética: no escuro a hierarquia se faz por
superfície e borda; no claro, por sombra — e sombra não funciona sobre fundo escuro. Virar
essa decisão depois **redesenha componentes**, não só os repinta.

**Discordância registrada:** o `brand.md` recomenda *dark-first* com a justificativa "o app
é usado à noite, em bar". O assistente **discorda da justificativa** (opinião): o rolezeiro
decide o rolê às 18h no sofá, o gestor publica de dia, e o catálogo público é link
compartilhado que abre em qualquer contexto. Quem decide claro ou escuro é a configuração
do aparelho, não a hora. A recomendação do `brand.md` de projetar bem no escuro continua
válida; a justificativa dela, não.

**Custo aceito:** dobra os pares de contraste a validar e dobra a validação visual de cada
tela.

**Consequência técnica (fato):** `web/src/app/globals.css` define hoje
`@custom-variant dark (&:is(.dark *))` — o escuro é por **classe**, e **nada no código
aplica essa classe**. "Seguir o aparelho" exige que isso passe a responder a
`prefers-color-scheme`.

**Alerta registrado:** um alternador manual de tema seria a repetição do **E-015** (ler
`localStorage` durante a renderização, árvore hidrata divergente, tela fica errada em
silêncio). A decisão de não ter alternador elimina essa superfície.

## D4 — Ordem das camadas: 4 → (1 ‖ 2) → 3 (origem: proposta)

O **contrato vem primeiro** porque é o único que pode ser verificado imediatamente: as
telas da spec 001 existem e estão validadas, então rodar o portão contra elas é o **teste
do próprio portão**. Se não acusar nada, o portão é fraco e isso se descobre no dia um.

É a lição literal do **E-012** no error-log: *"rede de proteção não verificada é rede que
dá falsa confiança — pior que não ter"*. Contrato escrito **depois** do kit nasce moldado
ao kit e não reprova ninguém.

Camadas 1 e 2 não se bloqueiam (o kit precisa de tokens e régua; o shell precisa de
decisão de produto). A camada 3 depende das duas.

> **Ressalva, por causa da D6:** a *escrita* do contrato continua sendo a primeira coisa,
> mas a **verificação** dele foi adiada para dentro da spec 002. Registrado aqui porque é
> o tipo de nuance que some entre sessões e volta como "a gente tinha decidido o contrário".

## D5 — Duas molduras: consumo e gestão (origem: Ícaro)

- **Consumo** — o rolezeiro, logado ou não. Descobrir, buscar, salvar.
- **Gestão** — o gestor de estabelecimento **e** o artista, compartilhando a mesma moldura.
  Publicar e gerenciar.

Descobrir e publicar são tarefas genuinamente diferentes; um menu que serve as duas vira
menu longo e genérico — o pior resultado possível para o público do Princípio XII.

**Erro comum desarmado aqui:** a fronteira do **ADR-0003** (servidor/cliente) **não é uma
fronteira visual**. São ortogonais. A mesma moldura pode ser renderizada no servidor quando
anônima e no cliente quando logada. Se a decisão de renderização virar decisão de moldura,
quem chega por link compartilhado e depois entra **vê o site mudar de forma**.

## D6 — Norma vai por documento; kit, shell e receitas vão dentro da spec 002 (origem: Ícaro)

### A tensão de processo encontrada

**Fatos:**

- `.claude/skills/spec-check/SKILL.md`, passo 6, marca como **Bloqueante** toda spec sem
  tela declarada e ação principal, e diz: *"Feature 'só de backend' não existe neste
  projeto"*. Qualquer Bloqueante ⇒ veredito **NÃO**.
- `.specify/templates/spec-template.md` exige a seção "Tela e Experiência" com telas
  entregues, ação principal e estados de carregando/vazio/erro/sucesso.
- Constituição, Princípio XI: proibido iniciar a próxima funcionalidade antes da validação
  visual da atual.
- `api/routes/api.php` e as migrations cobrem **só autenticação**. Não existe evento, local
  nem artista — nem tabela, nem rota.

**Conclusão:** um template não tem tela própria, não tem ação principal e não tem estado
vazio. Ele **não passa no próprio portão do projeto** — e o portão não está errado: foi
desenhado para features verticais, e o template é o primeiro trabalho do Bora que não é
feature vertical. A ideia de dar a ele uma "tela-vitrine" foi levantada e **descartada
depois da verificação**: não existe tela candidata, porque a única home real (o feed do
dia) depende de uma API que não existe — seria a 002 inteira, não uma vitrine.

### A saída, com precedente no próprio repositório

`docs/architecture/naming-conventions.md` é regra **vinculante**, adotada em 2026-08-31 e
aplicada retroativamente à spec 001, e **nunca passou pelo Spec Kit**. Foi documento
normativo, não feature. É esse o formato.

| O quê | Vai por onde |
|---|---|
| Camada 4 (contrato) + papéis de token + D3/D5 | Documento vinculante + ADR — não tem tela, não precisa de tela |
| O portão automatizado (o teste) | Junto da spec 002 (ver abaixo) |
| Camadas 1, 2 e 3 (kit, shell, receitas) | **Dentro da spec 002**, que tem telas de verdade |
| Conserto das telas da 001 | **Dentro da spec 002** |

### Consequências da escolha do Ícaro (retrofit dentro da 002)

O Ícaro escolheu levar o conserto da 001 para dentro da 002, contra a recomendação do
assistente (que era uma rodada de fundação antes). Decorre disso:

1. **O portão tem que viajar junto com a 002, não antes.** Entrando em `main` antes do
   conserto, a suíte fica vermelha por semanas — e suíte vermelha crônica mata o portão.
2. **O documento normativo entra sem dente até a 002.** Aceitável, **com prazo**: o portão
   tem que ser a **primeira** coisa da 002, não a última. Portão como último item nasce
   moldado ao que já foi construído.
3. **Risco registrado (opinião):** a 002 acumula API de eventos/locais + kit + shell +
   receitas + retrofit + portão. Escopo grande é onde a validação visual vira carimbo.
   **Mitigação:** usar o mecanismo que o `spec-template.md` já exige — User Stories
   priorizadas e *independently demonstrable* — e validar visualmente **por história**, não
   no fim da spec. A P1 tem que ser a história que força kit + shell + receita a existirem,
   com o retrofit da 001 como pré-requisito dela.

---

## Navegação

### D7 — Todo mundo aterrissa na moldura de consumo (origem: Ícaro)

Uma home só, previsível, sem estado persistido — e reforça o Princípio I: até o dono do bar
é rolezeiro. Custo aceito: o gestor paga um toque a mais, sempre.

### D8 — Seletor de moldura persistente no topo (origem: Ícaro — ratificada em 2026-09-02)

**A D7 só passa na régua acompanhada desta.** `ux-requirements.md` exige *"qualquer
conteúdo importante a no máximo ~3 toques da home"*. Com o seletor enterrado dentro de
"Conta", o dono do bar faz `home → Conta → Meu bar → Publicar evento` = **4 toques** —
reprova, e reprova exatamente para o lado de quem gera a receita, no cenário que a própria
premissa descreve (publicar evento pelo celular, no balcão).

Com seletor persistente no topo: `feed → [Meu bar] → Publicar evento` = **2 toques**.

Regra proposta:

- **Toggle direto** quando a conta tem **um** perfil de gestão (caso comum) — 1 toque.
- **Menu** quando tem mais de um (bar + artista) — 2 toques.
- **Não aparece** para conta que é só rolezeiro.
- O seletor é também o caminho de volta da gestão para o consumo.

Efeito colateral bom: sem "lembrar a última moldura", não há estado persistido — logo,
nenhuma superfície do E-015. A escolha do Ícaro saiu **mais barata tecnicamente** que a
recomendação do assistente.

### D9 — Barra de consumo com cinco itens (origem: Ícaro)

Barra **inferior** (estilo base, mobile-first literal), ícone **+ rótulo de texto**, alvo
≥ 44px:

| | Rótulo | Papel |
|---|---|---|
| 1 | **Hoje** | o feed "o que temos para hoje" — é a home |
| 2 | **Buscar** | busca + filtros por categoria de local e gênero musical |
| 3 | **Salvos** | locais e artistas salvos |
| 4 | **Dividir** | calculadora de divisão de conta |
| 5 | **Conta** | perfil, ajustes, sair |

A calculadora ganhou lugar de primeira classe porque a situação de uso pede 1 toque: mesa,
barulho, pressa. Custo aceito explicitamente: leva a barra a cinco itens e **obriga** a
declarar o comportamento de refluxo (D12).

**Suspeita registrada (opinião, não verificada):** `Hoje` e `Buscar` podem ser redundantes
se o feed já tiver filtros no topo — dois caminhos para o mesmo lugar, quando a régua pede
navegação **rasa**, não larga. É a candidata a corte se a barra apertar. Isso se descobre
com o Ícaro usando, não raciocinando.

### D10 — Moldura de gestão: Agenda + Perfil + ação da instância (origem: Ícaro — ratificada em 2026-09-02)

| | Rótulo | Bar | Artista |
|---|---|---|---|
| 1 | **Agenda** | meus eventos | meus shows + convites a confirmar |
| 2 | **Perfil** | perfil do estabelecimento | perfil do artista |

Mais o seletor do D8 no topo e **uma ação principal persistente na metade inferior**.

**Assimetria que precisa ser dita:** a ação principal **não é a mesma nos dois papéis**.
Pela `RN-EVENTO-002`, quem cria evento é o estabelecimento; o artista só confirma. Logo o
bar tem `+ Publicar evento` (com rótulo de texto — "+" sozinho é proibido pela régua) e o
artista **não tem ação de criação nenhuma**: o trabalho dele é reativo, dentro da Agenda.
A ação persistente é propriedade da **instância** da moldura, não do tipo — isso não reabre
a D5.

### D11 — A partir de 768px a barra vira trilho lateral (origem: Ícaro — ratificada em 2026-09-02)

Mesmos itens, mesma ordem, mesmos rótulos. É a leitura literal da frase do
`ux-requirements.md`: o conteúdo ganha a largura (**mostra mais**) e os controles
**consolidam numa borda** em vez de se espalharem. Estilo base é a barra inferior; a media
query só amplia.

### D12 — Refluxo dirigido pelo conteúdo, nunca por media query de largura (origem: derivada)

O `ux-requirements.md` pede duas coisas que parecem a mesma e **não são**:

| Cenário | O que acontece a 200% |
|---|---|
| **Zoom do navegador** | O viewport de 360px vira ~180px de layout; a fonte continua 16px |
| **Fonte do sistema** (iOS/Android) | O viewport **continua 360px**; a fonte vira 32px |

Os dois quebram uma barra de cinco itens com rótulo. **E o segundo não dispara nenhuma
media query de largura** — a barra quebra sem que nenhum teste de 360/1280 perceba. É o
perfil de bug do error-log: passa nos testes, aparece no aparelho de outra pessoa.

Portanto:

1. O refluxo é por `flex-wrap` ou container query — **nunca** por `@media` de largura.
2. **O portão (camada 4) precisa de uma asserção a mais: rodar também com fonte ampliada**,
   não só com largura reduzida. Sem isso o portão tem um ponto cego conhecido.
3. **Refluxo para duas linhas** (ratificado pelo Ícaro em 2026-09-09), mantendo **ícone +
   rótulo em todos os itens**. Custo aceito: a 200% a barra passa a ocupar perto de um terço
   da altura. Esconder itens atrás de "Mais" reintroduz o problema que a barra existia para
   resolver; quem está a 200% aceitou ver menos conteúdo, **não** aceitou perder o rótulo.

   **Emenda feita antes da ratificação:** a proposta original dizia "3 + 2". Isso é
   especificar demais antes de medir — a divisão sai da **medição** dos rótulos na fonte
   real, que continua pendente. A regra é "duas linhas com rótulo em todos"; o corte é
   consequência da medida, não escolha.

---

## Camada 3 — Receitas de tela

Aberta em 2026-09-02.

### D13 — Três arquétipos, lista fechada (origem: Ícaro — corrige a D1, ratificada emendada em 2026-09-09)

A D1 enumerou **cinco** arquétipos, incluindo "painel de gestão". Ao mapear as telas reais
do `vision.md` contra as molduras da D5, o painel **se dissolve**: a Agenda do gestor é uma
lista, "Publicar evento" é um formulário, o perfil do estabelecimento é um formulário. Não
sobra nada de próprio. **"Painel" era moldura (D10), não arquétipo** — estava contado duas
vezes.

| Arquétipo | Telas que ele cobre |
|---|---|
| **Lista** | Hoje (feed), Buscar, Salvos, Agenda do gestor, convites do artista, aprovar reivindicações |
| **Detalhe** | evento, perfil de local, perfil de artista |
| **Formulário** | entrar, criar conta, senha, unir contas, cadastrar local, pedir reivindicação, publicar evento, editar perfil, minha conta |

A lista é **fechada** de propósito: tela nova ou encaixa num destes, ou dispara uma decisão
explícita de criar o próximo. Sem isso, "arquétipo" vira etiqueta e não restringe nada.

**São três, não quatro** (ratificado pelo Ícaro em 2026-09-09, emendando a proposta
original). A proposta trazia um quarto — **"Ferramenta"** — que cobria **uma única tela**, a
calculadora de divisão de conta, cujas regras estão quase todas PENDENTE na `RN-CONTA-001`
(BORA-5). **Arquétipo com uma instância que ainda não existe é categoria vazia**, e
categoria vazia costuma ser preenchida errado por quem chega depois.

"Ferramenta" entra **quando a calculadora tiver regras** — e é exatamente para isso que a
lista fechada serve: a tela nova dispara a decisão no momento em que o problema está na mão,
não anos antes. A **D18** espera junto.

> A **Lista tem duas variantes** (pública e privada, `D15`), e isso **não** a torna dois
> arquétipos: elas compartilham quase todo o layout, e a diferença que importa — a pública
> não carrega estado que dependa de quem olha — já está escrita como **invariante
> testável**, que é onde ela tem dente.

**O que a receita acrescenta** não são os estados de carregando/vazio/erro/sucesso — o
`spec-template.md` já os cobra em toda spec. A receita acrescenta o que hoje cada tela
decide sozinha: onde fica a ação principal, qual é o caminho de volta, o que o desktop faz
com o espaço extra, e de que lado da fronteira do ADR-0003 a tela nasce.

**A fronteira é derivada, não escolhida.** A Constituição enumera o que é servidor:
*"evento, local, artista, feed do dia"*. Buscar não está na lista, nem Salvos, nem os
formulários, nem a ferramenta. Logo: `Detalhe` e o feed `Hoje` são servidor; todo o resto é
cliente.

### D14 — Salvar acontece só na tela de detalhe (origem: Ícaro)

O feed e a busca **mostram e levam**; salvar acontece no perfil do local/artista e no
detalhe do evento. É a leitura literal do exemplo do `ux-requirements.md` (*"Toque no ♥ de
um local para salvá-lo aqui"* — falando do local, não do card).

**Correção registrada:** ao apresentar a opção, o assistente afirmou que ela faria "sumir a
armadilha inteira" de hidratação. **Impreciso.** A armadilha não some — ela **se concentra**:
o ♥ sai do feed (onde seriam N cards, cada um um componente de cliente) e vira **um**
controle na tela de detalhe, que também é renderizada no servidor. Muito mais barato, mas
não zero — e a receita do Detalhe tem que resolvê-lo por construção.

Custo aceito: salvar passa a custar um toque a mais.

### D15 — A Lista tem duas variantes, e a pública não carrega estado por usuário (origem: derivada da D14)

| Variante | Telas | Fronteira | Estado por usuário |
|---|---|---|---|
| **Lista pública** | Hoje, Buscar | Hoje = servidor; Buscar = cliente | **Nenhum** |
| **Lista privada** | Salvos, Agenda, convites | cliente, atrás de login | É só isso que ela tem |

**Invariante com dente, testável pelo portão:** lista pública não contém nenhum controle
cujo estado dependa de quem está olhando. O servidor não sabe quem olha — o token é do
cliente e nunca cruza para lá (ADR-0003). Controle que finge saber, mente.

**Achado lateral — evita um erro copiado de SPA:** a Lista pública renderizada no servidor
**não tem estado de carregando na primeira pintura**; ela chega pronta. O carregando dela
existe só para interação (filtrar, carregar mais). Especificar esqueleto de carregamento
para a tela `Hoje` seria inventar um estado que a tela não tem.

### D16 — Ação principal de cada Detalhe (origem: Ícaro)

A régua exige **uma** ação principal por tela, e o Detalhe é a tela mais cheia de ações do
produto. Resolvido por entidade:

| Tela | Ação principal | Em segundo plano |
|---|---|---|
| **Detalhe do evento** | **Como chegar** | compartilhar, salvar, ver o artista, comentários |
| **Perfil do local** | **Como chegar** | ligar (clique-para-ligar), salvar, Instagram, agenda, avaliações |
| **Perfil do artista** | **Salvar para acompanhar** | ver agenda, Instagram, avaliações |

**Fato que desarma um alarme falso:** o assistente ia registrar que a ação principal de
duas dessas três telas ficaria bloqueada pelo provedor de mapas PENDENTE. **Foi verificar e
não é o caso** — a `RN-DESC-004` já decide: *"Fase 1: link para o app de mapas do usuário
(Google Maps/Waze) resolve sem custo de API"*. A decisão de provedor só existe para mapa
embutido, que não é Fase 1.

**Nuance que sobra (não é da receita):** a `RN-LOCAL-003` diz "endereço completo com
geolocalização (alimenta a rota)". Link com o endereço em texto não precisa de
geocodificação; link com coordenadas precisa. Isso é decisão do **cadastro de local**
(spec 002), não do Detalhe.

**Consequência técnica notável:** as duas telas cuja ação principal é "como chegar" têm
ação que **funciona antes de hidratar**, porque é um link nativo (`<a href>`) — imune à
janela do E-012. A do artista **não**: a ação principal dela é justamente o controle cujo
estado depende de saber quem está olhando, numa tela renderizada no servidor. É a única
tela do produto com essa combinação, e por isso o estado "ainda não sei" do ♥ precisa ser
um estado **desenhado**, com rótulo, sob a disciplina do `useHydrated` — não uma ausência.

**Observação de produto (opinião):** a ação principal das duas telas mais importantes do
catálogo **manda a pessoa para fora do Bora**. Está correto — é o que ela foi fazer ali —
mas significa que o momento de maior intenção é o momento da saída. Isso dá peso a
compartilhar e salvar como secundários bem-feitos, e é um dado para a Fase 2: o
estabelecimento paga por exposição, e a plataforma não vê o que acontece depois do toque.

### D19 — "Salvar" e "seguir" são duas ações distintas (origem: Ícaro — emenda a D16)

Decidido em 2026-09-02, contra a recomendação do assistente (que era uma ação só + uma
preferência de notificação em Conta). **Salvar** é marcador silencioso; **seguir** avisa.
Vale para locais e artistas.

> Isto resolve o PENDENTE da `RN-DESC-003`. **A decisão precisa ser escrita no catálogo de
> domínio** (`docs/domain/descoberta.md`) pela skill `domain-rule` — não vale como regra
> por estar registrada aqui.

**Ação principal do artista é Seguir** (ratificado pelo Ícaro em 2026-09-02). Ele respondeu
"salvar para **acompanhar**", e acompanhar é o que "seguir" significa; a leitura foi
apresentada como tal e confirmada. Salvar desce para secundária.

**Três consequências, e nenhuma é opinião:**

1. **Dois verbos parecidos exigem microtexto, não só rótulo.** A régua já proíbe ícone
   sozinho — mas aqui nem dois rótulos bastam: "Salvar" e "Seguir" lado a lado não ensinam
   a diferença a quem usa o produto de vez em quando. Cada um precisa de uma linha curta
   ("guarda na sua lista" / "avisa quando tiver show novo"). Sem isso, a tela não se explica
   sozinha, e o `ux-requirements.md` diz que tela que não se explica falhou.
2. **O perfil do local vira a tela mais cheia do produto:** como chegar (principal), ligar,
   salvar, seguir, Instagram, agenda e avaliações. Não é motivo para reverter — é motivo
   para a receita do Detalhe declarar hierarquia visual explícita, não só "uma principal".
3. **"Seguindo" ficou sem casa.** A D9 deu vaga na barra para **Salvos**. Com duas ações
   existe também um conjunto "Seguindo", que não tem destino — e o produto já dividia salvos
   entre locais e artistas, o que ameaça virar uma grade de 2×2.
   **Decidido** (Ícaro, 2026-09-02): uma lista só em `Salvos`, cada item marcado com um
   selo **de texto** ("seguindo") quando for o caso — informação nunca só por cor. Evita
   quatro abas. Alternativa descartada: duas abas (Salvos / Seguindo).

### D20 — Hierarquia do Detalhe, e a barra de navegação dá lugar à ação principal (origem: Ícaro — emenda D9 e D11)

Decidido em 2026-09-02. Fecha o último buraco da camada 3.

**O problema encolheu antes de ser resolvido.** As "sete coisas" do perfil do local não são
sete ações do mesmo tipo — agenda e avaliações nunca foram ação, são **conteúdo**:

| Nível | O quê | No perfil do local |
|---|---|---|
| **1 — ação principal** | uma, verbo, metade inferior | **Como chegar** |
| **2 — ações diretas** | fileira de ícone + rótulo, ≥44px | ligar, salvar, seguir, convidar |
| **3 — conteúdo** | seções que se rola, não botões | descrição, agenda, avaliações, Instagram |

Sobra "um botão + uma fileira de quatro", não sete controles concorrendo.

**O conflito com a camada 2, e a decisão.** O Detalhe é alcançado a partir da barra de cinco
itens (D9) — então uma ação principal fixa embaixo empilharia **duas barras** no rodapé. A
360px já é caro; com o refluxo de duas linhas a 200% (D12), come metade da tela.

**Decidido: no Detalhe a barra de navegação some** e dá lugar à ação principal. Reforça que
Detalhe é tela em que se **entrou**, não aba em que se **está**.

**Consequência 1 — o "voltar" passa de recomendável a OBRIGATÓRIO**, porque vira o único
caminho de saída.

**Consequência 2 — e esta é uma correção do próprio raciocínio que recomendou a opção:** o
Detalhe é justamente a tela que **chega por link compartilhado** (é o caso do SSR/SEO da
Constituição). Quem abre um evento pelo WhatsApp não tem histórico de navegação — e, sem a
barra, ficaria **preso**, sem caminho para o resto do produto, no primeiro contato com o
Bora. Portanto:

> **O "voltar" é link para destino nomeado, nunca `history.back()`.** "Voltar para Hoje",
> "Voltar para o Caetano". Chegada fria tem porta de entrada, e a régua já proibia depender
> do gesto do navegador.

**Consequência 3 — a regra vale só onde o espaço é escasso.** No desktop (≥768px, D11) o
**trilho lateral permanece** no Detalhe: o motivo de sumir é altura, e altura não falta a
1280. Sumir no desktop seria tirar navegação sem ganhar nada.

**Rótulo da ação de compartilhar: "Convidar"** (Ícaro, 2026-09-02). "Compartilhar" tem 12
caracteres e não cabe na fatia de 90px da fileira de quatro; "Chamar" — a expressão que ele
usou ao descrever a ação — colidiria com "Ligar", que está na mesma fileira. "Convidar" tem
8 caracteres, cabe, e é o que a pessoa está de fato fazendo.

### D17 — O padrão do Formulário é obrigatório; o conjunto de componentes cresce por demanda (origem: Ícaro — emenda a proposta original)

**Correção da proposta original, feita antes da ratificação.** Eu havia escrito que "a
receita do Formulário **já existe**". Exagerei: o que existe é `BaseForm` + `Field` +
`Alert` + `useHydrated`, e o **`Field` é campo de texto**. A spec 002 precisa de **seleção de
categoria** (múltipla escolha) já na P1, e de **área de texto** e **envio de foto** na P4.
Dizer que a receita já existe reivindicava cobertura que ela não tem — **o padrão existe; o
conjunto de componentes, não**.

O que fica ratificado (Ícaro, 2026-09-09):

- **O padrão é obrigatório.** Todo formulário do Bora usa `BaseForm` + `Alert` +
  `useHydrated`, com a disciplina de hidratação que resolveu E-012, E-013 e E-015 por
  construção. Isso não se reinventa por tela.
- **A fundação acrescenta só o que a P1 precisa:** a seleção de categoria (múltipla
  escolha). **Área de texto e envio de foto chegam na P4**, junto com o perfil rico que os
  usa.

**Por quê:** a fase Foundational já é a maior coisa da spec 002 e **bloqueia todas as
histórias** — engordá-la com dois componentes que só a última história usa atrasa tudo.
Mexer no kit depois é barato **desde que a régua esteja embutida no componente desde agora**,
que é justamente o que a fundação conserta.

**Risco aceito, e vale dizer:** componente que nasce dentro de uma história tende a nascer
sob pressa — foi assim que o `min-h-11` copiado na mão apareceu. A mitigação é o **portão**,
que passa a existir antes de qualquer história (D4/D6) e reprova alvo abaixo de 44px venha
ele de onde vier.

Regras próprias: enviar fica abaixo dos campos, na metade inferior; volta obrigatória com
rótulo de texto; no desktop vira **cartão centralizado**, e **não** duas colunas de campos
(isso espalharia controles, que é exatamente o que o `ux-requirements.md` proíbe).

**Marcado, não decidido:** "Publicar evento" é o único candidato a formulário de várias
etapas — a régua manda "um assunto por etapa" e ele tem data, hora, local, atração, valor e
links. Se é uma tela longa ou três curtas é assunto da spec 002, não da receita.

### D18 — A Ferramenta recebe política da API como parâmetro (origem: proposta — SUSPENSA com a D13)

> **Suspensa em 2026-09-09**, junto com o arquétipo "Ferramenta" (D13). O raciocínio abaixo
> continua válido e não se perde: ele volta quando a calculadora tiver regras
> (`RN-CONTA-001`, BORA-5). Não é decisão revogada — é decisão **prematura**, guardada
> inteira para o momento em que houver o que decidir.

A calculadora é a única tela **anônima, de cliente e sem chamada de rede para operar**.
Isso colide com a Constituição, que proíbe regra de negócio no `web/` — e o princípio está
**certo** aqui, não pedante: se a política de taxa e arredondamento morar só no site, o app
mobile teria que reimplementá-la.

Resolução, usando regra que o projeto já tem (*"política no domínio, parâmetro como dado,
nunca hardcoded"*): a **política** vive na API e chega ao front como **parâmetros** (taxa
padrão, regra de arredondamento, se couvert entra na divisão). O front faz apenas a
aritmética com os parâmetros recebidos. Nenhuma regra hardcoded no `web/`, nenhuma chamada
por tecla, e o app mobile recebe os mesmos parâmetros.

**Risco registrado:** a D9 deu vaga na barra de navegação para a tela **menos definida do
produto** — a `RN-CONTA-001` está quase toda PENDENTE (divisão simples ou por item? taxa e
couvert como campos? anônimo ou logado?). Ou essas pendências saem antes da 002, ou a
quinta vaga aponta para o vazio por um tempo. Não reverte a D9; não pode passar
despercebido.

---

## Camada 1 — Kit de UI e tema

Aberta em 2026-09-09. Fecha as duas lacunas que bloqueavam a construção de tela.

### D21 — Estrutura tipográfica: uma família web, dois pesos (origem: Ícaro)

**O corte é o mesmo das cores:** a **estrutura** se decide agora; o **nome da família** é
marca, e a marca está PENDENTE (BORA-25). Estrutura é quantas famílias, quantos pesos, e o
que a pessoa vê enquanto a fonte não chegou — tudo isso vale para qualquer família que o
redesenho escolher.

- **Uma família** para tudo (nada de par título/corpo), **dois pesos**: regular e negrito.
- Hospedada localmente pelo `next/font`, como já é feito — sem requisição a terceiro.
- **O nome da família vem com o redesenho.** Até lá vale um substituto de métrica
  equivalente.

**Dois pesos não são preferência, são piso funcional.** O `brand.md` mediu que o laranja da
marca (`#F23E02`, 3,9:1 sobre branco) **só passa em AA como texto grande ou negrito**. Sem
negrito, a paleta da marca é inutilizável em texto.

**Fato verificado (2026-09-09):** `web/src/app/layout.tsx` carrega **Geist Mono** e
`font-mono` só aparece em `web/src/app/page.tsx` — o scaffold do Next, que vai ser apagado.
É **uma família inteira baixada para uma página que não é do produto**. Sai na fundação;
ganho de peso de graça.

**Custo aceito:** estimados **15–30 KB por peso** (`woff2`, subconjunto latino), logo 30–60 KB
no total. **Estimativa, não medição** — medir quando a família for escolhida.

**VERIFICADO em 2026-09-09**, lendo o pacote instalado (`next@16.3.3`), não de memória:
`display` tem padrão **`'swap'`** e `adjustFontFallback` tem padrão **`true`**
(`.../@next/font/dist/google/validate-google-font-function-call.js`); com ele ligado, o
loader gera uma família de reserva **com métricas ajustadas**, a partir da tabela embarcada
`next/dist/server/capsize-font-metrics.json`. O texto aparece no primeiro quadro e a troca
**não desloca o layout**.

Ressalva: o ajuste elimina o **deslocamento**, não a **troca visível de desenho** — a letra
ainda muda à vista. É o comportamento correto: o conteúdo fica legível desde o início, que é
o que a régua pede em 3G.

**Previsão contrariada, registrada:** ia-se anotar que a garantia dependeria de a família vir
do Google Fonts, e que uma fonte licenciada pelo redesenho a perderia. **Está errado.** O
`next/font/local` calcula as métricas de reserva a partir do **próprio arquivo da fonte**
(`getFallbackMetricsFromFontFile`), e só desliga se `adjustFontFallback: false` for passado
de propósito. **A escolha da família no redesenho (BORA-25) não fica limitada por isto.**

### D22 — Papéis semânticos de cor (origem: Ícaro)

Não se parte do zero: o `globals.css` **já tem** uma lista de papéis (a do shadcn). O
trabalho é **cortar o morto e acrescentar o que falta**.

| Grupo | Papéis |
|---|---|
| **Superfície e texto** | `fundo`, `superfície`, `superfície-elevada`, `texto`, `texto-secundário`, `borda`, `borda-de-campo`, `foco` |
| **Ação** | `primária`, `sobre-primária`, `secundária`, `sobre-secundária` |
| **Estado** | `sucesso`, `perigo`, `aviso` — cada um com seu par de texto |

> Os nomes acima descrevem o **papel**; os identificadores no código são em inglês
> (`naming-conventions.md`).

**Três níveis de superfície decorrem da D3**, não são gosto: no escuro a hierarquia se faz
por superfície e borda, não por sombra — dois níveis não bastam para separar página, cartão
e bloco elevado.

**O que sai:** `sidebar-*` (oito papéis; o Bora não tem sidebar — tem **trilho lateral**, que
usa os mesmos papéis de fundo) e `chart-1..5` (não há gráfico no produto; se a Fase 2 trouxer
analytics, entram lá).

**Duas simplificações, ambas para reduzir pares de contraste a validar — e a D3 já dobrou
esse trabalho ao adotar claro e escuro:**

- **`perigo` serve erro E ação destrutiva**, um papel só. É o que o shadcn já faz.
- **`info` não existe como papel.** O `Alert` de hoje já usa `muted` para informação e
  funciona.

**Mudança de estrutura, não de valor:** hoje o escuro é por **classe** (`.dark`) e **nada
aplica essa classe**. Pela D3, os papéis passam a ser redefinidos sob
`prefers-color-scheme` — tarefa da fundação.

**A escala categórica de cores fica de fora** (Ícaro, 2026-09-09). Ela é de **gênero
musical**, que é do artista (`RN-ART-002`) e não entra na spec 002 — e a lista de gêneros é
a **BORA-19**, ainda em aberto: definir cor para uma lista que não existe seria inventar. As
três categorias de local não precisam de cor, porque a régua exige rótulo de texto de
qualquer jeito. Quando entrar, a forma correta é **um conjunto pequeno de slots atribuídos
por dado**, nunca um token por gênero — a `RN-ART-002` diz que a lista é dado, e token
engessaria em CSS o que precisa mudar sem tocar em código.

**O que continua dependendo do redesenho (BORA-25):** os **valores** dos papéis. A troca não
deve exigir mexer em tela, porque **nenhuma tela escreve cor literal** — regra que hoje já
está sendo quebrada em `page.tsx` e `alert.tsx`, e que a fundação conserta.

## Fatos verificados que motivaram estas decisões

Todos conferidos no repositório em 2026-09-01:

- **`web/src/components/ui/button.tsx`** — o `size: default` do shadcn é `h-8` (**32px**).
  O padrão do kit **reprova** a régua de 44px. Hoje só passa porque cada chamada corrige na
  mão: `className="min-h-11 w-full text-base"` aparece copiado em `BaseForm.tsx`,
  `GoogleButton.tsx` e nos formulários. Dívida silenciosa — o dia que alguém esquecer o
  `min-h-11`, ninguém percebe.
- **`web/src/app/globals.css`** — é o tema padrão do shadcn, cinza neutro. **Nenhum token
  da paleta do Bora** (`#F23E02`, `#F4F1DE`, `#2B2C28`). O escuro é por classe e nada
  aplica a classe.
- **Cor literal já vazando** — `web/src/app/page.tsx` usa `bg-zinc-50`, `dark:bg-black`,
  `text-zinc-600`; `web/src/components/ui/alert.tsx` usa `emerald-600` cravado no sucesso.
  A regra "nenhuma tela escreve cor literal, só token" já está sendo quebrada no pouco
  código que existe — e é ela que torna a troca de paleta barata.
- **`web/src/app/page.tsx`** — ainda é o boilerplate do `create-next-app` (logo da Vercel,
  "To get started, edit the page.tsx file"). A home do produto não existe.
- **`web/src/app/layout.tsx`** — carrega **Geist / Geist Mono**, herdado do boilerplate. O
  `brand.md` sugere "Poppins ou similar". **Ninguém decidiu**; hoje vale o boilerplate por
  omissão.
- **O que já é bom e não se joga fora** — `field.tsx` e `alert.tsx` já carregam a régua
  dentro do próprio componente (≥16px, `min-h-11`, `aria-describedby`, `role="alert"`), e
  `lib/hydration.ts` (`useHydrated`) já resolve E-012/E-013/E-015 por construção. O
  template **reconcilia** com isso; não recomeça.

## O que já dá para decidir sem o redesenho da marca

A identidade visual está PENDENTE no `brand.md` e o Figma foi aposentado. Ainda assim,
**estrutura e estética separam-se** — a estética trava a estrutura em exatamente três
pontos, e os três podem ser fechados sem redesenho:

1. **Claro ou escuro** — fechado na D3.
2. **A lista de papéis semânticos de cor** (superfície, superfície elevada, texto, texto
   secundário, borda, ação primária, sucesso, erro, aviso, escala categórica de gênero
   musical). Isso é **vocabulário**, não paleta: os hexes podem ser provisórios, a lista de
   papéis não — mudá-la depois é reescrever tela. **Em aberto.**
3. **O perfil de contraste do laranja** — já **medido** no `brand.md`: `#F23E02` dá 3,9:1
   sobre branco (reprova para texto normal) e `#FEC601` dá 1,6:1 (nunca é texto). Logo a
   regra "o laranja nunca é cor de texto e nunca é fundo de botão com texto branco em
   tamanho normal" **já é conhecida hoje**.

**Armadilha registrada (opinião):** "uso cor provisória e troco depois" só é barato se a
provisória tiver **o mesmo perfil de contraste** da marca real. Prototipar com um azul de
7:1 e depois entrar o laranja de 3,9:1 não troca um valor — redesenha o componente.

## Em aberto

- **Falta medir:** o orçamento real de caracteres dos rótulos da barra, na fonte real, a
  360px e sob os dois cenários de ampliação. Os números usados na D12 são **estimativa**
  (~0,5em de avanço médio por caractere), não medição. Resolve-se renderizando os rótulos
  candidatos e conferindo.
- ~~**Tipografia**~~ — **estrutura fechada na D21** (uma família, dois pesos, `next/font`), e
  o comportamento do `next/font` **verificado em 2026-09-09** no pacote instalado. Falta só o
  **nome da família**, que vem com o redesenho (BORA-25).
- ~~**Lista de papéis semânticos de cor**~~ — **fechada na D22**. Faltam os **valores**, que
  vêm com o redesenho (BORA-25).
- **Escala tipográfica e de espaçamento** — derivável da régua, quase sem escolha.
- **`RN-DESC-003` está decidida (D19) mas ainda não escrita no catálogo.** A regra só vale
  depois de entrar em `docs/domain/descoberta.md` pela skill `domain-rule`. Enquanto não
  entrar, o catálogo e este documento discordam.
- ~~**Propostas não ratificadas**~~ — **as quatro foram despachadas em 2026-09-09.** D13
  ratificada **emendada** (três arquétipos, não quatro); D17 ratificada **emendada** (o
  padrão é obrigatório, o conjunto de componentes cresce por demanda); o refluxo da D12
  ratificado, com o corte "3 + 2" removido por ser especificação antes da medição; **D18
  suspensa** junto com o arquétipo "Ferramenta", guardada inteira para quando a calculadora
  tiver regras.

**Com isso, toda decisão deste documento tem origem "Ícaro" ou "derivada".** Não resta
proposta pendurada.
- **Hierarquia visual do Detalhe** — a régua exige "uma ação principal", mas o perfil do
  local acumula sete controles (D19). A receita precisa declarar níveis, não só o topo.
- **Estado "ainda não sei" do ♥** — precisa ser desenhado (rótulo, aparência) e não pode
  parecer defeito nem mentir. Sai da disciplina do `useHydrated`, mas o desenho é trabalho
  de tela.
- **`RN-CONTA-001`** — as pendências da calculadora (ver D18) contra a vaga que ela já
  ganhou na barra (D9).
- **O que exatamente o portão assere** — incluindo a asserção de fonte ampliada (D12).
- **ADR** — a D5 (duas molduras) é estrutural o bastante para merecer ADR própria. Não foi
  aberta.
- **Pendências herdadas** que continuam bloqueando e não são deste documento: redesenho da
  identidade (`brand.md`), registro INPI, provedor de mapas.
