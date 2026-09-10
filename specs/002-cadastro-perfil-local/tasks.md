# Tasks: Cadastro e Perfil de Estabelecimento

**Input**: documentos de projeto em `specs/002-cadastro-perfil-local/`

**Prerequisites**: [plan.md](./plan.md) e [spec.md](./spec.md) (obrigatórios);
[research.md](./research.md), [data-model.md](./data-model.md),
[contracts/locais-api.md](./contracts/locais-api.md), [quickstart.md](./quickstart.md).

**Testes**: **obrigatórios nesta spec.** Não é opção do template — o Princípio IX da
constituição exige teste de backend **e** de frontend, um teste por `RN-<CTX>-NNN`
referenciada e um teste que **prove o bloqueio** de cada princípio NON-NEGOTIABLE tocado. A
seção "Cenários de Teste" da spec enumera quais.

**Organização**: agrupadas por história de usuário, para cada uma ser implementada, testada
e **validada visualmente pelo Ícaro** de forma independente (Princípio XI).

## Formato: `[ID] [P?] [Story] Descrição`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência pendente)
- **[Story]**: a qual história pertence (US1, US2, US3, US4)
- Todo caminho de arquivo é explícito

**Regra que vale para toda tarefa desta lista, sem repeti-la em cada linha:** tarefa que
altera comportamento **carrega a atualização da documentação no mesmo commit** — regra do
`CLAUDE.md` e do `development-workflow.md`. Se a tarefa muda uma regra transversal, o
`docs/domain/locais.md` entra no mesmo commit; se muda uma decisão de estrutura, o
`docs/product/design-system.md` entra junto; se muda contrato, o
`contracts/locais-api.md` entra junto.

## Convenções de caminho

Duas aplicações desacopladas no mesmo repositório (ADR-0002): `api/` (Laravel 13) e `web/`
(Next.js 16, App Router). Identificador em inglês, prosa em português, caminho de URL em
português (`docs/architecture/naming-conventions.md`).

---

## Phase 1: Setup (infraestrutura compartilhada)

**Purpose**: abrir espaço para a feature sem mudar comportamento, e fixar a linha de base.

- [ ] T001 [P] Criar a árvore de pastas do contexto Venue na API: `api/app/Domain/Venue/`, `api/app/UseCases/Venue/`, `api/app/Http/Requests/Venue/`, `api/tests/Feature/Venue/`, `api/tests/Unit/Domain/Venue/`
- [ ] T002 [P] Criar a árvore de pastas do front: `web/src/components/shell/`, `web/src/components/venue/`, `web/src/app/locais/`, `web/tests/e2e/gate/`
- [ ] T003 [P] Registrar a linha de base verde das três suítes antes de qualquer mudança (`cd api && php artisan test`, `cd web && npm test`, `cd web && npm run test:e2e`) em `docs/logs/error-log.md` — é contra ela que o retrofit da spec 001 será conferido
- [ ] T004 [P] Configurar `allowedDevOrigins` com o IP da máquina na rede em `web/next.config.ts`, para a validação no aparelho não travar (E-013)

---

## Phase 2: Foundational (pré-requisitos bloqueantes)

**Purpose**: a fundação que a spec 002 carrega por decisão **D6** do `design-system.md`.

**⚠️ CRÍTICO**: **nenhuma tarefa de história vem antes desta fase terminar.** Validar tela
antes disso é validar tela que vai ser refeita (`quickstart.md`).

**A composição e a ordem estão fixadas** na seção "Nota de sequenciamento — a fundação é
fase, não história" da `spec.md`, e são estas seis, nesta ordem:

1. **Portão de conformidade de tela**, rodado contra as telas já entregues da spec 001 (T005–T009)
2. **Kit de UI com o alvo de 44px embutido no componente**, não corrigido por chamada (T010–T015)
3. **Tipografia e papéis semânticos de cor** (T010–T011, dentro do bloco do kit)
4. **Shell de consumo** em `web/src/components/shell/` (T016–T023)
5. **Retrofit das telas de conta da spec 001** (T024–T025)
6. **Modelo e migrations de local** (T026–T037)

O portão vem primeiro **de propósito** (D4): portão escrito depois do kit nasce moldado ao
kit e não reprova ninguém (E-012). Os blocos 2 e 4 não se bloqueiam, e o bloco 6 é
independente do front inteiro.

### Camada 4 — Portão de conformidade de tela (primeiro)

- [ ] T005 Escrever o portão de conformidade de tela em `web/tests/e2e/gate/screen-conformance.spec.ts`, asserindo por tela, em **360 e 1280**: alvo de toque ≥ 44px, fonte base ≥ 16px, ausência de rolagem horizontal a 360px, `axe` **sem nenhuma violação**, nenhum ícone sem rótulo de texto, nenhuma informação só por cor e foco visível
- [ ] T006 Acrescentar dois projetos em `web/playwright.config.ts`, além dos `celular-360` e `computador-1280` que já existem: `celular-390` (faixa 390–430, exigida no Detalhe por ser a tela mais densa) e `fonte-ampliada-360` (viewport 360 com a fonte do sistema ampliada) — zoom de navegador e fonte do sistema são mecanismos diferentes, e o segundo não dispara `media query` (D12)
- [ ] T007 Acrescentar ao portão a passagem com **fonte do sistema ampliada** em `web/tests/e2e/gate/screen-conformance.spec.ts`, cobrindo o ponto cego que a D12 registrou — reduzir a largura não é o mesmo cenário
- [ ] T008 Rodar o portão contra as **telas já validadas da spec 001** e registrar em `docs/logs/error-log.md` **o que ele acusou**. Portão que não acusa nada é portão fraco, e isso precisa ser descoberto agora (E-012)
- [ ] T009 [P] Proibir cor literal em tela por regra de lint em `web/eslint.config.mjs` — hoje `page.tsx` e `alert.tsx` já quebram a regra "nenhuma tela escreve cor literal, só token"

### Camada 1 — Kit de UI e tema (paralelo à camada 2)

- [ ] T010 [P] Definir os papéis semânticos de cor da D22 em `web/src/app/globals.css`, redefinidos sob `prefers-color-scheme` (D3, não por classe `.dark`), removendo `sidebar-*` e `chart-1..5`
- [ ] T011 [P] Trocar a tipografia em `web/src/app/layout.tsx` para **uma família, dois pesos**, via `next/font` com os padrões (`display: 'swap'`, `adjustFontFallback: true`), e **remover o Geist Mono** (D21, R1)
- [ ] T012 Corrigir o alvo padrão para ≥ 44px em `web/src/components/ui/button.tsx` — hoje o `size: default` é `h-8` (32px) e só passa porque cada chamada corrige com `min-h-11`
- [ ] T013 [P] Substituir a cor literal `emerald-600` por token de papel em `web/src/components/ui/alert.tsx`
- [ ] T014 [P] Criar o `CategorySelect` (múltipla escolha, sem limite, rótulo de texto, alvo ≥ 44px) em `web/src/components/ui/category-select.tsx` — é o único componente novo que a fundação acrescenta (D17); área de texto e envio de foto ficam para a P4
- [ ] T015 [P] Estender o teste de componente do kit em `web/tests/unit/primitives.test.tsx`: `Button` **sem** `className` mede ≥ 44px, `CategorySelect` sem violação de `axe`, nenhum componente com cor literal

### Camada 2 — Shell de consumo, e o de gestão só com Perfil (paralelo à camada 1)

- [ ] T016 Criar o shell de consumo com a barra inferior de cinco itens (`Hoje`, `Buscar`, `Salvos`, `Dividir`, `Conta`), ícone **+ rótulo**, em `web/src/components/shell/ConsumptionShell.tsx` (D9)
- [ ] T017 Fazer a barra virar **trilho lateral** a partir de 768px, mesmos itens e mesma ordem, em `web/src/components/shell/ConsumptionShell.tsx` (D11)
- [ ] T018 Implementar o refluxo **por conteúdo** (`flex-wrap` ou container query, nunca `@media` de largura), quebrando em duas linhas com rótulo em **todos** os itens, em `web/src/components/shell/ConsumptionShell.tsx` (D12)
- [ ] T019 [P] **Medir** o orçamento de caracteres dos rótulos da barra (R2). **Critério de pronto declarado pelo próprio R2**: renderizar `Hoje`, `Buscar`, `Salvos`, `Dividir` e `Conta` num navegador de verdade, na família escolhida ou num substituto de métrica equivalente, a 360px, sob **zoom de 200%** e sob **fonte do sistema ampliada** — que são mecanismos diferentes — e usar o resultado para definir **onde a barra quebra em duas linhas**. Substituir em `docs/product/design-system.md` os números da D12, que hoje são **estimativa (~0,5em de avanço médio por caractere) e não medição**, pelos valores medidos, dizendo que foram medidos e como
- [ ] T020 Mover o `AccountHeader` do layout raiz para dentro do shell, em `web/src/app/layout.tsx` e `web/src/components/shell/ConsumptionShell.tsx` — o shell solto no layout raiz foi a causa do E-016
- [ ] T021 Substituir o boilerplate do `create-next-app` em `web/src/app/page.tsx` por uma home mínima dentro do shell, sem cor literal (a home "Hoje" de verdade é outra spec)
- [ ] T022 Criar o shell de gestão com **apenas Perfil**, mais o seletor de moldura persistente no topo (D8), em `web/src/components/shell/ManagementShell.tsx` — **sem Agenda**: agenda depende de eventos, que são de outra spec, e item de menu que não leva a lugar nenhum é dívida visível ao usuário (D10)
- [ ] T023 [P] Teste e2e do shell em `web/tests/e2e/shell.spec.ts`: cinco itens com rótulo a 360, trilho a 1280, duas linhas sob fonte ampliada, nenhum item dependente de quem olha, e a moldura de gestão **sem** item de Agenda

### Retrofit das telas da spec 001

- [ ] T024 Retrofit das telas de conta para o kit novo, removendo o `min-h-11` copiado à mão, em `web/src/components/auth/BaseForm.tsx`, `web/src/components/auth/GoogleButton.tsx`, `web/src/components/auth/SignInForm.tsx`, `web/src/components/auth/SignUpForm.tsx` e `web/src/components/auth/MergeAccountsForm.tsx`
- [ ] T025 Rodar o portão e as suítes da spec 001 (`web/tests/e2e/us1-account.spec.ts` a `us4-recovery.spec.ts`, `web/tests/unit/`) e confirmar verde contra a linha de base do T003 — criar conta, entrar, sair, Google, unir contas, esqueci a senha

### Modelo de dados de local

- [ ] T026 [P] Migration da tabela `venues` em `api/database/migrations/`, com `slug` único, `name_normalized` indexada, endereço estruturado, `district`/`city`/`state` indexadas, `latitude`/`longitude` nulas, campos ricos nulos, `active` e `created_by_account_id` (data-model.md)
- [ ] T027 [P] Migration da tabela `venue_categories` em `api/database/migrations/` (`slug` único, `name`, `active`, `position`)
- [ ] T028 [P] Migration da tabela pivô `venue_venue_category` em `api/database/migrations/`, com chave única no par e **sem teto** de categorias
- [ ] T029 [P] Migration da tabela `venue_managers` em `api/database/migrations/` — vínculo **N:N** com `granted_at` e `granted_by_claim_id`
- [ ] T030 [P] Migration da tabela `venue_claims` em `api/database/migrations/`, com os quatro campos de evidência, `status`, `decided_by_account_id`, `decided_at`, `decision_reason` e `verification_method`
- [ ] T031 [P] Model `Venue` com `$fillable` explícito e relações em `api/app/Models/Venue.php`
- [ ] T032 [P] Model `VenueCategory` em `api/app/Models/VenueCategory.php`
- [ ] T033 [P] Model `VenueClaim` em `api/app/Models/VenueClaim.php`
- [ ] T034 [P] Relações `venues()` e `managedVenues()` na conta existente, em `api/app/Models/User.php` — **sem** criar conta paralela (Princípio I)
- [ ] T035 [P] Seeder das três categorias iniciais (bar, restaurante, casa de shows) em `api/database/seeders/VenueCategoriesSeeder.php`, registrado em `api/database/seeders/DatabaseSeeder.php`
- [ ] T036 [P] Factories `VenueFactory`, `VenueCategoryFactory` e `VenueClaimFactory` em `api/database/factories/`
- [ ] T037 Rodar `php artisan migrate:fresh --seed` e confirmar que o esquema sobe limpo e a suíte da API segue verde

**Checkpoint**: fundação pronta — portão com dente comprovado, kit com a régua embutida,
shell existindo, spec 001 verde e esquema de local no ar. As histórias podem começar.

---

## Phase 3: User Story 1 — Coloco um bar no Bora e vejo a página dele no ar (P1) 🎯 MVP

**Goal**: qualquer conta autenticada cadastra um bar com quatro campos e cai na página
pública dele, com endereço próprio, compartilhável e legível sem sessão.

**Independent Test**: entrar, cadastrar um bar com nome, endereço, categoria e telefone, ser
levado à página pública, copiar o endereço, abrir em janela anônima e ver a mesma página com
a linha de perfil não gerenciado.

> **A caixinha "sou eu que gerencio este bar" NÃO entra aqui** — é da US3, com a aprovação
> (FR-019). Se aparecer nesta fase, o sistema acumula pedido que ninguém pode analisar.

### Testes da User Story 1

> Escrever primeiro e confirmar que **falham** antes de implementar.

- [ ] T038 [P] [US1] Teste de criação — sucesso, falta de cada campo obrigatório, categoria inexistente, sem autenticação — em `api/tests/Feature/Venue/CreateVenueTest.php`
- [ ] T039 [P] [US1] Teste do perfil público magro: campos ricos vêm como `null` e **não vazam**, nem na resposta de API, em `api/tests/Feature/Venue/PublicVenueProfileTest.php` (FR-002, FR-015)
- [ ] T040 [P] [US1] Teste do bloqueio do **Princípio II**: as quatro rotas públicas respondem **sem token e sem cobrança**, em `api/tests/Feature/Venue/PublicAccessTest.php`
- [ ] T041 [P] [US1] Teste do aviso de duplicata por nome normalizado + mesmo bairro, que **avisa e não bloqueia**, em `api/tests/Feature/Venue/SimilarVenuesTest.php` (FR-020, `RN-LOCAL-004`)
- [ ] T042 [P] [US1] Teste de toque duplo em rede lenta não criando dois perfis, em `api/tests/Feature/Venue/CreateVenueDoubleSubmissionTest.php` (FR-018)
- [ ] T043 [P] [US1] Teste do bloqueio do **Princípio VIII**: a criação gera registro recuperável com quem e quando, em `api/tests/Feature/Venue/VenueAuditLogTest.php` (FR-010)
- [ ] T044 [P] [US1] Teste de `RN-LOCAL-002`: aceita 1, 2 e 3 categorias; recusa categoria fora da lista; **acrescentar categoria nova não exige mudar código**, em `api/tests/Feature/Venue/VenueCategoryTest.php`
- [ ] T045 [P] [US1] Teste de `RN-LOCAL-001`: conta **sem vínculo nenhum** cria perfil com sucesso, em `api/tests/Feature/Venue/CreateVenueTest.php`
- [ ] T046 [P] [US1] Teste unitário das invariantes da entidade — `slug` imutável, ao menos uma categoria, perfil não reivindicado recusa campo rico — em `api/tests/Unit/Domain/Venue/VenueTest.php`
- [ ] T047 [P] [US1] Teste unitário do gerador de `slug`, incluindo desambiguação pelo bairro na colisão, em `api/tests/Unit/Domain/Venue/VenueSlugTest.php` (R3)
- [ ] T048 [P] [US1] Teste unitário do normalizador de nome — minúscula, sem acento, sem pontuação, sem termo genérico inicial — em `api/tests/Unit/Domain/Venue/NameNormalizerTest.php` (R4)
- [ ] T049 [P] [US1] Teste de componente do formulário de cadastro (erro no campo, botão desabilitado durante o envio, `axe` limpo) em `web/tests/unit/venue-form.test.tsx`
- [ ] T050 [P] [US1] Teste e2e do percurso P1 em `web/tests/e2e/us1-venue.spec.ts`: cadastrar, cair na página pública, abrir sem sessão, conferir "Como chegar", "Ligar" e "Convidar", e a **chegada fria** com volta nomeada funcionando (D20, SC-004). Rodar em **360, 1280 e 390–430** — a faixa extra é obrigatória no perfil público, que é a tela mais densa da feature — com `axe` sem violação e uma passagem a mais com **fonte do sistema ampliada**

### Implementação da User Story 1 — API

- [ ] T051 [P] [US1] Entidade de domínio com as invariantes em `api/app/Domain/Venue/Venue.php` (a borda apenas reporta; a regra não vive no controller)
- [ ] T052 [P] [US1] Gerador de `slug` imutável, desambiguado pelo bairro, em `api/app/Domain/Venue/VenueSlug.php`
- [ ] T053 [P] [US1] Normalizador de nome para o aviso de duplicata em `api/app/Domain/Venue/NameNormalizer.php`
- [ ] T054 [US1] Caso de uso `CreateVenue` em `api/app/UseCases/Venue/CreateVenue.php`, com idempotência por identificador de submissão (FR-018) e auditoria (FR-010)
- [ ] T055 [US1] `CreateVenueRequest` com `authorize()` e `rules()` e mensagens em português do dia a dia, em `api/app/Http/Requests/Venue/CreateVenueRequest.php` — usa `validated()`, **nunca** `all()`, para campo rico enviado ser ignorado (Princípio V)
- [ ] T056 [P] [US1] `VenueResource` com `claimed`, categorias, `address.formatted` e campos ricos sempre presentes como `null`, em `api/app/Http/Resources/VenueResource.php`
- [ ] T057 [P] [US1] `VenueCategoryResource` em `api/app/Http/Resources/VenueCategoryResource.php`
- [ ] T058 [US1] `VenueController` com `store` e `show` (404 para local inativo) em `api/app/Http/Controllers/Api/V1/VenueController.php`
- [ ] T059 [US1] Endpoint de locais semelhantes (`GET /api/v1/locais/semelhantes`), que **avisa e não bloqueia**, em `api/app/Http/Controllers/Api/V1/VenueController.php`
- [ ] T060 [P] [US1] `VenueCategoryController` com `index` lendo a lista **de dado** em `api/app/Http/Controllers/Api/V1/VenueCategoryController.php`
- [ ] T061 [US1] Registrar as rotas em português em `api/routes/api.php` — as públicas **fora** do grupo autenticado, para não aceitarem nem lerem `Authorization`

### Implementação da User Story 1 — Front

- [ ] T062 [P] [US1] Tipos e chamadas de local em `web/src/lib/venues.ts`, reusando `web/src/lib/api.ts` — zero regra de negócio no front
- [ ] T063 [US1] Formulário de cadastro (receita Formulário: `BaseForm` + `Field` + `CategorySelect` + `Alert` + `useHydrated`, enviar abaixo dos campos na metade inferior) em `web/src/components/venue/VenueForm.tsx`
- [ ] T064 [US1] Página de cadastrar local (cliente) em `web/src/app/locais/novo/page.tsx`
- [ ] T065 [US1] Aviso de duplicata no formulário, mostrando o que encontrou e **deixando a decisão com quem cadastra**, em `web/src/components/venue/VenueForm.tsx` (FR-020)
- [ ] T066 [US1] Página pública do local **renderizada no servidor** (ADR-0003, sem tocar em token) em `web/src/app/locais/[slug]/page.tsx`, na receita Detalhe com hierarquia de três níveis
- [ ] T067 [P] [US1] Selo de estado com ícone **e** texto — "Perfil do estabelecimento" ou a linha de perfil não gerenciado — em `web/src/components/venue/VenueStateBadge.tsx` (FR-005, nunca só ícone, nunca só cor)
- [ ] T068 [US1] Fileira de ações do Detalhe com **apenas** "Como chegar" (principal, fixa na metade inferior), "Ligar" e "Convidar" — sem Salvar, sem Seguir — em `web/src/components/venue/VenueActions.tsx` (FR-024, FR-025, D16, D20)
- [ ] T069 [US1] "Como chegar" montando link para o app de mapas com o **endereço em texto**, sem provedor pago, em `web/src/components/venue/VenueActions.tsx` (`RN-DESC-004`, R5)
- [ ] T070 [US1] "Convidar" abrindo o compartilhamento do próprio aparelho com o link da página, com alternativa quando o aparelho não oferecer, em `web/src/components/venue/VenueActions.tsx` (FR-024)
- [ ] T071 [US1] Volta para destino **nomeado**, funcionando na chegada fria sem histórico (nunca `history.back()`), em `web/src/app/locais/[slug]/page.tsx` (D20, SC-004)
- [ ] T072 [US1] Fazer a barra de navegação **sumir no Detalhe** a 360px e o trilho **permanecer** a partir de 768px, em `web/src/components/shell/ConsumptionShell.tsx` e `web/src/app/locais/[slug]/page.tsx` (D20)

### Fechamento da User Story 1

- [ ] T073 [US1] Rodar o portão sobre as duas telas novas e as três suítes, e confirmar verde (`cd api && php artisan test`, `cd web && npm test`, `cd web && npm run test:e2e`)
- [ ] T074 [US1] **Validação visual do Ícaro** no celular primeiro, seguindo a seção P1 do `quickstart.md`. A US2 não começa antes desta aprovação (Princípio XI)

**Checkpoint**: o bar existe na internet com link compartilhável. É o MVP.

---

## Phase 4: User Story 2 — Encontro bares na lista e filtro por categoria (P2)

**Goal**: o rolezeiro abre a lista da cidade, vê nome e bairro de cada local e filtra por
categoria. É o que torna o catálogo navegável sem já ter o link.

**Independent Test**: com locais em categorias diferentes, abrir a lista, aplicar cada
filtro, ver o conjunto mudar e conferir que nenhum item traz informação que dependa de quem
está olhando.

### Testes da User Story 2

- [ ] T075 [P] [US2] Teste da lista pública paginada e dos filtros por categoria, cidade e busca, em `api/tests/Feature/Venue/ListVenuesTest.php`
- [ ] T076 [P] [US2] Teste do bloqueio da `D15`/FR-017: **nenhum** campo da lista depende de quem olha — resposta idêntica com e sem token — em `api/tests/Feature/Venue/PublicListStatelessTest.php`
- [ ] T077 [P] [US2] Teste de `RN-LOCAL-004`: cada item traz o **bairro** derivado do endereço, em `api/tests/Feature/Venue/ListVenuesTest.php`
- [ ] T078 [P] [US2] Teste de componente da lista e do filtro (rótulo de texto, linha inteira clicável ≥ 44px, estado vazio que ensina) em `web/tests/unit/venue-list.test.tsx`
- [ ] T079 [P] [US2] Teste e2e da lista em `web/tests/e2e/us2-venue-list.spec.ts`, em **360 e 1280**, com `axe` sem violação e uma passagem a mais com **fonte do sistema ampliada**

### Implementação da User Story 2

- [ ] T080 [US2] Método `index` paginado com filtros `categoria`, `cidade`, `busca` e `pagina` em `api/app/Http/Controllers/Api/V1/VenueController.php`
- [ ] T081 [P] [US2] `VenueListResource` com envelope `data`/`meta`/`links` e `district` em cada item, em `api/app/Http/Resources/VenueListResource.php`
- [ ] T082 [P] [US2] Cartão de item com nome e bairro, uma coluna a 360px, linha inteira clicável com altura ≥ 44px, em `web/src/components/venue/VenueCard.tsx`
- [ ] T083 [P] [US2] Filtro de categoria com **rótulo de texto** (nunca só ícone, nunca só cor), alimentado pela API, em `web/src/components/venue/VenueCategoryFilter.tsx`
- [ ] T084 [US2] Página da lista (cliente, receita Lista pública) em `web/src/app/locais/page.tsx`
- [ ] T085 [US2] Estado vazio que **ensina** o que fazer em vez de ficar em branco, em `web/src/app/locais/page.tsx`

### Fechamento da User Story 2

- [ ] T086 [US2] Rodar o portão sobre a tela de lista e as três suítes, e confirmar verde
- [ ] T087 [US2] **Validação visual do Ícaro**, seguindo a seção P2 do `quickstart.md`

**Checkpoint**: US1 e US2 funcionam independentes. O catálogo é navegável.

---

## Phase 5: User Story 3 — Reivindico o perfil do meu estabelecimento (P3)

**Goal**: o gestor pede a reivindicação com evidência; a plataforma aprova ou recusa à mão; a
aprovação **transfere sem recriar** e o solicitante é sempre avisado.

**Independent Test**: com um perfil não reivindicado, pedir a reivindicação por uma conta,
aprovar pelo caminho de operação, e ver o selo na página pública e a conta passando a editar.

### Testes da User Story 3

- [ ] T088 [P] [US3] Teste do pedido — sucesso e **falta de cada um dos quatro campos de evidência** — em `api/tests/Feature/Venue/RequestClaimTest.php` (FR-026)
- [ ] T089 [P] [US3] Teste de que um segundo pedido pendente para o mesmo local é **aceito** (não é 409) e os dois ficam pendentes, em `api/tests/Feature/Venue/ConcurrentClaimsTest.php` (FR-022)
- [ ] T090 [P] [US3] Teste de que pedido em local **já reivindicado** recebe 409, em `api/tests/Feature/Venue/RequestClaimTest.php`
- [ ] T091 [P] [US3] Teste do bloqueio do **Princípio X**: aprovar **transfere e não recria** — avaliações e histórico continuam ligados ao mesmo `venue_id` — em `api/tests/Feature/Venue/ApproveClaimTest.php` (FR-009)
- [ ] T092 [P] [US3] Teste de que aprovar **encerra os demais pendentes** como recusados com motivo e **avisa cada solicitante**, em `api/tests/Feature/Venue/ConcurrentClaimsTest.php` (FR-023)
- [ ] T093 [P] [US3] Teste de que recusar **exige motivo**, registra e dispara o aviso com caminho para falar com a plataforma, em `api/tests/Feature/Venue/RejectClaimTest.php` (FR-021)
- [ ] T094 [P] [US3] Teste do bloqueio do **Princípio I**: uma conta vira gestora de **dois** locais **sem** nenhuma conta paralela ser criada, em `api/tests/Feature/Venue/VenueManagerTest.php` (FR-012, `RN-LOCAL-004`)
- [ ] T095 [P] [US3] Teste do bloqueio do **Princípio V**: conta sem permissão de operação recebe recusa ao aprovar ou recusar, em `api/tests/Feature/Venue/ClaimAuthorizationTest.php`
- [ ] T096 [P] [US3] Teste do bloqueio do **Princípio VIII**: pedido, aprovação e recusa **cada um** geram registro com quem, quando e **por qual método**, em `api/tests/Feature/Venue/VenueAuditLogTest.php` (FR-010, `RN-LOCAL-005`)
- [ ] T097 [P] [US3] Teste do bloqueio de `RN-EVENTO-001`: local **não reivindicado** tem a publicação de evento recusada, em `api/tests/Feature/Venue/UnclaimedVenueCannotPublishTest.php` (FR-013 — o evento é outra spec; o bloqueio, não)
- [ ] T098 [P] [US3] Teste de que a caixinha "sou eu que gerencio este bar" marcada no cadastro abre o **pedido** junto com a criação, em `api/tests/Feature/Venue/CreateVenueWithClaimTest.php` (FR-019)
- [ ] T099 [P] [US3] Teste de componente do formulário de pedido (erro no campo por evidência faltando, `axe` limpo) em `web/tests/unit/venue-claim-form.test.tsx`
- [ ] T100 [P] [US3] Teste e2e do percurso P3 em `web/tests/e2e/us3-venue-claim.spec.ts`: pedir, ver dois pedidos lado a lado, aprovar, ver o selo na página pública, recusar com motivo. Em **360 e 1280**, mais **390–430** na página pública por causa do selo, com `axe` sem violação e passagem com **fonte do sistema ampliada**

### Implementação da User Story 3 — API

- [ ] T101 [P] [US3] Política de reivindicação no domínio, com o **método como parâmetro** e não como regra, em `api/app/Domain/Venue/ClaimPolicy.php` (FR-008 — trocar o método não pode exigir mudar regra)
- [ ] T102 [P] [US3] Estado reivindicado **derivado do histórico** (existe claim aprovada ⇒ reivindicado), sem `enum` no `Venue` que possa divergir, em `api/app/Domain/Venue/Venue.php` e `api/app/Models/Venue.php` (R6)
- [ ] T103 [P] [US3] Bloqueio de publicação para local não reivindicado em `api/app/Domain/Venue/VenuePublishingPolicy.php` (`RN-EVENTO-001`)
- [ ] T104 [US3] Caso de uso `ClaimVenue` em `api/app/UseCases/Venue/ClaimVenue.php`
- [ ] T105 [US3] Caso de uso `ApproveClaim` em `api/app/UseCases/Venue/ApproveClaim.php` — cria o vínculo em `venue_managers`, transfere sem recriar, encerra os demais pendentes e enfileira um aviso por solicitante
- [ ] T106 [US3] Caso de uso `RejectClaim` em `api/app/UseCases/Venue/RejectClaim.php` — motivo obrigatório, aviso ao solicitante, novo pedido permitido
- [ ] T107 [P] [US3] `RequestClaimRequest` e `RejectClaimRequest` em `api/app/Http/Requests/Venue/`
- [ ] T108 [P] [US3] `VenueClaimResource` expondo a evidência **ao lado do telefone do perfil**, em `api/app/Http/Resources/VenueClaimResource.php` (FR-026 — nunca um número informado pelo solicitante)
- [ ] T109 [US3] `VenueClaimController` com pedir, listar, aprovar e recusar em `api/app/Http/Controllers/Api/V1/VenueClaimController.php`
- [ ] T110 [P] [US3] `VenuePolicy` e `VenueClaimPolicy` em `api/app/Policies/`, registradas em `api/app/Providers/AppServiceProvider.php`
- [ ] T111 [US3] Job de aviso do resultado reusando a porta de e-mail da spec 001, em `api/app/Jobs/NotifyClaimDecision.php` (Princípio VI — assíncrono)
- [ ] T112 [P] [US3] Mensagem de resultado com motivo em linguagem simples e **caminho para falar com a plataforma**, em `api/resources/views/emails/` (FR-021 — o caminho até a resposta é parte da feature, lição do E-019)
- [ ] T113 [US3] Integrar a opção "sou eu que gerencio este bar" ao `CreateVenue` em `api/app/UseCases/Venue/CreateVenue.php` (FR-019)
- [ ] T114 [US3] Registrar as rotas de reivindicação em `api/routes/api.php`, com o grupo de operação protegido

### Implementação da User Story 3 — Front

- [ ] T115 [US3] Formulário de pedido com os quatro campos de evidência e nada além, em `web/src/components/venue/VenueClaimForm.tsx`
- [ ] T116 [US3] Página de pedir reivindicação (1 toque a partir do perfil) em `web/src/app/locais/[slug]/reivindicar/page.tsx`
- [ ] T117 [US3] Confirmação explícita de que o pedido foi registrado e será analisado, em `web/src/app/locais/[slug]/reivindicar/page.tsx` (FR-007)
- [ ] T118 [US3] Tela de aprovar reivindicações (receita Lista privada), com pedidos **agrupados por local e lado a lado**, em `web/src/app/admin/reivindicacoes/page.tsx` (FR-022)
- [ ] T119 [US3] Recusar como ação secundária **exigindo motivo** em `web/src/app/admin/reivindicacoes/page.tsx` (FR-021)
- [ ] T120 [US3] Exibir o selo "Perfil do estabelecimento" na página pública quando reivindicado, em `web/src/components/venue/VenueStateBadge.tsx` e `web/src/app/locais/[slug]/page.tsx`
- [ ] T121 [US3] Acrescentar a caixinha "sou eu que gerencio este bar" ao formulário de cadastro em `web/src/components/venue/VenueForm.tsx` (FR-019 — só agora, com o caminho de aprovação existindo)

### Fechamento da User Story 3

- [ ] T122 [US3] Rodar o portão sobre as duas telas novas e as três suítes, e confirmar verde — reiniciando o `queue:work` antes, porque o worker é daemon e carrega o provider antigo em memória (E-018)
- [ ] T123 [US3] **Validação visual do Ícaro**, seguindo a seção P3 do `quickstart.md`

**Checkpoint**: as três histórias funcionam independentes. O perfil pode virar presença
oficial.

---

## Phase 6: User Story 4 — Enriqueço o perfil do meu estabelecimento (P4)

**Goal**: com o perfil reivindicado, o gestor acrescenta foto, descrição e Instagram — o
prêmio da reivindicação.

**Independent Test**: com um perfil reivindicado, acrescentar foto, descrição e Instagram e
vê-los na página pública; com um perfil não reivindicado, verificar que os campos **não são
sequer oferecidos**.

### Testes da User Story 4

- [ ] T124 [P] [US4] Teste do bloqueio do **Princípio V**: só gestor vinculado edita; conta de outro local é recusada, em `api/tests/Feature/Venue/UpdateVenueAuthorizationTest.php` (FR-011)
- [ ] T125 [P] [US4] Teste de que campo rico em perfil **não** reivindicado é recusado pela invariante, em `api/tests/Feature/Venue/UpdateVenueTest.php` (FR-015, `RN-LOCAL-001`)
- [ ] T126 [P] [US4] Teste de upload recusado por tipo e por tamanho, com a recusa explicando o limite, em `api/tests/Feature/Venue/VenueUploadTest.php` (FR-016)
- [ ] T127 [P] [US4] Teste de `RN-LOCAL-003`: perfil reivindicado expõe foto, descrição, telefone clique-para-ligar, endereço e Instagram, em `api/tests/Feature/Venue/PublicVenueProfileTest.php`
- [ ] T128 [P] [US4] Teste de componente do formulário de edição e dos dois componentes novos, em `web/tests/unit/venue-edit-form.test.tsx`
- [ ] T129 [P] [US4] Teste e2e do percurso P4 em `web/tests/e2e/us4-venue-profile.spec.ts`, incluindo as imagens carregando **sem deslocar** o conteúdo (SC-006). Em **360, 1280 e 390–430** — o perfil público fica ainda mais denso com os campos ricos — com `axe` sem violação e passagem com **fonte do sistema ampliada**

### Implementação da User Story 4

- [ ] T130 [US4] Caso de uso `UpdateVenue` em `api/app/UseCases/Venue/UpdateVenue.php`, respeitando a invariante de campo rico e o `slug` imutável
- [ ] T131 [US4] `UpdateVenueRequest` com validação de MIME e tamanho e mensagens em linguagem humana, em `api/app/Http/Requests/Venue/UpdateVenueRequest.php`
- [ ] T132 [US4] Autorização de edição pela `VenuePolicy` em `api/app/Policies/VenuePolicy.php` (FR-011)
- [ ] T133 [US4] Método `update` em `api/app/Http/Controllers/Api/V1/VenueController.php` e rota `PATCH /api/v1/locais/{slug}` em `api/routes/api.php`
- [ ] T134 [P] [US4] Porta e adaptador de armazenamento de imagem em `api/app/Ports/ImageStorage.php` e `api/app/Adapters/Storage/` — o domínio não importa SDK
- [ ] T135 [P] [US4] Componente de área de texto em `web/src/components/ui/textarea.tsx`, com a régua embutida (D17 — chega agora, com a história que o usa)
- [ ] T136 [P] [US4] Componente de envio de foto em `web/src/components/ui/image-upload.tsx`, com a régua embutida e a recusa explicando o limite
- [ ] T137 [US4] Página de editar perfil (só para perfil reivindicado), dentro da moldura de gestão do T022 — que aqui nasce **só com o Perfil** — em `web/src/app/meus-locais/[slug]/editar/page.tsx`
- [ ] T138 [US4] Exibir foto, descrição e Instagram na página pública, com dimensão reservada para a imagem não deslocar o conteúdo, em `web/src/app/locais/[slug]/page.tsx` (SC-006)

### Fechamento da User Story 4

- [ ] T139 [US4] Rodar o portão sobre a tela de edição e as três suítes, e confirmar verde
- [ ] T140 [US4] **Validação visual do Ícaro**, seguindo a seção P4 do `quickstart.md`

**Checkpoint**: as quatro histórias entregues e validadas.

---

## Phase 7: Polish e questões transversais

- [ ] T141 [P] Teste do guarda FR-014: local com histórico **não é excluído**, é inativado, e o sistema **informa qual condição** impede, em `api/tests/Feature/Venue/DeactivateVenueTest.php` (Princípio X — guarda, não tela)
- [ ] T142 [P] Conferir a documentação da API gerada pelo `dedoc/scramble` para as dez rotas do contrato, sem inventar convenção nova (BORA-26 segue em aberto)
- [ ] T143 [P] Atualizar o catálogo de regras em `docs/domain/locais.md` com o que esta feature fixou (`slug` imutável, estado derivado, critério de duplicata)
- [ ] T144 [P] Escrever a ajuda das telas em `docs/product/user-guide/screens/` — cadastrar local, perfil público, lista, pedir reivindicação, aprovar reivindicações, editar perfil
- [ ] T145 Medir **SC-006** em rede 3G: nome, endereço, telefone e "Como chegar" visíveis e utilizáveis em até 3 segundos, imagens sem deslocar
- [ ] T146 Medir **SC-001**: pessoa que nunca usou o Bora cadastra um bar em menos de 2 minutos, pelo celular, sem ajuda
- [ ] T147 Medir **SC-005**: mostrar a página a 5 pessoas de fora, 30 segundos cada; ao menos 4 explicam a diferença entre perfil gerenciado e não gerenciado
- [ ] T148 [P] Fechar a documentação da feature com `/doc-sync`: `CHANGELOG.md`, `docs/logs/backlog.md`, `docs/logs/error-log.md` e o quadro do Linear (time `BORA`)
- [ ] T149 Rodar o `quickstart.md` inteiro de ponta a ponta, incluindo a fase Foundational

---

## Dependências e ordem de execução

### Dependências de fase

- **Setup (Phase 1)**: sem dependência
- **Foundational (Phase 2)**: depende do Setup e **bloqueia todas as histórias**
- **User Stories (Phase 3–6)**: todas dependem da Phase 2 completa
- **Polish (Phase 7)**: depende das histórias desejadas estarem completas

### Dependências internas da Phase 2

A ordem é a **D4**, e não é arbitrária:

```text
T005–T009 (portão)  →  T010–T015 (kit) ‖ T016–T023 (shell)  →  T024–T025 (retrofit)

T026–T037 (dados) — independente do front, corre em paralelo desde o começo
```

O portão vem antes do kit porque portão escrito depois do kit nasce moldado a ele.

### Dependências entre histórias

- **US1 (P1)**: pode começar assim que a Phase 2 terminar. Sem dependência de outra história
- **US2 (P2)**: depende da Phase 2; reusa o `VenueResource` e o cliente de API da US1, mas é
  testável sozinha
- **US3 (P3)**: depende da Phase 2 e do `Venue` existindo (US1) — reivindica-se um perfil que
  precisa existir
- **US4 (P4)**: depende da US3 — campo rico só existe em perfil reivindicado (FR-015)

> **Restrição do Princípio XI, acima de qualquer paralelismo:** a próxima história **não
> abre** antes da validação visual da atual (T074, T087, T123, T140). Na prática as histórias
> correm em série, mesmo onde a dependência técnica permitiria paralelo.

### Dentro de cada história

Testes escritos e falhando → domínio → casos de uso → borda (FormRequest, Controller,
Resource, rotas) → front → portão e suítes → validação visual.

### Oportunidades de paralelismo

- Todas as tarefas de Setup marcadas `[P]`
- Na Phase 2: o bloco de dados (T026–T037) roda em paralelo do bloco de front por inteiro; e
  dentro do front, kit (T010–T015) e shell (T016–T023) são independentes
- Em cada história, todos os testes marcados `[P]` de uma vez, antes da implementação
- Entidades de domínio marcadas `[P]` dentro da mesma história

---

## Exemplo de paralelismo: User Story 1

```bash
# Os onze testes da US1, de uma vez, antes de qualquer implementação:
api/tests/Feature/Venue/CreateVenueTest.php
api/tests/Feature/Venue/PublicVenueProfileTest.php
api/tests/Feature/Venue/PublicAccessTest.php
api/tests/Feature/Venue/SimilarVenuesTest.php
api/tests/Feature/Venue/CreateVenueDoubleSubmissionTest.php
api/tests/Feature/Venue/VenueAuditLogTest.php
api/tests/Feature/Venue/VenueCategoryTest.php
api/tests/Unit/Domain/Venue/VenueTest.php
api/tests/Unit/Domain/Venue/VenueSlugTest.php
api/tests/Unit/Domain/Venue/NameNormalizerTest.php
web/tests/unit/venue-form.test.tsx
```

```bash
# As três peças de domínio da US1, de uma vez:
api/app/Domain/Venue/Venue.php
api/app/Domain/Venue/VenueSlug.php
api/app/Domain/Venue/NameNormalizer.php
```

---

## Estratégia de implementação

### MVP primeiro (só a User Story 1)

1. Phase 1 — Setup
2. Phase 2 — Foundational (**crítica: bloqueia tudo**)
3. Phase 3 — User Story 1
4. **PARAR e VALIDAR**: percurso P1 do `quickstart.md`, no celular primeiro
5. O bar existe na internet com link compartilhável

### Entrega incremental

1. Setup + Foundational → fundação com dente comprovado
2. US1 → validar → **MVP**
3. US2 → validar → catálogo navegável
4. US3 → validar → presença oficial
5. US4 → validar → perfil rico

Cada história acrescenta valor sem quebrar a anterior — e o portão prova isso a cada
fechamento (T073, T086, T122, T139).

### Sobre trabalhar em paralelo

O `tasks-template.md` prevê histórias em paralelo entre pessoas. **Aqui não se aplica**: a
validação visual por história (Princípio XI) serializa a entrega por decisão, e a equipe é de
uma pessoa. O paralelismo real desta spec está **dentro** da fase Foundational, onde o bloco
de dados da API não toca em nada do front.

---

## Notas

- `[P]` = arquivos diferentes, sem dependência pendente
- Cada história é completável e validável sozinha
- Confirmar que o teste **falha** antes de implementar
- **Não existe tarefa de Agenda.** A moldura de gestão nasce nesta feature **só com o
  Perfil** (T022): agenda depende de eventos, que são de outra spec
- **Não existe tarefa de `git push`.** O `/doc-sync` commita; o push é sempre do Ícaro, à mão
- Commitar por tarefa ou grupo lógico; **toda alteração atualiza a documentação no mesmo
  commit**
- **Risco registrado, vindo da D6:** esta spec acumula fundação + quatro histórias. Escopo
  grande é onde a validação visual vira carimbo. A mitigação é estrutural — a fase
  Foundational bloqueia, e a validação é **por história**
- **Cuidado do E-012:** teste de componente não pega a janela entre o HTML chegar e o
  JavaScript assumir; `jsdom` não tem essa janela. Controle novo que dispara ação precisa de
  teste **e2e**, não só de componente
- **Cuidado do E-018:** reiniciar o `queue:work` depois de qualquer renomeação de classe ou
  mudança de binding — o aviso de reivindicação sai por fila
- **Divergência de contagem — RESOLVIDA pelo Ícaro em 2026-09-09.** O `plan.md` dizia "4
  migrations novas" e o `data-model.md` abria com "Quatro tabelas novas", enquanto o próprio
  `data-model.md` define **cinco** seções de tabela. **São cinco**, e as cinco migrations
  ficam: `venues`, `venue_categories`, `venue_venue_category`, `venue_managers` e
  `venue_claims`. O **T028 permanece**: o pivô é tabela de verdade, o `data-model.md` define
  chave única no par, e o precedente autoral do projeto é **um arquivo por tabela**
  (`create_users_table`, `create_social_accounts_table`, `create_email_tokens_table`) — o
  único multi-tabela do repositório é a migration publicada pelo `spatie/laravel-permission`,
  que é de terceiro. As três frases erradas foram corrigidas no mesmo commit desta nota:
  `data-model.md`, `plan.md` e a entrada "Plano técnico da spec 002" do `CHANGELOG.md`
