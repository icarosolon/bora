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

## Próximo passo

1. **Spike descartável do frontend (M0)** — página pública consumindo um `GET` simples da
   API, sem login, **fora da Definition of Done**: absorve a curva de Next/React/CORS antes
   que o relógio da spec 001 comece. Decidido junto com o ADR-0003.
2. Depois, rodar `/specify` da primeira feature: **fundação de contas e autenticação**
   (conta única multi-papel + login Google/e-mail — `RN-PLAT-001/002`), que todo o resto
   pressupõe. Segunda na fila: cadastro/perfil de local, que destrava o catálogo. Atenção ao
   Princípio XI: essa spec já inclui a **tela** (login/cadastro).
