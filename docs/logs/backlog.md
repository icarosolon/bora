# Backlog

Status: pendências extraídas da ideia original e das decisões de 2026-08-28. Nenhuma spec
escrita ainda.

## Decisões pendentes que bloqueiam spec

Cada item abaixo precisa ser decidido **antes** da spec que depender dele.

| Item | Bloqueia | Fonte |
|---|---|---|
| Registro de marca "Bora" (INPI) + domínio + @ nas redes | material público, lançamento | `brand.md` |
| Framework de frontend (**SEO e acessibilidade são critérios eliminatórios**) | primeira tela (Princípio XI) | constituição, ADR-0002 |
| Setup de CORS/Sanctum SPA e padrão de documentação da API (OpenAPI) | primeira feature | ADR-0002, Princípio IV |
| Teste informal de usabilidade com usuário de baixo letramento digital (idoso) | lançamento Fase 1 | `ux-requirements.md` |
| Hospedagem | deploy | constituição |
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
| Preço dos planos B2B e limite do plano grátis (validar no mercado local) | monetização Fase 1 | `monetization.md` |
| Critério numérico para ativar a cobrança (fim da Fase 0) | monetização Fase 1 | `monetization.md` |
| Gateway de pagamento | bilheteria (Fase 3), Pix da divisão | `monetization.md`, `RN-CONTA-001` |
| Redesenho da identidade visual (logo flat, dark-first, tokens semânticos) | UI | `brand.md` |

## Infra do método

- **Linear**: conector não autenticado na sessão de setup — estrutura pronta em
  `docs/logs/linear-import.md`; criar projeto e issues quando o Ícaro autorizar o conector.

## Próximo passo

Escolher a primeira feature e rodar `/specify`. Candidato natural: **fundação de contas e
autenticação** (conta única multi-papel + login Google/e-mail — `RN-PLAT-001/002`), que
todo o resto pressupõe. Segunda na fila: cadastro/perfil de local, que destrava o catálogo.
Atenção ao Princípio XI: a primeira spec já inclui a **tela** (login/cadastro) — o que
torna a decisão do framework de frontend pré-requisito imediato.
