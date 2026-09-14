# Backlog

Status: pendências extraídas da ideia original e das decisões de 2026-08-28 em diante.
**Spec 001 CONCLUÍDA em 2026-09-02** — 120/120, com a T120 aberta e fechada durante a
própria validação visual (E-019). Primeira feature do Bora a cumprir a Definition of Done
do Princípio XI por inteiro.

## Decisões pendentes que bloqueiam spec

Cada item abaixo precisa ser decidido **antes** da spec que depender dele.

| Item | Bloqueia | Fonte |
|---|---|---|
| **Severidade da regra de lint que proíbe cor literal em tela (T009 da spec 002)**: entra como **erro agora**, ou como **aviso agora promovido a erro na T021**? Medido em 2026-09-12 com a regra montada e descartada: ela pega **10 ocorrências** — 9 em `web/src/app/page.tsx` (o scaffold) e 1 em `web/src/components/ui/alert.tsx:23` (o `emerald` do `kind="success"`). As correções desses dois arquivos são a **T013** e a **T021**, posteriores. **Contexto que muda a pergunta:** o `npm run lint` **já está vermelho** hoje, por 7 erros de `react-hooks/set-state-in-effect` — não é a T009 que o deixa vermelho, é a **BORA-42**, e ele continuará vermelho depois da T021 enquanto a BORA-42 não for tratada | **T009, e com ela o resto da Phase 2 da spec 002** | `specs/002-cadastro-perfil-local/tasks.md`, BORA-42 |
| **Convenção para diretório vazio no Git** (`.gitkeep` ou nada): as pastas criadas nas T001/T002 da spec 002 não entram em commit nenhum, porque o Git não versiona diretório vazio e o repositório **não tem nenhum `.gitkeep`** hoje. Elas existem em disco e se materializam quando as tarefas seguintes gravarem arquivo dentro | nada hoje; afeta quem clonar o repositório antes da T010 | `specs/002-cadastro-perfil-local/tasks.md` |
| Registro de marca "Bora" (INPI) + domínio + @ nas redes | material público, lançamento, **envio real de e-mail (SPF/DKIM — spec 001)** | `brand.md` |
| Teste informal de usabilidade com usuário de baixo letramento digital (idoso) | lançamento Fase 1 | `ux-requirements.md` |
| Hospedagem (agora precisa hospedar **também um processo Node**, além do PHP) | deploy | constituição, ADR-0003 |
| **Ferramenta de túnel HTTPS** (ngrok, Cloudflare Tunnel…) para validar o **login com Google no celular** — o IP de rede local não serve como URI de redirecionamento, o Google só aceita HTTP em loopback | **validação visual da spec 001 no celular** (Princípio XI, US2). As telas sem Google validam por IP de rede local, sem túnel | `ux-requirements.md`, `specs/001-contas-autenticacao/` |
| Emenda constitucional formalizando o Resend como provedor de e-mail transacional (decisão já tomada — ver Decisões tomadas) | governança | constituição (lista PENDENTE do Stack) |
| Padrão de documentação OpenAPI **por feature**: cada feature documenta o quê, com que detalhe, conferido por quem e quando (BORA-26). A ferramenta já está decidida — Scramble, D3; falta a regra | toda spec com API | spec 001 D3, BORA-40 |
| Cidade do usuário: geolocalização, escolha manual, múltiplas cidades | feed, busca | `RN-PLAT-006` |
| Método automático que sucede a aprovação manual da reivindicação | cadastro de local | `RN-LOCAL-005` |
| Desempate entre duas reivindicações do mesmo local | cadastro de local | `RN-LOCAL-005` |
| **Revogar** o papel de operação: apaga o vínculo ou o encerra preservando histórico? A revogação audita? O registro de quem aprovou reivindicação sobrevive a ela? (BORA-53) | futura **spec de operação** — **não** bloqueia a spec 002, que concede e não revoga | `RN-PLAT-007` |
| Lista inicial de gêneros musicais ("Tiktok" é gênero ou coleção?) | cadastro de artista, filtros | `RN-ART-002` |
| Co-gestão de perfil de banda | cadastro de artista | `RN-ART-001` |
| Evento publicável antes da confirmação do artista? Prazo de resposta? | eventos | `RN-EVENTO-002` |
| Evento com atração não cadastrada (texto livre + reivindicação)? | eventos | `RN-EVENTO-002` |
| Janela de edição de evento publicado e notificação de mudança | eventos | `RN-EVENTO-004` |
| Escala de avaliação (likes? estrelas?) e 1-por-usuário vs comentários livres | avaliações | `RN-AVAL-001` |
| Avaliação do artista: geral ou por apresentação/evento | avaliações | `RN-AVAL-002` |
| Viabilidade/custo do Google Places para exibir avaliações (máx. ~5, com atribuição) — **registrar ADR** | avaliações | `RN-AVAL-003` |
| Política de moderação (o que remove, prazo, quem modera) | avaliações, conteúdo | `RN-AVAL-004` |
| Ordenação padrão do feed sem histórico (cold start) | descoberta | `RN-DESC-002` |
| Cancelamento de evento notifica também quem apenas **salvou**? (nasceu da separação salvar/seguir — BORA-45) | eventos, notificações | `RN-EVENTO-004`, `RN-DESC-003` |
| Mapa embutido vs link externo; provedor de mapas e custo | rotas | `RN-DESC-004` |
| Sinais da personalização v1 e critério mínimo (cold start) | personalização | `RN-DESC-005` |
| Canais de notificação (push web/e-mail/WhatsApp) e provedores | notificações | `RN-DESC-006`, constituição |
| Divisão de conta: simples ou por item; taxa/couvert; anônima? | divisão de conta | `RN-CONTA-001` |
| Tecnologia do app mobile: Flutter, React Native ou PWA — **decidir só na Fase 3** | app mobile (Fase 3) | ADR-0003, Princípio IV |
| Preço dos planos B2B e limite do plano grátis (validar no mercado local) | monetização Fase 1 | `monetization.md` |
| Contagem real de casas com música ao vivo nas duas cidades (premissa: 20% de 741) | mercado, pitch | `investment-plan.md` |
| Validar premissas do orçamento (pró-labore, freelances, custo de API em volume) | captação | `investment-plan.md` |
| Critério numérico para ativar a cobrança (fim da Fase 0) | monetização Fase 1 | `monetization.md` |
| Gateway de pagamento | bilheteria (Fase 3), Pix da divisão | `monetization.md`, `RN-CONTA-001` |
| Redesenho da identidade visual (logo flat, dark-first, tokens semânticos) | UI | `brand.md` |

## Entregáveis previstos, ainda sem spec

### Landing page (no lançamento do MVP)

Decidido por Ícaro em 2026-08-29: **não se constrói agora**. Entra no **lançamento do MVP**
e deve estar **alinhada com o que estiver documentado como produção naquele momento** — ou
seja, com o produto de fato entregue e com `brand.md` + `ux-requirements.md` vigentes na
data, e não com uma visão antecipada do produto.

- **Gatilho:** lançamento do MVP (Fase 1 de `vision.md`).
- **Escopo:** a definir. Ícaro informa mais à frente se haverá também uma página de
  **pré-lançamento** (institucional/waitlist, que não depende da API). A **home pública do
  produto** — feed "O que temos para hoje?" — é tela de produto, segue pelo fluxo normal de
  spec e **não se confunde** com a landing.
- **Onde vive:** `web/` (ADR-0002, ADR-0003), como página renderizada no servidor. Nunca
  como aplicação separada.
- **Sujeita ao Princípio XI e ao `spec-check`** como qualquer tela: 360px sem rolagem
  horizontal, WCAG AA, testes em 360 e 1280 com `axe`, validação visual do Ícaro.

**Bloqueios conhecidos** (verificados em 2026-08-29):

- **Marca/INPI.** A landing é material público com nome e logo — cai no item "Registro de
  marca 'Bora' (INPI) + domínio + @ nas redes" da tabela acima. Construir, pode;
  **publicar antes do registro é exatamente o risco que aquele item sinaliza.**
- **Identidade visual.** O redesenho (logo flat, dark-first, tokens semânticos) está
  PENDENTE em `brand.md` e o Figma está aposentado. Hoje não há referência visual aprovada
  além da paleta, com as ressalvas de contraste já medidas.
- **Setup do `web/`.** Tailwind, biblioteca de primitivas acessíveis e o conjunto de testes
  de front são action item da **spec 001** (ADR-0003). Se a landing vier antes da 001, ela
  **herda** essas decisões — não as toma sozinha.
- A landing **não é** o spike descartável do M0 (aquele consome um `GET` da API e é jogado
  fora).

**Modularização — julgamento do assistente, não medido.** Vale modularizar em sentido
estreito: cada seção como componente próprio em `web/src/components/landing/` e a **copy
fora do JSX**, em módulo TypeScript tipado. Razões: a copy muda com frequência na janela de
lançamento e é reaproveitada em redes/e-mail/pitch; os três públicos (rolezeiro, bar,
artista) tendem a gerar páginas irmãs com a mesma estrutura; e testar seção a seção nas duas
larguras é mais barato que testar página monolítica. Como componentes de servidor
estáticos, isso não custa nada em runtime. **Não vale** agora: sistema de blocos
configurável, CMS ou landing como aplicação separada — é uma página, um dev, nenhum editor
não-técnico. Os primitivos (botão, container, tipografia, tokens de cor) nascem em
`web/src/components/ui`, compartilhados com o app; se nascerem dentro de `landing/`, a
landing vira um segundo design system e diverge das telas do produto.

### Envio real de e-mail (antes do primeiro usuário de verdade)

**Estado verificado em 2026-09-01, abrindo os arquivos:** o envio **não está configurado**.
`MAIL_MAILER=log`, `RESEND_KEY` vazio — nenhuma mensagem sai da máquina; todas caem em
`api/storage/logs/laravel.log`. Isso é o comportamento **certo** para desenvolvimento
(decisão D8), e vale para os três fluxos: verificação de e-mail, redefinição de senha e link
de união.

O mailer `resend` **já existe** em `config/mail.php` (veio do scaffold do Laravel), então
não há código a escrever — é configuração e DNS.

**O que falta, item a item:**

| item | valor hoje | por quê importa |
|---|---|---|
| `MAIL_MAILER` | `log` | precisa virar `resend` em produção |
| `RESEND_KEY` | vazio | chave da conta |
| domínio verificado no Resend | não existe | sem SPF/DKIM o e-mail cai em spam ou é recusado |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | padrão do scaffold, nunca tocado |
| `MAIL_FROM_NAME` | `${APP_NAME}` | herda o item abaixo |
| `APP_NAME` | **`Laravel`** | as 19 mensagens já geradas saem como `From: Laravel <hello@example.com>` |

**Armadilha registrada, para não morder na hora da troca:** `APP_NAME` não alimenta só o
remetente. Sem `CACHE_PREFIX` e `REDIS_PREFIX` explícitos — e eles **não** estão no `.env` —
o Laravel deriva os prefixos de cache, Redis e sessão do slug de `APP_NAME`
(`config/cache.php:121`, `config/database.php:155`, `config/session.php:132`). Trocar
`APP_NAME` de `Laravel` para `Bora` **invalida todas as chaves de cache no instante da
troca**: em produção isso derruba quem estiver no meio de um login com Google (o `state`
vive no cache) ou com uma união pendente. Ou se faz numa janela em que isso é aceitável, ou
se fixa `CACHE_PREFIX`/`REDIS_PREFIX` explicitamente **antes** de mexer no nome.

- **Gatilho:** antes do primeiro usuário real — não antes.
- **Dependência:** o endereço remetente depende do **domínio**, que depende do registro de
  marca no INPI, hoje PENDENTE (item na tabela acima). Configurar o Resend com um domínio
  que talvez mude é retrabalho garantido.
- **Por que isto está escrito em vez de ser "óbvio na hora":** a falha é **silenciosa por
  desenho**. Pela decisão D5, falha de envio não derruba a operação que a originou — a API
  responde 200, a conta é criada, a tela diz que deu certo, e a pessoa simplesmente nunca
  recebe o e-mail. Foi exatamente assim que o E-018 passou despercebido por horas. Vale
  considerar, junto com a configuração, um **alarme sobre `failed_jobs`**: hoje ninguém é
  avisado quando um e-mail morre ali.

## Dívidas técnicas conhecidas

Levantadas em 2026-09-01, durante a validação visual da spec 001. Nenhuma delas foi
inventada: cada uma foi verificada abrindo o arquivo ou rodando o comando citado.

| item | estado verificado | por que não foi feito agora |
|---|---|---|
| **A home é o scaffold do Next** — BORA-43 | `web/src/app/page.tsx` ainda é a página de `create-next-app` — logo do Vercel, "To get started, edit the page.tsx file", tudo em inglês. É o destino do login (`router.replace('/')`) | a home de produto ("O que temos para hoje?") é spec futura; trocá-la agora seria abrir feature fora de spec |
| **`npm run lint` falha** — BORA-42 | 7 erros, todos `react-hooks/set-state-in-effect`, em `lib/hydration.ts`, `verificar-email`, `unir-contas`, `unir-contas/confirmar` e `entrar/google/retorno`. Pré-existentes: nenhum nos arquivos tocados em 2026-09-01. **Reconferido em 2026-09-12: os mesmos 7, nos mesmos arquivos** | mexer em `useEffect` de cinco telas já validadas, sem teste que prove o ganho, arrisca regressão de hidratação (E-015) para resolver aviso de estilo |
| **As telas validadas da spec 001 reprovam no portão de conformidade** — aberto em 2026-09-12 pela T008 | 32 das 36 combinações reprovadas. Alvo de toque abaixo de 44px (o `a "Bora"` do cabeçalho, 38×28px, nas nove telas; e `a "Ir para o Bora"` e `a "Voltar para entrar"`, com 17px de altura, sendo a **única saída** da tela em que aparecem); texto de ajuda a 14px; link distinguido só por cor; e **contraste de 4:1 contra os 4,5:1 exigidos** no `Alert` de erro (`#e7000b` sobre `#fde6e7`). Lista completa em `error-log.md`, seção "T008" | **não é dívida a tratar avulsa**: já tem dono na própria spec 002 — o retrofit **T021–T025**, com a T025 conferindo contra esta lista. Corrigir tela não é tarefa do portão |
| **Não existe "excluir minha conta"** — BORA-41 | `AuditLog` tem seis eventos (`account_created`, `credentials_merged`, `password_set`, `password_reset`, `email_verified`, `session_ended`) e **nenhum** de exclusão; não há rota de exclusão em `api/routes/api.php` | nunca esteve no escopo da 001. Mas **direito de eliminação é LGPD**, que a constituição invoca — precisa de spec própria, com decisão sobre anonimizar vs. apagar e o que acontece com histórico |
| **O `spec-check` não cobra o caminho até a tela nem os campos do payload** — BORA-40 | é a causa raiz do E-019: a spec listava cinco telas e o portão conferiu as cinco; a tela sem porta não estava na lista, então não havia o que cobrar | mexer no portão é mudança de método, não de código — decisão do Ícaro |
| ~~`SKILL.md` do `doc-sync` pedia trailer `Claude Fable 5`~~ | **RESOLVIDO em 2026-09-02**, autorizado pelo Ícaro: a skill passou a pedir `Claude Opus 5`, que é o que os commits do repositório já usavam. Era o único lugar do `.claude/` com o nome antigo | — |

## Infra do método

- **Linear**: projeto **Bora** criado em 2026-08-28
  (<https://linear.app/icasst/project/bora-f0ad76fe7e09>), migrado em 2026-08-29 para o
  **time próprio `Bora`/BORA**, com marcos M0–M5 e uma issue `decisao-pendente` por item da
  tabela acima (BORA-1..BORA-29) + setup (BORA-30, BORA-31).
  Ver `docs/logs/linear-import.md`.
- **Claude Cowork**: instruções do Project em `docs/agents/cowork-project.md`
  (2026-09-09); o campo do produto recebe só o resumo da seção 0 e aponta para o arquivo.
  Duas pendências de método que a inspeção levantou, **decisão do Ícaro**:
  - **Trailer `Co-Authored-By` fixado na skill `doc-sync`** (`Claude Opus 5`, decidido em
    2026-09-02). Quando outro modelo conduz a sessão, o ambiente instrui a assinar com o
    nome dele — foi o caso em 2026-09-09 (`Claude Fable 5.1`), e o commit desta entrada
    seguiu o ambiente, não a skill. Opções: (a) a skill deixa de fixar o nome e passa a
    dizer "o modelo que assinou a sessão"; (b) a skill fixa e o assistente sempre a
    obedece, mesmo contra o ambiente. Até decidir, o histórico terá as duas assinaturas.
  - **Skill `speckit-taskstoissues`** (`.claude/skills/speckit-taskstoissues/SKILL.md`)
    veio no bundle do Spec Kit e cria issues no **GitHub**; o rastreio do Bora é no
    Linear. Nunca foi usada. Opções: remover a skill, ou mantê-la e proibir o uso no
    `CLAUDE.md`. Hoje só o `cowork-project.md` diz "não usar".

- **Ritual de abertura `session-open`** (2026-09-09):
  `.claude/skills/session-open/SKILL.md`, somente leitura e manual, registrado no
  `development-workflow.md` (§2 e §3) e no `CLAUDE.md`. Fecha o par com o `/doc-sync`.
- **PENDENTE — renormalizar ou não os arquivos já versionados** (decisão do Ícaro). O
  `.gitattributes` (`* text=auto eol=lf`) entrou em 2026-09-09; o repositório não tinha
  nenhum, e a normalização dependia do `core.autocrlf` de cada máquina.
  **O que foi medido:** o stage dos quatro caminhos desta sessão deu diff de 4 linhas — o
  arquivo novo não sofreu reescrita.
  **O que NÃO foi medido:** se os arquivos antigos estão gravados com CRLF. A sondagem usou
  `git show :caminho`, que pode converter na saída; `git cat-file blob` (que não converte)
  não chegou a rodar. Enquanto isso não for medido, **não se sabe** se um `git add` futuro
  vai mostrar arquivo inteiro reescrito.
  **Como medir, em um comando:**
  `git ls-tree -r HEAD --name-only | while read f; do git cat-file blob "HEAD:$f" | grep -qU $'\r' && echo "$f"; done`
  **Opções, se der CRLF:** (a) um commit dedicado de renormalização
  (`git add --renormalize .`), que suja o `blame` de uma vez só e resolve; (b) deixar
  acontecer aos poucos, e cada arquivo tocado aparecer reescrito no seu commit.

## Decisões tomadas

- **A entrada para "Definir senha" foi corrigida dentro da spec 001** (decisão do Ícaro,
  2026-09-01, durante a T118). A tela existia e funcionava, mas nada no produto levava até
  ela (E-019). Alternativas oferecidas: corrigir agora, ou registrar e fechar a 001. Ele
  escolheu **corrigir dentro da 001** — e depois escolheu a **faixa abaixo do cabeçalho**,
  em vez de link no cabeçalho, para não apertar os alvos de toque a 360px. A lição ficou
  escrita na própria spec: **listar a tela não basta; o caminho até ela é parte da tela.**
- **Nomenclatura padronizada** (decisão do Ícaro, 2026-08-31): **identificador em inglês,
  prosa em português, e a única exceção é o caminho da URL**. Escrita em
  `docs/architecture/naming-conventions.md`; `CLAUDE.md` e `development-workflow.md` §5.1
  apontam para lá, para as próximas specs já nascerem no padrão. Aplicada retroativamente à
  spec 001 inteira no mesmo dia — código, banco (migrations editadas, não migrations de
  rename), campos do JSON, testes e documentação. Três fronteiras foram decididas por ele
  explicitamente: nomes de método de teste em inglês, banco em inglês editando as migrations
  existentes, e **campos do JSON em inglês** — esta última revertendo uma escolha anterior
  minha de manter o corpo em português, depois que ele apontou o custo permanente da
  tradução de borda. Só o caminho da rota continua em português.
- **Polish da spec 001 concluído** (T110–T117, 2026-08-31). Documentação da API conferida
  contra o contrato (14 rotas, batem exatamente), andaime do spike removido, verificação de
  vazamento de token e de logs limpa, quickstart executado contra a API real.
- **Token de união saiu da URL** (decisão do Ícaro, 2026-08-31): passou a `sessionStorage`.
  A regra "token nunca em URL" agora vale sem exceção; só o link de e-mail do plano B
  continua carregando token, onde é inevitável.
- **US3 e US4 da spec 001 entregues e VALIDADAS pelo Ícaro** (2026-08-31). Com elas, as
  quatro user stories estão prontas: 109/119 tarefas, 185 testes de backend, 37 de
  componente e 66 e2e. **A feature ainda não está pronta** — falta o Polish, que contém
  itens da própria Definition of Done (documentação da API conferida contra o contrato,
  andaime do spike removido, verificação de token em log e em prop de cliente).
- **US2 da spec 001 entregue e VALIDADA pelo Ícaro** (2026-08-31). Entrar com Google e
  definir senha. 87/119 tarefas. 130 testes de backend, 24 de componente, 32 e2e.
- **Pacote de CA instalado no PHP da máquina** (E-014, autorizado pelo Ícaro em
  2026-08-31). O PHP 8.4 do WAMP não tinha `curl.cainfo` nem `openssl.cafile`, então
  **nenhuma** chamada HTTPS funcionava. `cacert.pem` oficial do projeto curl em
  `C:\wamp64\bin\php\php8.4.15\extras\ssl\`, apontado em `php.ini` e `phpForApache.ini`,
  com backups `.bak-antes-cacert`. **Risco conhecido, igual ao do phpredis:** se o WAMP
  atualizar o PHP, a configuração se perde e volta o `cURL error 60`. Também vale renovar o
  `cacert.pem` de tempos em tempos — raízes expiram.
- **Validação no celular passa a ser sem configuração** (2026-08-31, após E-011/E-012/E-013).
  O front deriva o endereço da API de onde a página foi aberta, a CSP faz o mesmo pelo
  header `Host`, o `allowedDevOrigins` vem das interfaces de rede da máquina e a task do
  VS Code sobe a API em `0.0.0.0`. Some o `.env.local` com IP fixo.
  **Sobra um ajuste manual:** `FRONTEND_URLS` em `api/.env` precisa conter a origem do
  celular, porque o CORS usa origens explícitas de propósito (Princípio V). Vale avaliar,
  numa próxima, liberar por padrão as faixas de rede privada **apenas em ambiente local** —
  fecharia o último passo manual sem afrouxar produção.
- **US1 da spec 001 entregue e VALIDADA pelo Ícaro no celular** (2026-08-31). Primeira
  feature do Bora a fechar a Definition of Done do Princípio XI por inteiro. 71/119
  tarefas. 99 testes de backend, 18 de componente com `axe`, 14 e2e em 360 e 1280.
- **Testes de feature rodam em MySQL, não em SQLite** (decisão do Ícaro, 2026-08-30 —
  fecha a pendência que estava aberta aqui). Base `bora_test`, separada da `bora` de
  desenvolvimento porque o `RefreshDatabase` apaga tudo a cada execução. Motivo: a
  invariante central da feature é um índice único, e SQLite e MySQL divergem em índice,
  colação e comparação de string. Custo aceito: a suíte foi de ~1s para ~10s.
  Criar a base com:
  `CREATE DATABASE bora_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- **Fundação da spec 001 implementada** (T001–T044, 2026-08-30). Testes verdes dos dois
  lados. Decisões de implementação que fogem do texto da tarefa e valem saber:
  1. **Políticas de domínio recebem parâmetro por construtor**, não por `config()`. As
     tarefas diziam "mínimo vindo de config", mas isso importaria framework para dentro do
     núcleo (Princípio VII). A amarração acontece no `AppServiceProvider`; os testes de
     domínio rodam em `PHPUnit\TestCase` puro, sem subir o Laravel.
  2. **`Retry-After` exposto no CORS.** Não estava na tarefa. Sem `exposed_headers`, o
     JavaScript não lê o header e a tela não tem como dizer quanto esperar após um 429 —
     requisito de UX que ficaria impossível de cumprir.
  3. **"Test User" removido do `DatabaseSeeder`.** Seeder que cria conta silenciosamente
     atrapalha justamente a feature cuja invariante é "uma conta por e-mail".
  4. **Classe `Auditoria` com lista de chaves barradas**, em vez de `activity()` solto:
     um descuido futuro num caso de uso não consegue gravar senha ou token no log.
  5. **Limitação de e-mail registrada em teste**: acento no endereço e domínio
     internacionalizado são recusados (limite do `filter_var`). Não atinge o público real;
     fica documentado com o ponto exato de correção, se um dia precisar.
- **Ressalva aberta — testes rodam em SQLite, produção é MySQL.** O `phpunit.xml` do
  Laravel usa `sqlite :memory:`; verificado que o banco de dev **não** é tocado pelo
  `RefreshDatabase`. Mas a invariante central desta feature é um **índice único**, e
  testar num banco enquanto se roda em outro pode esconder diferença de comportamento.
  **Decisão do Ícaro, ainda não tomada:** manter assim (rápido) ou apontar os testes de
  feature para um banco MySQL de teste (fiel).
- **Tarefas da spec 001 geradas** (`/speckit-tasks`, 2026-08-30):
  `specs/001-contas-autenticacao/tasks.md`, **119 tarefas** (T001–T119) por user story —
  Setup 13, Foundational 31, US1 27 (**MVP**), US2 16, US3 12, US4 10, Polish 10; 85
  paralelizáveis. Duas coisas registradas de propósito, para não se perderem:
  1. **Teste aqui não é opcional.** O template do Spec Kit trata como opcional; os
     Princípios IX e XI mandam o contrário. Toda story tem teste de back e front, e os que
     **provam bloqueio** têm tarefa própria: T046 (conta duplicada), T054 (gratuidade),
     T091 (invariante da união em todos os desfechos), T100 (resposta neutra).
  2. **A US3 não é independente.** Unir credenciais é o cruzamento de US1 e US2, então
     exige as duas implementadas — é a única dependência real entre stories. US1, US2 e
     US4 podem correr em paralelo depois da fundação.
- **Credenciais OAuth do Google criadas** (Ícaro, 2026-08-30). Projeto `bora-507117` no
  Google Cloud, cliente OAuth 2.0 do tipo Aplicativo da Web, app em modo **Externo**.
  `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e `GOOGLE_REDIRECT_URI` gravados em
  `api/.env` (ignorado pelo git) e **verificados**: o Laravel lê os três.
  URIs registrados no console: origem `http://localhost:3000` e redirecionamento
  `http://localhost:3000/entrar/google/retorno` — o redirecionamento aponta para o `web/`,
  **não** para a API, porque o Google devolve o `code` a uma página do Next que o repassa
  por POST (é o que mantém o token fora da URL).
  **Ainda a confirmar pelo Ícaro:** que o "Salvar" do console foi aplicado (o próprio
  console avisa que pode levar de 5 min a algumas horas) e que o Gmail dele está em
  **Público-alvo → Usuários de teste** — sem isso o retorno é `access_denied`.
  **Decisão de segurança registrada:** este cliente é **de desenvolvimento e não vai a
  produção**. Quando a hospedagem for definida (BORA-27), cria-se um cliente novo, com
  secret próprio. Motivo: o secret deste foi exposto em transcrição de conversa; com
  cliente separado, a exposição não alcança produção. Escopos mantidos no mínimo
  (`openid`, `email`, `profile`) — qualquer escopo além disso dispara verificação do Google.
- **Plano técnico da spec 001 fechado** (`/speckit-plan`, 2026-08-30). Spec **aprovada** pelo
  Ícaro; artefatos em `specs/001-contas-autenticacao/`: `plan.md`, `research.md`,
  `data-model.md`, `quickstart.md` e `contracts/auth-api.md`. Constitution Check sem gate
  bloqueante; duas ressalvas justificadas no Complexity Tracking. Decisões técnicas que o
  plano fixou, todas sobre estado **verificado** (Boost + `composer --dry-run`):
  1. **Socialite exige `-W`** — o pacote pede `guzzle ^6|^7` e o projeto está em guzzle
     8.1.0. Aceito o downgrade para 7.15.5; verificado que nada exige guzzle 8 (framework
     `^7.8.2||^8.0`, boost `^7.9|^8.0`, flysystem só conflita `<7.0`). **A revisitar**
     quando o Socialite suportar guzzle 8 — aí um `composer update` reverte.
  2. **Expiração deslizante é código nosso** — o Sanctum só tem prazo absoluto; a D7 pediu
     inatividade. Middleware próprio sobre `expires_at`, com `'expiration' => null`.
  3. **Guarda do token: `localStorage`** (Ícaro, 2026-08-30), mantendo a D2 apesar de a doc
     oficial do Sanctum desaconselhar token de API para SPA de primeira parte. Risco de XSS
     aceito e escrito, com mitigações obrigatórias (CSP estrita, token fora de prop de
     componente cliente, fora de URL e de log).
  4. **Fluxo do Google sem token na URL** — a API devolve a URL de autorização, o Google
     redireciona para uma página do `web/`, e o `web/` troca o `code` por sessão via POST.
  5. **`/api/user` volta para dentro do versionamento** como `/api/v1/eu` (Princípio IV).
  Levantado e ainda não feito: três pacotes obrigatórios pela constituição não estão
  instalados (`socialite`, `spatie/laravel-permission`, `spatie/laravel-activitylog`) e o
  `web/` não tem nenhuma ferramenta de teste — tudo entra na implementação.
- **Spec 001 (fundação de contas e autenticação) escrita e aprovada no portão
  `spec-check`** (2026-08-29). Oito decisões que bloqueavam a spec foram tomadas pelo
  Ícaro na mesma sessão e registradas na spec (`specs/001-contas-autenticacao/spec.md`,
  seção "Decisões ratificadas", D1–D8):
  1. **União de credenciais (RN-PLAT-002)**: senha da conta existente confirma; link por
     e-mail é o plano B; direção inversa exige sessão ativa. Catálogo atualizado
     (`docs/domain/plataforma.md`) — o PENDENTE saiu.
  2. **Autenticação do `web/` + CORS**: token Bearer (paridade com o app mobile); origens
     explícitas; sem credenciais de cookie. Fecha o item "Setup de CORS/Sanctum SPA" que
     estava nesta tabela (ADR-0002 atualizado).
  3. **Documentação da API**: OpenAPI gerado automaticamente (Scramble).
  4. **Base de UI e testes de front** (action item do ADR-0003): shadcn/ui (Radix +
     Tailwind); Vitest + Testing Library + axe; Playwright e2e em 360 e 1280.
  5. **Verificação de e-mail**: envia sem bloquear o uso.
  6. **"Esqueci minha senha"**: dentro do escopo da 001.
  7. **Sessão**: expira em 30 dias de inatividade, renovada no uso (parâmetro
     configurável).
  8. **E-mail transacional: Resend** (atrás de porta & adapter; captura local em dev).
     A constituição lista esse item como PENDENTE no Stack — **formalizar por emenda**
     ficou como item de governança nesta tabela; o envio real em produção também depende
     do domínio próprio (SPF/DKIM), vinculado ao item de marca/INPI.
- **Nomes de comando corrigidos na doc do método** (2026-08-29): `CLAUDE.md` e
  `development-workflow.md` diziam `/specify`, `/plan` e `/tasks`, mas as skills
  instaladas são `speckit-specify`, `speckit-plan` e `speckit-tasks` (não existe
  `.claude/commands/`). Ver E-005. `spec-check` e `doc-sync` já estavam com o nome real.
- **Spike do frontend concluído — o Next fica** (BORA-32, 2026-08-29). As quatro perguntas
  do spike foram respondidas com evidência (detalhe no comentário de fechamento da
  BORA-32):
  1. **Renderização no servidor funciona:** os três eventos aparecem no HTML cru de
     `curl -s http://localhost:3000/eventos`. A razão principal da escolha do Next se
     cumpriu. Detalhe verificado nos docs do Next 16 instalado: `fetch` não é cacheado por
     padrão e bloqueia a renderização, então o `await` vai direto no componente de página;
     **`<Suspense>` mandaria o conteúdo por streaming e o tiraria do HTML inicial** — é a
     armadilha a evitar nas páginas de catálogo.
  2. **CORS resolvido para o caso anônimo, sem configurar nada.** O `HandleCors` já está no
     stack global e o default do framework é `paths => ["api/*"]`,
     `allowed_origins => ["*"]`, `supports_credentials => false`. Não existe
     `config/cors.php` publicado neste projeto.
  3. **360px sem rolagem horizontal**, medido no navegador (`scrollWidth == clientWidth`,
     nenhum elemento estourando); idem a 1280. Os critérios de mobile-first do
     `ux-requirements.md` são implementáveis — o documento não precisa mudar.
  4. **A fronteira servidor/cliente ficou clara.** Regra prática que o spike fixou: arquivo
     sem `"use client"` roda só no servidor e o navegador nunca recebe esse código; o
     `"use client"` é **fronteira, não etiqueta** — tudo abaixo dele vai para o navegador.
     Consequência verificada no HTML: **props que cruzam a fronteira são serializadas na
     página** (o endpoint aparece literal no payload RSC), logo **nenhum segredo pode
     atravessar essa linha** — atenção na spec 001, que terá token.
  **Consequência:** a alternativa React Router v7 prevista no ADR-0003 **não é acionada**.
  O que fica no repositório: `install:api` (Sanctum 4.3.3, `routes/api.php`, migration
  `personal_access_tokens`) e o `lang="pt-BR"` no layout raiz. O que é descartável e sai
  quando o spike morrer: `api/app/Http/Controllers/Spike/`, o bloco `v1/eventos` no fim de
  `api/routes/api.php` e `web/src/app/eventos/`.
  **Ressalva registrada:** isso **não** fecha o item "Setup de CORS/Sanctum SPA" da tabela
  acima — `allowed_origins: "*"` não convive com `supports_credentials: true`, que a área
  logada vai exigir. O spike é anônimo; a política de CORS continua sendo assunto da
  spec 001.
- **PATH da máquina corrigido: `php` global passa a ser o 8.4.15 do WAMP** (2026-08-29,
  E-004). A entrada `C:\xampp\php` (PHP 8.2.4) saiu do PATH da máquina e entrou
  `C:\wamp64\bin\php\php8.4.15`. Motivo: o `composer.bat` do Windows roda `php
  composer.phar`, então o composer herdava o PHP do XAMPP e reprovava no `"php": "^8.3"` do
  projeto. **Desinstalar o XAMPP foi avaliado e descartado** — era o único `php` do PATH
  (removê-lo deixaria o composer sem PHP) e `C:\xampp\htdocs` guarda ~20 projetos antigos,
  além de bases em `C:\xampp\mysql\data`. O XAMPP segue instalado, fora do PATH. Efeito
  colateral aceito pelo Ícaro: os projetos antigos do `htdocs` passam a ver PHP 8.4 e não
  serão mais mexidos; se algum precisar, o WAMP tem `php8.2.29`.
- **Framework de frontend: Next.js + React + TypeScript** (2026-08-29, ADR-0003). Catálogo
  público renderizado no servidor (SEO); área logada renderizada no cliente (evita a
  armadilha SSR + Sanctum); nenhuma regra de negócio no `web/`.
- **Ambiente de desenvolvimento montado** (2026-08-29, BORA-31): `api/` com Laravel 13.29.0
  sobre o **PHP 8.4.15 do WAMP** (o `php` do PATH é o do XAMPP 8.2.4 e **não** serve),
  MySQL 8.4.7 na porta 3306 (MariaDB fica na 3307, não usado), base `bora`, migrações
  rodadas em InnoDB; `web/` com Next 16.3.3, React 19.2.8, TypeScript 5 e Tailwind 4;
  Node atualizado de 18.18.0 para **24.19.0 LTS** (Next 16 exige ≥ 20.9.0).
- **Redis ativo em desenvolvimento** (2026-08-29), fechando a pendência aberta no setup.
  Servidor **Redis 8.2.5** em `127.0.0.1:6379` (standalone), instalado pelo Ícaro. O PHP
  8.4 do WAMP **não trazia** a extensão: foi instalada a `php_redis.dll` 6.3.0 (build
  `8.4-ts-vs17-x64`, do host oficial `downloads.php.net`) em
  `C:\wamp64\bin\php\php8.4.15\ext\` e habilitada em `php.ini` **e** `phpForApache.ini`
  (backups `.bak-antes-redis` ao lado). `CACHE_STORE`, `SESSION_DRIVER` e
  `QUEUE_CONNECTION` apontam para `redis`. Verificado pela facade `Cache` do Laravel, com a
  chave localizada no **db 1** do servidor. Escolhido phpredis e não predis para o dev ficar
  igual à produção Linux. **Risco conhecido:** se o WAMP atualizar o PHP, a DLL deixa de
  casar com o build e precisa ser trocada.
- **`laravel/boost` como dependência de desenvolvimento** (2026-08-29): instalado **só o
  servidor MCP** (`--mcp`), sem `--guidelines` e sem `--skills`. Motivo: as diretrizes do
  pacote mandam "só criar documentação se o usuário pedir", o que contradiz a regra
  inegociável de documentar no mesmo commit; e a parte boa delas (contratos nas bordas,
  testes obrigatórios, conferir versão instalada) já está na constituição. A autoridade
  segue sendo `CLAUDE.md` da raiz + constituição.

## Próximo passo

1. **A spec 001 está fechada.** A validação visual (T118) foi feita em duas etapas: o
   percurso de conta no **celular** (criar conta, cabeçalho mudando sozinho, sair, entrar,
   união — "tudo funcionou, o cabeçalho mudou sozinho e nada ficou apertado"), e a **faixa
   "Definir senha"** no computador, com login Google real, porque conta só-Google não é
   criável no celular sem túnel HTTPS. **Fronteira registrada de propósito:** o login com
   Google e a união **nunca** foram validados no celular — só no computador —, e continuarão
   assim enquanto a ferramenta de túnel for decisão pendente.
2. **Assunto do "template": tratado em paralelo e agora commitado**, em
   `docs/product/design-system.md` (sessões de 2026-09-01 e 02). Define o template como
   quatro camadas — kit de UI/tema, shell, receitas de tela, contrato + portão — e marca a
   origem de cada decisão (Ícaro / Proposta / Derivada). **Dezenove decisões**: as camadas
   1, 2 e 4 mais a **camada 3 inteira** (D13–D19, arquétipos de tela). Está como *decisões
   travadas, norma ainda não escrita*: **não é vinculante** até as lacunas da seção "Em
   aberto" fecharem e o Ícaro ratificar (BORA-39 — a metade "commitar" está feita, falta
   ratificar). Enquanto isso, a régua vinculante segue sendo `ux-requirements.md`.
   **Em 2026-09-02 o Ícaro ratificou D8, D10, D11 e as duas metades da D19**, e a **D20**
   fechou a camada 3 (hierarquia do Detalhe; a barra de navegação some no Detalhe e o
   "voltar" vira link para destino nomeado, nunca `history.back()`).
   **Lacunas que restam** (todas na BORA-39): medir o orçamento real de caracteres dos
   rótulos da barra na fonte real; escolher a tipografia (hoje é o Geist do scaffold, por
   omissão); a lista de papéis semânticos de cor; e ratificar as quatro propostas que nunca
   foram à mesa — **D13** (os quatro arquétipos), **D17** (o Formulário existente virar
   obrigatório), **D18** (a Ferramenta receber política da API como parâmetro) e o refluxo
   em duas linhas recomendado na **D12**.
3. **Inventário do que falta para a próxima spec (cadastro/perfil de local).** Levantado,
   não implícito:
   - **A spec 002 está ESCRITA, APROVADA, PLANEJADA e COM TAREFAS GERADAS** —
     `specs/002-cadastro-perfil-local/` (BORA-52). Portão `/spec-check`: SIM em 2026-09-08,
     depois de cinco bloqueantes corrigidos. Revisada com o Ícaro em 2026-09-09 (cinco
     problemas que o portão não pega; a ordem das histórias mudou) e **aprovada por ele** na
     mesma data. `/speckit-plan` rodado em 2026-09-09: existem `plan.md`, `research.md`,
     `data-model.md`, `contracts/locais-api.md` e `quickstart.md`. `/speckit-tasks` rodado em
     2026-09-09: **`tasks.md` com 159 tarefas** em sete fases (as T157–T159 entraram em
     2026-09-10, com o papel de operação).
     **EM IMPLEMENTAÇÃO desde 2026-09-12.** Feito: **T157–T159** (papel de operação — os
     testes, que nunca haviam rodado por o MySQL local estar parado, rodaram e passaram:
     quatro casos, 16 asserções); **Phase 1 Setup inteira** (T001–T004); e **a camada 4 da
     Phase 2, o portão de conformidade de tela** (T005–T008).
     **Parado na T009**, que é decisão do Ícaro — ver "Decisões pendentes que bloqueiam
     spec", no topo deste arquivo.
     **O portão fez o que devia: reprovou 32 das 36 combinações** das telas já validadas da
     spec 001. Relatório item por item em `docs/logs/error-log.md`, seção "T008". Os achados
     pertencem ao retrofit **T021–T025** — corrigir tela não é tarefa do portão. Dois achados
     confirmam dívidas já conhecidas por outro caminho: a tela de início é a **BORA-43** (o
     scaffold) e o `Alert` de erro tem contraste de 4:1 contra os 4,5:1 exigidos.
     **O próprio portão tinha dois furos, achados por rodá-lo** — que é para isso que a T008
     existe. Corrigidos; ver `E-025`.
     **Uma asserção do portão continua NÃO PROVADA:** a de "ícone sem rótulo de texto". Em
     toda a spec 001 existe um único controle com ícone, e ele tem texto — então a asserção
     nunca teve o que reprovar, e rodá-la não é evidência de que funciona. **Conferir de novo
     na T016–T023**, quando o shell trouxer a barra de navegação com ícone + rótulo.
     **Quatro paradas obrigatórias, uma por história** (Princípio XI): validação visual do
     Ícaro em **T074, T087, T123 e T140**. A história seguinte **não abre** antes da parada
     da atual, mesmo onde a dependência técnica permitiria paralelo — é a mitigação escrita
     na D6 para o risco de a spec 002 acumular fundação mais quatro histórias.
     **As duas pendências que a geração das tarefas abriu foram decididas** em 2026-09-10 —
     ver "Abertas pela spec 002", abaixo.
   - **Ordem das histórias** (revisada): **P1** cadastro + página pública · **P2** lista e
     busca · **P3** reivindicação com aprovação manual · **P4** perfil rico. A **fundação é
     fase bloqueante** (Phase 2 Foundational), não história.
   - **Única verificação ainda em aberto na fundação:** medir o orçamento de caracteres dos
     rótulos da barra, na fonte real, a 360px, sob zoom do navegador **e** sob fonte do
     sistema ampliada. Já está alocada como tarefa; os números atuais são estimativa. A
     outra verificação (comportamento do `next/font`) foi **resolvida** em 2026-09-09 lendo
     o pacote instalado.
   - As três decisões que a bloqueavam caíram antes: BORA-22 (2026-09-03), BORA-20
     (2026-09-03) e BORA-21 (2026-09-05).
   - ~~BORA-22 verificação de propriedade~~ → `RN-LOCAL-005`: qualquer conta cria o perfil,
     ele nasce **não reivindicado**, e a porta da verificação fica na **publicação de
     evento**. Fase 1 verifica por **aprovação manual**.
   - ~~BORA-20 redes/franquias~~ → `RN-LOCAL-004`: um perfil por unidade física, sem
     entidade "rede" na Fase 1, vínculo gestor↔local **N:N** (Princípio I).
   - ~~BORA-21 categorias~~ → `RN-LOCAL-002`: **bar, restaurante, casa de shows**, sem
     limite por local (fecha também a BORA-51). Choperia e petiscaria são bar; espetaria e
     churrascaria são restaurante. **Estimada, não medida** — a contagem em campo é a
     BORA-47 e segue por fazer.
   - **Não bloqueiam:** BORA-49 e BORA-50 (geradas pela BORA-22).

   **Abertas pela spec 002 na geração das tarefas (2026-09-09) e DECIDIDAS pelo Ícaro em
   2026-09-10. Nenhuma das duas chegou a virar issue no Linear — foram abertas e fechadas
   entre duas sessões:**

   1. ~~**Como uma conta ganha a permissão de operação?**~~ → **papel semeado + comando
      artisan** (2026-09-10). `account.operation_role` em `api/config/bora.php` com o valor
      `operator`, semeado pelo `RolesSeeder`; a concessão é o comando
      `bora:grant-operator {email}`, idempotente e auditado. Tarefas **T157, T158 e T159**.
      **Motivo de não ser concessão à mão no banco:** a T037 roda `migrate:fresh --seed`,
      que apaga a concessão, e isso se repete a cada recriação do esquema. A alternativa não
      sobrevivia ao próprio fluxo da feature. **Sem tela, de propósito** — conceder papel de
      operação é ato de plataforma, não funcionalidade de usuário, e o Princípio XI cobra
      tela para feature de produto. Virou **FR-027** na spec.
      **Ponta solta fechada no mesmo dia:** a regra virou **`RN-PLAT-007`** em
      `docs/domain/plataforma.md`, escrita pelo Ícaro. Confirmou o que o arquivo já indicava
      — a `RN-PLAT-001` governa papéis **de produto**, que se acumulam por cadastro, e papel
      que **nunca se autoatribui** é de outra natureza, logo `RN` nova, não emenda. A regra
      também fecha um buraco mais antigo: a `RN-LOCAL-005` manda aprovar reivindicação à mão
      e a `RN-PLAT-004` cita "operador da plataforma" em moderação, e **nenhuma das duas
      dizia quem podia ser esse operador**. As três ganharam ponteiro recíproco.
   2. ~~**`cover_path` existe no modelo e em nenhum outro lugar.**~~ → **retirado do
      modelo** (2026-09-10). **Motivo:** o `data-model.md` guarda `latitude`/`longitude`
      vazios **com destino nomeado** (BORA-8) — esse é o precedente bom. O `cover_path`
      tinha a coluna e **não tinha o destino**: nem contrato, nem spec, nem tarefa. Coluna
      anulável sem regra é o que alguém preenche sem saber o que significa duas specs
      adiante. Volta por migration quando a capa for pedida. **Conferido:** não restou
      ocorrência em código, contrato, spec ou tarefa — só o registro histórico aqui e no
      `linear-import.md`. A T026 passou a **nomear os três campos ricos** (`description`,
      `instagram`, `logo_path`), para a migration escrita depois não recriar a coluna por
      inércia.

   **Recorte acordado com o Ícaro em 2026-09-06, para o `/speckit-specify` usar:**

   A **D6** do `design-system.md` mandou kit, shell, receitas de tela e o retrofit das telas
   da 001 para dentro desta spec. Somado à API de local, isso é spec grande — e escopo
   grande é onde a validação visual do Princípio XI vira carimbo. **O `tasks-template.md`
   resolve isso estruturalmente**, e não por disciplina: ele tem uma **Phase 2 —
   Foundational (Blocking Prerequisites)**, com o aviso literal *"No user story work can
   begin until this phase is complete"*.

   Registrado de propósito: o assistente ia propor a fundação **como P1**, e o
   `spec-template.md` não permite — ele exige que cada história, sozinha, entregue um MVP
   viável com valor, e fundação não dá valor a usuário nenhum. A fase Foundational faz o
   trabalho melhor do que a ideia original.

   - **Phase 2 (Foundational, bloqueia todas as histórias):** o portão (camada 4) **primeiro**,
     como a D6 mandou; kit de UI com a régua embutida no componente (o `Button` de 32px vira
     44px); as duas lacunas do `design-system` — **tipografia** e **papéis semânticos de
     cor**; shell de consumo; **retrofit das telas da 001**; modelo e migrations de local.
   - **P1 — "Cadastro meu bar e vejo a página dele no ar."** Valor demonstrável numa frase: o
     bar passa a existir na internet com link compartilhável. Força a existir o Formulário
     (que já existe — D17) e o **Detalhe**, que é a receita mais rica, além da fronteira
     servidor/cliente do ADR-0003.
   - **P2** — reivindicação com aprovação manual · **P3** — lista e busca · **P4** — perfil
     rico (o prêmio da reivindicação).
   - **A P1 não precisa do shell de gestão completo.** A D10 diz Agenda + Perfil, mas
     *Agenda* só faz sentido quando houver eventos, que é outra spec. Na 002 o shell de
     gestão nasce só com o Perfil.
   - **Bloqueia a tela, não a spec:** identidade visual PENDENTE em `brand.md` (BORA-25) — o
     redesenho (logo flat, dark-first, tokens semânticos) e o Figma aposentado. O
     `design-system.md` do item 2 é o caminho para destravar isto.
   - **Não bloqueia:** a home ainda é o scaffold do Next (ver dívidas técnicas). Ela vira
     tela de produto na spec do feed, não na de local.
4. **Só então abrir a próxima feature.** O Princípio XI proíbe começar a próxima antes de a
   atual estar pronta — e agora ela está.
5. Governança: emenda constitucional registrando o Resend como provedor de e-mail
   transacional (decisão D8 da spec 001).
6. **A revisitar quando o `laravel/socialite` suportar guzzle 8** — hoje o projeto fica em
   guzzle 7.15.5 por causa dele; a volta é um `composer update`.
7. **Linear sincronizado em 2026-09-02.** Marco M1 em 100%. Fechadas: BORA-34 (T118),
   BORA-35 (T119), **BORA-44** (T120 — criada a pedido do Ícaro) e **BORA-9** (`RN-DESC-003`,
   salvar ≠ seguir, com o rótulo `decisao-pendente` removido). Abertas para o que ficou
   pendente: BORA-39 (ratificar o `design-system.md` — a metade "commitar" está feita),
   BORA-40 (portão que não cobra o caminho até a tela nem os campos do payload), BORA-41
   (excluir conta / LGPD), BORA-42 (`lint`), BORA-43 (home no scaffold) e **BORA-45**
   (cancelamento notifica quem só salvou?). Detalhe em `linear-import.md`.
