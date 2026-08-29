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
| **Redis**: a constituição exige Redis 7.0+ e a máquina de dev não tem (não há build oficial para Windows). Hoje cache/sessão/fila estão em `database`. Decidir entre WSL2, Memurai, Docker ou emendar a constituição para "Redis só em produção" | dev e deploy | constituição (Stack), BORA-31 |
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

- **Framework de frontend: Next.js + React + TypeScript** (2026-08-29, ADR-0003). Catálogo
  público renderizado no servidor (SEO); área logada renderizada no cliente (evita a
  armadilha SSR + Sanctum); nenhuma regra de negócio no `web/`.
- **Ambiente de desenvolvimento montado** (2026-08-29, BORA-31): `api/` com Laravel 13.29.0
  sobre o **PHP 8.4.15 do WAMP** (o `php` do PATH é o do XAMPP 8.2.4 e **não** serve),
  MySQL 8.4.7 na porta 3306 (MariaDB fica na 3307, não usado), base `bora`, migrações
  rodadas em InnoDB; `web/` com Next 16.3.3, React 19.2.8, TypeScript 5 e Tailwind 4;
  Node atualizado de 18.18.0 para **24.19.0 LTS** (Next 16 exige ≥ 20.9.0).
- **`laravel/boost` como dependência de desenvolvimento** (2026-08-29): instalado **só o
  servidor MCP** (`--mcp`), sem `--guidelines` e sem `--skills`. Motivo: as diretrizes do
  pacote mandam "só criar documentação se o usuário pedir", o que contradiz a regra
  inegociável de documentar no mesmo commit; e a parte boa delas (contratos nas bordas,
  testes obrigatórios, conferir versão instalada) já está na constituição. A autoridade
  segue sendo `CLAUDE.md` da raiz + constituição.

## Próximo passo

1. **Spike descartável do frontend (M0)** — página pública consumindo um `GET` simples da
   API, sem login, **fora da Definition of Done**: absorve a curva de Next/React/CORS antes
   que o relógio da spec 001 comece. Decidido junto com o ADR-0003.
   **Pré-requisito descoberto no setup:** não existe `routes/api.php` — no Laravel 11+ ele
   só nasce com `php artisan install:api`, que instala o Sanctum junto. Rodar antes do
   spike; a *política* de CORS/token continua sendo assunto da spec 001.
2. Depois, rodar `/specify` da primeira feature: **fundação de contas e autenticação**
   (conta única multi-papel + login Google/e-mail — `RN-PLAT-001/002`), que todo o resto
   pressupõe. Segunda na fila: cadastro/perfil de local, que destrava o catálogo. Atenção ao
   Princípio XI: essa spec já inclui a **tela** (login/cadastro).
