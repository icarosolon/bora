# Backlog

Status: pendências extraídas da ideia original e das decisões de 2026-08-28 em diante.
Spec 001: as quatro user stories entregues e validadas; falta a fase de Polish (T110-T119).

## Decisões pendentes que bloqueiam spec

Cada item abaixo precisa ser decidido **antes** da spec que depender dele.

| Item | Bloqueia | Fonte |
|---|---|---|
| Registro de marca "Bora" (INPI) + domínio + @ nas redes | material público, lançamento, **envio real de e-mail (SPF/DKIM — spec 001)** | `brand.md` |
| Teste informal de usabilidade com usuário de baixo letramento digital (idoso) | lançamento Fase 1 | `ux-requirements.md` |
| Hospedagem (agora precisa hospedar **também um processo Node**, além do PHP) | deploy | constituição, ADR-0003 |
| **Ferramenta de túnel HTTPS** (ngrok, Cloudflare Tunnel…) para validar o **login com Google no celular** — o IP de rede local não serve como URI de redirecionamento, o Google só aceita HTTP em loopback | **validação visual da spec 001 no celular** (Princípio XI, US2). As telas sem Google validam por IP de rede local, sem túnel | `ux-requirements.md`, `specs/001-contas-autenticacao/` |
| Emenda constitucional formalizando o Resend como provedor de e-mail transacional (decisão já tomada — ver Decisões tomadas) | governança | constituição (lista PENDENTE do Stack) |
| Cidade do usuário: geolocalização, escolha manual, múltiplas cidades | feed, busca | `RN-PLAT-006` |
| Verificação de propriedade do estabelecimento | cadastro de local | `RN-LOCAL-001` |
| Lista inicial de categorias de local | cadastro de local, filtros | `RN-LOCAL-002` |
| Redes/franquias: um perfil por unidade? | cadastro de local | `RN-LOCAL-004` |
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
| "Salvar" e "seguir": uma ação ou duas | descoberta | `RN-DESC-003` |
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

## Infra do método

- **Linear**: projeto **Bora** criado em 2026-08-28
  (<https://linear.app/icasst/project/bora-f0ad76fe7e09>), migrado em 2026-08-29 para o
  **time próprio `Bora`/BORA**, com marcos M0–M5 e uma issue `decisao-pendente` por item da
  tabela acima (BORA-1..BORA-29) + setup (BORA-30, BORA-31).
  Ver `docs/logs/linear-import.md`.

## Decisões tomadas

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

1. **Fechar a spec 001 com a fase de Polish (T110–T119).** Não é acabamento: contém itens
   da Definition of Done do Princípio XI. Em especial — **gerar a documentação da API com o
   Scramble e conferi-la contra `contracts/auth-api.md`** (o princípio exige API
   *documentada*), **remover o andaime do spike** (`api/app/Http/Controllers/Spike/`, o
   bloco `v1/eventos` e `web/src/app/eventos/`), verificar que **nenhum token cruza para
   prop de componente cliente nem aparece em log**, e rodar o `quickstart.md` inteiro.
2. **Só então abrir a próxima feature.** O Princípio XI proíbe começar a próxima antes de a
   atual estar pronta — e "pronta" inclui o Polish.
3. Segunda na fila: cadastro/perfil de local, que destrava o catálogo (`/speckit-specify`).
4. Governança: emenda constitucional registrando o Resend como provedor de e-mail
   transacional (decisão D8 da spec 001).
5. **A revisitar quando o `laravel/socialite` suportar guzzle 8** — hoje o projeto fica em
   guzzle 7.15.5 por causa dele; a volta é um `composer update`.
