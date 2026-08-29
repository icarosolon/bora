# Backlog

Status: pendências extraídas da ideia original e das decisões de 2026-08-28. Nenhuma spec
escrita ainda.

## Decisões pendentes que bloqueiam spec

Cada item abaixo precisa ser decidido **antes** da spec que depender dele.

| Item | Bloqueia | Fonte |
|---|---|---|
| Registro de marca "Bora" (INPI) + domínio + @ nas redes | material público, lançamento | `brand.md` |
| Setup de CORS/Sanctum SPA e padrão de documentação da API (OpenAPI) | primeira feature | ADR-0002, Princípio IV |
| Teste informal de usabilidade com usuário de baixo letramento digital (idoso) | lançamento Fase 1 | `ux-requirements.md` |
| Hospedagem (agora precisa hospedar **também um processo Node**, além do PHP) | deploy | constituição, ADR-0003 |
| Fluxo de confirmação ao unir credenciais Google ↔ e-mail/senha | cadastro/login | `RN-PLAT-002` |
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

1. **Rodar `/specify` da primeira feature: fundação de contas e autenticação** (conta única
   multi-papel + login Google/e-mail — `RN-PLAT-001/002`), que todo o resto pressupõe.
   Atenção ao Princípio XI: essa spec já inclui a **tela** (login/cadastro). Insumos que o
   spike deixou prontos para ela:
   - **política de CORS + Sanctum** ainda a decidir (o default `allowed_origins: "*"` do
     framework não serve com credenciais);
   - **base de UI e conjunto de testes de front** (Tailwind + primitivas acessíveis,
     runner, Testing Library, `axe`) — action item do ADR-0003, ainda aberto;
   - lembrete do spike: **nada de segredo em prop que cruza para componente cliente**.
2. Segunda na fila: cadastro/perfil de local, que destrava o catálogo.
3. Apagar o andaime do spike quando a spec 001 tiver sua própria tela:
   `api/app/Http/Controllers/Spike/`, o bloco `v1/eventos` de `api/routes/api.php` e
   `web/src/app/eventos/`.
