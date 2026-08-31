# Tasks: Fundação de Contas e Autenticação

**Input**: documentos de design em `specs/001-contas-autenticacao/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/auth-api.md](./contracts/auth-api.md),
[quickstart.md](./quickstart.md)

**Testes**: **obrigatórios, não opcionais.** O template do Spec Kit trata teste como
opcional; aqui a constituição manda o contrário — Princípio IX (toda `RN` referenciada e
todo princípio NON-NEGOTIABLE tocado têm teste) e Princípio XI (back **e** front aprovados
antes da entrega). Onde o template diria "if requested", neste projeto é sempre.

**Organização**: por user story, para cada uma ser implementável e testável em separado.

## Formato: `[ID] [P?] [Story] Descrição com caminho de arquivo`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência pendente)
- **[Story]**: US1..US4 conforme a spec. Setup, Foundational e Polish não levam rótulo.

## Convenções de caminho (do plan.md)

- Backend: `api/app/...`, `api/tests/...` — PHPUnit 12.5.34 (é o runner instalado, não Pest)
- Frontend: `web/src/...`, `web/tests/...` — Vitest + Testing Library + axe, Playwright e2e

---

## Phase 1: Setup (infraestrutura compartilhada)

**Objetivo**: instalar e configurar o que hoje **não existe**. Verificado em 2026-08-30:
nenhum destes pacotes está instalado e o `web/` não tem nenhuma ferramenta de teste.

- [x] T001 Instalar Socialite em `api/` com `composer require laravel/socialite -W` — o `-W` é **obrigatório**: o pacote exige guzzle ^6|^7 e o projeto está em 8.1.0; sem `-W` a instalação falha e a resolução cai numa faixa de `firebase/php-jwt` sob advisory de segurança. Ver [research.md](./research.md) §2
- [x] T002 Instalar os demais pacotes em `api/`: `composer require spatie/laravel-permission spatie/laravel-activitylog dedoc/scramble`
- [x] T003 [P] Publicar e configurar `api/config/cors.php` com `php artisan config:publish cors` — `allowed_origins` **explícito** (`http://localhost:3000`), `supports_credentials` em `false`, `paths` em `api/*`
- [x] T004 [P] Adicionar bloco `google` em `api/config/services.php` lendo `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e `GOOGLE_REDIRECT_URI` — as três já estão no `.env` e foram verificadas
- [x] T005 [P] Criar `api/config/bora.php` com os parâmetros configuráveis da spec: senha mínima (8), tentativas por minuto (5), validade dos tokens de e-mail (união 60 min, redefinição 60 min, verificação 7 dias), validade da sessão (30 dias). **Nenhum desses números pode ficar hardcoded** (Princípio VII)
- [x] T006 [P] Confirmar `'expiration' => null` em `api/config/sanctum.php` e comentar o porquê — valor ali **sobrepõe** o `expires_at` por token e quebra a expiração deslizante ([research.md](./research.md) §1)
- [x] T007 [P] Publicar as migrations dos pacotes com `php artisan vendor:publish` para `spatie/laravel-permission` e `spatie/laravel-activitylog`
- [x] T008 [P] Atualizar `api/.env.example` com as chaves novas (`GOOGLE_*`, `RESEND_API_KEY`, `FRONTEND_URL`), sem valores reais
- [x] T009 [P] Instalar ferramentas de teste no `web/`: `npm install -D vitest @vitejs/plugin-react jsdom @testing-library/react @testing-library/jest-dom @testing-library/user-event jest-axe @playwright/test`
- [x] T010 [P] Criar `web/vitest.config.mts` (ambiente jsdom, alias `@/*`) e `web/tests/setup.ts` (matchers de `jest-dom` e `jest-axe`). **Extensão `.mts`, não `.ts`**: o Vite 8 avisa que carregar config ESM como CommonJS deixará de funcionar
- [x] T011 [P] Criar `web/playwright.config.ts` com **dois projetos de viewport: 360×740 e 1280×800** — as larguras obrigatórias do `ux-requirements.md` — e `baseURL` `http://localhost:3000`
- [x] T012 [P] Inicializar shadcn/ui no `web/` sobre o Tailwind 4 já instalado, gerando `web/src/components/ui/` e `web/src/lib/utils.ts`
- [x] T013 [P] Adicionar scripts `test`, `test:e2e` e `lint` em `web/package.json`

**Checkpoint**: dependências instaladas; `php artisan test` e `npm run test` executam.

---

## Phase 2: Foundational (pré-requisitos bloqueantes)

**⚠️ Nenhuma user story começa antes desta fase inteira.**

### Banco e modelos

- [x] T014 Criar migration em `api/database/migrations/` alterando `users`: `password` para **nullable** e nova coluna `ultimo_acesso_em` (timestamp, nullable). O índice único de `email` já existe — não recriar. Ver [data-model.md](./data-model.md)
- [x] T015 [P] Criar migration da tabela `contas_sociais` (`user_id` FK, `provedor`, `provedor_user_id`, `email_no_provedor` nullable, `vinculado_em`), com **único em (`provedor`,`provedor_user_id`)** e **único em (`user_id`,`provedor`)**
- [x] T016 [P] Criar migration da tabela `tokens_de_email` (`user_id` FK, `finalidade`, `token_hash` único, `expira_em`, `usado_em` nullable, `dados` json nullable) e índice em (`user_id`,`finalidade`)
- [x] T017 Rodar `php artisan migrate` e **conferir que as tabelas nasceram InnoDB** (E-003 — o MySQL do WAMP usa MyISAM por padrão; `api/config/database.php` força InnoDB e isso não se desfaz)
- [x] T018 [P] Atualizar `api/app/Models/User.php`: adicionar `HasApiTokens` (Sanctum) e `HasRoles` (spatie), relações `contasSociais()` e `tokensDeEmail()`, cast de `ultimo_acesso_em`. **Manter a sintaxe do Laravel 13 já usada no arquivo** — atributos `#[Fillable([...])]` e `#[Hidden([...])]`, não propriedades
- [x] T019 [P] Criar `api/app/Models/ContaSocial.php` com `$fillable` explícito e relação `user()`
- [x] T020 [P] Criar `api/app/Models/TokenDeEmail.php` com `$fillable` explícito, relação `user()` e escopos `valido()` / `naoUsado()`
- [x] T021 Criar seeder idempotente em `api/database/seeders/` que garante o papel `rolezeiro` (spatie)

### Domínio (núcleo testável, sem framework — Princípio VII)

- [x] T022 [P] Criar `api/app/Domain/Account/Email.php` — value object que **normaliza** (minúsculas, `trim`) e valida. É o que sustenta a invariante de conta única contra variação de caixa
- [x] T023 [P] Criar `api/app/Domain/Account/PoliticaDeSenha.php` — comprimento mínimo vindo de `config`, nunca literal
- [x] T024 [P] Criar `api/app/Domain/Account/PoliticaDeSessao.php` — calcula o novo `expires_at` (agora + prazo configurado) da janela deslizante
- [x] T025 [P] Criar `api/app/Ports/ProvedorDeIdentidade.php` e `api/app/Ports/EnviadorDeEmail.php` — interfaces do domínio; **nenhum `use` de SDK externo** nesses arquivos

### Bordas da API

- [x] T026 Reescrever `api/routes/api.php`: tudo sob o grupo `v1` e **mover `/user` para `/api/v1/eu`** (hoje está em `/api/user`, fora do versionamento — Princípio IV). Manter o bloco andaime `v1/eventos` do spike por enquanto; ele sai na T115
- [x] T027 Criar `api/app/Http/Middleware/RenovarExpiracaoDoToken.php` — a cada request autenticada empurra o `expires_at` do token atual para agora + prazo configurado e atualiza `users.ultimo_acesso_em`. Registrar no grupo autenticado em `api/bootstrap/app.php`
- [x] T028 Configurar em `api/bootstrap/app.php` o rendering de exceções no envelope da constituição: 422 com `errors` por campo, 401/409/410 com `message`, 429 com `message` + header `Retry-After`. Nenhum 5xx pode vazar detalhe interno para a tela
- [x] T029 [P] Criar `api/app/Http/Resources/ContaResource.php` — resposta sempre por API Resource, nunca Model direto; datas ISO 8601; **nunca** expor `password` nem token
- [x] T030 [P] Criar `api/app/Http/Resources/SessaoResource.php` com `conta`, `token` e `expira_em`
- [x] T031 [P] Configurar rate limiters nomeados em `api/app/Providers/AppServiceProvider.php`, por e-mail + IP, com limite vindo do config. O mesmo limitador serve login **e** união — a spec exige o bloqueio nas duas portas
- [x] T032 [P] Configurar `api/config/activitylog.php` e criar helper de auditoria que registra **evento e autor, nunca valores sensíveis** — senha, hash e token são PROIBIDOS no log (Princípio V)
- [x] T033 [P] Criar adapter em `api/app/Adapters/Email/` implementando `EnviadorDeEmail` — Resend em produção, `log` em desenvolvimento (a chave `resend` já existe em `config/services.php`)
- [x] T034 [P] Criar `api/app/Jobs/EnviarEmailTransacional.php` — todo envio vai para a fila Redis, nunca no ciclo da request (Princípio VI); falha de envio **não** derruba a request que o originou
- [x] T035 [P] Configurar `api/config/scramble.php` para expor só o prefixo `api/v1`, e agendar `sanctum:prune-expired --hours=24` em `api/routes/console.php`

### Frontend base

- [x] T036 [P] Criar `web/src/lib/api.ts` — cliente da API pública: base URL de env, envelope (`data`/`message`/`errors`), anexa `Authorization: Bearer` quando há token e traduz 401/409/410/422/429 em tipos discriminados para a tela tratar
- [x] T037 [P] Criar `web/src/lib/sessao.ts` — guarda o token em `localStorage` (decisão D2). Arquivo **exclusivamente de cliente**: nunca importado por componente de servidor, e **nenhum token pode ir em prop que cruze a fronteira servidor→cliente** (o spike provou que essas props são serializadas no HTML)
- [x] T038 [P] Configurar **CSP estrita** em `web/next.config.ts`, sem `unsafe-inline` para script — mitigação obrigatória do risco de XSS aceito na D2 ([research.md](./research.md) §3)
- [x] T039 [P] Criar primitivos acessíveis em `web/src/components/ui/` (botão, campo com rótulo associado, mensagem de erro anunciada a leitor de tela, indicador de carregando): alvo de toque ≥ 44px, foco visível, contraste AA, ícone sempre com rótulo
- [x] T040 [P] Criar `web/src/components/auth/FormularioBase.tsx` — componente **cliente** com estados de carregando/erro/sucesso e bloqueio de dupla submissão (botão desabilita durante o envio)
- [x] T041 [P] Criar `web/src/components/auth/LayoutAuth.tsx` — uma coluna a 360px sem rolagem horizontal; a partir de 768px vira cartão centralizado com largura máxima legível. **Estilo base é o do celular**; media query só amplia

### Testes da fundação

- [x] T042 [P] `api/tests/Unit/Domain/EmailTest.php` — normalização de caixa e espaços; e-mail inválido rejeitado
- [x] T043 [P] `api/tests/Unit/Domain/PoliticaDeSenhaTest.php` e `PoliticaDeSessaoTest.php` — **senha no limite mínimo exato aceita, um caractere a menos recusada** (edge case da spec)
- [x] T044 `api/tests/Feature/Auth/ExpiracaoDeslizanteTest.php` — request autenticada empurra o `expires_at`; token expirado devolve 401. É a prova da D7, que **não** vem pronta do Sanctum

**Checkpoint**: fundação pronta — as user stories podem começar.

---

## Phase 3: User Story 1 — Criar conta e entrar (P1) 🎯 MVP

**Objetivo**: cadastro com nome/e-mail/senha, login, logout e verificação de e-mail que
não bloqueia. Entrega identidade persistente na plataforma.

**Teste independente**: com o sistema no ar e banco vazio, criar conta pelo `web/`, sair e
entrar de novo. Tentar criar segunda conta com o mesmo e-mail e ver a recusa.

### Testes de backend (escrever antes; devem falhar)

- [ ] T045 [P] [US1] `api/tests/Feature/Auth/CriarContaTest.php` — 201 com envelope correto; papel `rolezeiro` atribuído; `email_verified_at` nulo não bloqueia; Job de verificação enfileirado
- [ ] T046 [P] [US1] `api/tests/Feature/Auth/CriarContaDuplicadaTest.php` — **prova do Princípio I**: e-mail já existente devolve 422 orientando o login e **não cria segunda conta**; conferir `COUNT(*) == 1`. Cobrir também variação de caixa e espaços (`"  Maria@Gmail.com "`)
- [ ] T047 [P] [US1] `api/tests/Feature/Auth/ValidacaoCadastroTest.php` — e-mail malformado, senha curta e nome vazio devolvem 422 com `errors` **por campo**; nada é criado
- [ ] T048 [P] [US1] `api/tests/Feature/Auth/LoginTest.php` — 200 com token e `expira_em`; senha errada devolve 401 com **mensagem única** que não revela qual campo falhou; conta sem senha (Google) orienta a entrar pelo Google
- [ ] T049 [P] [US1] `api/tests/Feature/Auth/LimiteTentativasTest.php` — excedido o limite, 429 com `Retry-After` e mensagem dizendo quanto esperar
- [ ] T050 [P] [US1] `api/tests/Feature/Auth/SairTest.php` — 204; o token da request é revogado; os demais tokens da conta **continuam válidos**
- [ ] T051 [P] [US1] `api/tests/Feature/Auth/VerificacaoEmailTest.php` — link válido confirma; link expirado devolve 410; link já usado devolve 410; reenvio funciona e é limitado
- [ ] T052 [P] [US1] `api/tests/Feature/Auth/DuplaSubmissaoTest.php` — duas requests concorrentes de cadastro com o mesmo e-mail produzem **uma** conta (edge case da spec)
- [ ] T053 [P] [US1] `api/tests/Feature/Auth/AuditoriaTest.php` — criação de conta e verificação de e-mail geram registro de auditoria com autor e evento, e **nenhuma senha, hash ou token aparece no log** (Princípio V)
- [ ] T054 [P] [US1] `api/tests/Feature/Auth/GratuidadeTest.php` — **prova do Princípio II**: percorre cadastro, login e verificação e assegura que nenhum passo exige, menciona ou condiciona pagamento; a API da feature não expõe operação de cobrança

### Implementação de backend

- [ ] T055 [P] [US1] Criar `api/app/UseCases/Account/RegistrarConta.php` — normaliza o e-mail, recusa duplicata (nunca cria conta paralela), atribui `rolezeiro`, emite token de sessão e dispara o Job de verificação
- [ ] T056 [P] [US1] Criar `api/app/UseCases/Account/AutenticarPorSenha.php` — mensagem única de falha; atualiza `ultimo_acesso_em`
- [ ] T057 [P] [US1] Criar `api/app/UseCases/Account/VerificarEmail.php` — valida hash, expiração e uso único; marca `usado_em`
- [ ] T058 [P] [US1] Criar `api/app/Http/Requests/Auth/CriarContaRequest.php` e `LoginRequest.php` — com `authorize()` e `rules()`; mensagens em **português, linguagem humana** ("Digite um e-mail válido, como nome@exemplo.com")
- [ ] T059 [US1] Criar `api/app/Http/Controllers/Api/V1/Auth/ContaController.php` — `POST /api/v1/contas` (controller fino, sem regra)
- [ ] T060 [US1] Criar `api/app/Http/Controllers/Api/V1/Auth/SessaoController.php` — `POST /api/v1/sessoes` e `DELETE /api/v1/sessoes/atual`
- [ ] T061 [US1] Criar `api/app/Http/Controllers/Api/V1/EuController.php` — `GET /api/v1/eu`, autenticado
- [ ] T062 [US1] Criar `api/app/Http/Controllers/Api/V1/Auth/VerificacaoEmailController.php` — `POST /api/v1/email/verificar` e `POST /api/v1/email/verificar/reenviar`
- [ ] T063 [US1] Registrar as rotas da US1 em `api/routes/api.php` com os rate limiters da T031

### Testes de frontend (escrever antes; devem falhar)

- [ ] T064 [P] [US1] `web/tests/unit/criar-conta.test.tsx` — erro **no campo** para e-mail inválido e senha curta; botão desabilita durante o envio; **`axe` sem violações**
- [ ] T065 [P] [US1] `web/tests/unit/entrar.test.tsx` — credenciais erradas mostram mensagem única e oferecem "Esqueci minha senha"; 429 mostra o tempo de espera; **`axe` sem violações**
- [ ] T066 [P] [US1] `web/tests/e2e/us1-conta.spec.ts` — jornada completa (criar conta → sair → entrar) **em 360 e 1280**, com asserção explícita de **ausência de rolagem horizontal a 360px**

### Implementação de frontend

- [ ] T067 [P] [US1] Criar `web/src/app/criar-conta/page.tsx` e `web/src/components/auth/FormularioCriarConta.tsx` — ação principal "Criar conta" **abaixo dos campos, na metade inferior**; aviso discreto de e-mail não confirmado após sucesso
- [ ] T068 [P] [US1] Criar `web/src/app/entrar/page.tsx` e `web/src/components/auth/FormularioEntrar.tsx` — campos de e-mail/senha, links "Esqueci minha senha" e "Criar conta" com área de toque completa. O botão "Entrar com Google" fica como espaço reservado até a US2
- [ ] T069 [US1] Implementar estado autenticado no cabeçalho em `web/src/components/auth/` — nome da pessoa e ação **"Sair" com rótulo de texto**, nunca só ícone
- [ ] T070 [US1] Implementar tratamento de sessão expirada em `web/src/lib/api.ts` — 401 leva a `/entrar` **preservando o destino de origem** e retorna a ele após autenticar
- [ ] T071 [US1] Criar `web/src/app/verificar-email/page.tsx` — confirma pelo token da URL; link expirado oferece reenviar

**Checkpoint**: US1 funciona ponta a ponta e é demonstrável sozinha. **É o MVP.**

---

## Phase 4: User Story 2 — Entrar com Google (P2)

**Objetivo**: entrar com conta Google, criando a conta única na primeira vez e
autenticando na mesma conta nas seguintes. Inclui definir senha em conta nascida no Google.

**Teste independente**: com uma conta Google de teste, completar o fluxo e sair
autenticado; repetir e confirmar que autentica na **mesma** conta.

> **Pré-requisito externo**: credenciais OAuth já estão no `.env` e verificadas. Falta o
> Ícaro confirmar que o "Salvar" dos URIs foi aplicado no console e que o Gmail dele está
> em Público-alvo → Usuários de teste. Sem isso: `redirect_uri_mismatch` ou `access_denied`.

### Testes de backend (escrever antes; devem falhar)

- [ ] T072 [P] [US2] `api/tests/Feature/Auth/GoogleUrlTest.php` — `GET /api/v1/auth/google/url` devolve URL e `state`; o `state` é validado depois e expira
- [ ] T073 [P] [US2] `api/tests/Feature/Auth/GoogleSessaoTest.php` — e-mail inédito cria conta com `email_verified_at` preenchido e papel `rolezeiro`; repetir o fluxo **não** duplica (`COUNT(*) == 1` — prova do Princípio I)
- [ ] T074 [P] [US2] `api/tests/Feature/Auth/GoogleFalhaTest.php` — cancelamento, recusa, falha do provedor e provedor sem devolver e-mail: 401 com mensagem humana e **nenhuma conta criada, nenhum estado parcial**
- [ ] T075 [P] [US2] `api/tests/Feature/Auth/GoogleEmailAlteradoTest.php` — conta cujo e-mail no Google mudou desde o vínculo continua entrando na **mesma** conta, porque o vínculo é pelo `provedor_user_id` (edge case da spec)
- [ ] T076 [P] [US2] `api/tests/Feature/Auth/DefinirSenhaTest.php` — conta nascida no Google define senha **com sessão ativa** e passa a aceitar os dois métodos; sem sessão, 401; conta que já tem senha, 422
- [ ] T077 [P] [US2] `api/tests/Feature/Auth/CadastroComEmailDeContaGoogleTest.php` — tentar cadastrar e-mail/senha com e-mail que só entra pelo Google devolve 422 orientando o Google e **não cria conta paralela**

### Implementação de backend

- [ ] T078 [P] [US2] Criar `api/app/Adapters/Socialite/GoogleIdentidade.php` implementando `ProvedorDeIdentidade` em modo `stateless()` — **o `use` do Socialite fica só aqui**, nunca no domínio
- [ ] T079 [US2] Criar `api/app/UseCases/Account/AutenticarPorGoogle.php` — decide entre entrar, criar conta ou **exigir união** (409); gera e valida o `state`; não grava nada quando o desfecho é união
- [ ] T080 [P] [US2] Criar `api/app/UseCases/Account/DefinirSenha.php` — exige sessão ativa (D1, direção inversa)
- [ ] T081 [US2] Criar `api/app/Http/Controllers/Api/V1/Auth/GoogleController.php` — `GET /api/v1/auth/google/url` e `POST /api/v1/auth/google/sessoes`, devolvendo 409 com `uniao_token` quando for o caso
- [ ] T082 [US2] Criar `api/app/Http/Controllers/Api/V1/Auth/SenhaController.php` com `POST /api/v1/senha` (autenticado) e registrar as rotas da US2 em `api/routes/api.php`

### Testes e implementação de frontend

- [ ] T083 [P] [US2] `web/tests/unit/botao-google.test.tsx` — o botão tem rótulo de texto (não só ícone), estado de carregando e mensagem humana em falha; **`axe` sem violações**
- [ ] T084 [P] [US2] `web/tests/e2e/us2-google.spec.ts` — fluxo com o provedor **simulado** (sem depender do Google real na CI), em 360 e 1280
- [ ] T085 [US2] Implementar o botão "Entrar com Google" em `web/src/components/auth/BotaoGoogle.tsx` — **acima** do formulário de e-mail/senha, por ser o caminho de menor fricção
- [ ] T086 [US2] Criar `web/src/app/entrar/google/retorno/page.tsx` — é **a URL registrada no console do Google**; lê o `code`, faz POST para a API, mostra "Entrando…" e trata o 409 redirecionando para `/unir-contas`. **Nenhum token pode aparecer na URL**
- [ ] T087 [US2] Criar `web/src/app/definir-senha/page.tsx` — tela autenticada para conta nascida no Google

**Checkpoint**: US1 e US2 funcionam de forma independente.

---

## Phase 5: User Story 3 — Unir credenciais (P3)

**Objetivo**: unir Google e e-mail/senha na mesma conta mediante confirmação do titular —
senha da conta existente, com link por e-mail como plano B (D1).

**Teste independente**: criar conta por e-mail/senha, entrar com Google no mesmo e-mail,
confirmar a união e verificar que existe **uma** conta com os dois meios de entrada.

### Testes de backend (escrever antes; devem falhar)

- [ ] T088 [P] [US3] `api/tests/Feature/Auth/UniaoPorSenhaTest.php` — senha correta une, autentica e devolve sessão; depois, os dois métodos entram na mesma conta
- [ ] T089 [P] [US3] `api/tests/Feature/Auth/UniaoPorLinkTest.php` — plano B: envia link (Job enfileirado), e o link válido conclui a união igual à senha
- [ ] T090 [P] [US3] `api/tests/Feature/Auth/UniaoErrosTest.php` — senha errada 401 e **sujeita ao mesmo rate limit do login**; `uniao_token` expirado ou já usado devolve 410
- [ ] T091 [P] [US3] `api/tests/Feature/Auth/UniaoInvarianteTest.php` — **o teste mais importante da story**: em todos os desfechos (confirmada, cancelada, expirada, senha errada) o número de contas com aquele e-mail é **exatamente um** (US3-6, Princípio I)
- [ ] T092 [P] [US3] `api/tests/Feature/Auth/UniaoAuditoriaTest.php` — a união gera registro de auditoria do **evento**, sem valores sensíveis

### Implementação de backend

- [ ] T093 [US3] Criar `api/app/UseCases/Account/UnirCredenciais.php` — valida `uniao_token` (uso único, expiração), confirma por senha ou por link, cria o vínculo em `contas_sociais` e emite sessão. **Nada é gravado antes da confirmação**
- [ ] T094 [P] [US3] Criar `api/app/Http/Requests/Auth/UnirCredenciaisRequest.php` com `authorize()` e `rules()`
- [ ] T095 [US3] Criar `api/app/Http/Controllers/Api/V1/Auth/UniaoCredenciaisController.php` — `POST /api/v1/uniao-credenciais`, `POST /api/v1/uniao-credenciais/link` e `POST /api/v1/uniao-credenciais/link/confirmar`; registrar as rotas

### Testes e implementação de frontend

- [ ] T096 [P] [US3] `web/tests/unit/unir-contas.test.tsx` — a tela **explica em linguagem simples** o que será unido; senha errada mostra erro e oferece o plano B; **`axe` sem violações**
- [ ] T097 [P] [US3] `web/tests/e2e/us3-uniao.spec.ts` — união por senha, por link e cancelamento, em 360 e 1280
- [ ] T098 [US3] Criar `web/src/app/unir-contas/page.tsx` e `web/src/components/auth/FormularioUnirContas.tsx` — ação principal "Unir e entrar"; alternativa "Receber link por e-mail"; caminho de cancelar visível
- [ ] T099 [US3] Criar `web/src/app/unir-contas/confirmar/page.tsx` — conclui a união pelo token do e-mail; link expirado explica e oferece recomeçar

**Checkpoint**: US1, US2 e US3 funcionam de forma independente.

---

## Phase 6: User Story 4 — Recuperar senha (P4)

**Objetivo**: quem esqueceu a senha recupera o acesso sozinho, pelo e-mail, sem suporte.

**Teste independente**: disparar "Esqueci minha senha", usar o link, definir nova senha e
entrar com ela.

### Testes de backend (escrever antes; devem falhar)

- [ ] T100 [P] [US4] `api/tests/Feature/Auth/EsqueciSenhaNeutraTest.php` — **prova da não-enumeração**: e-mail existente e inexistente produzem resposta **byte a byte idêntica**; conta só-Google também
- [ ] T101 [P] [US4] `api/tests/Feature/Auth/RedefinirSenhaTest.php` — senha nova funciona, a antiga não; **as sessões dos outros aparelhos são revogadas** e a atual permanece (FR-015)
- [ ] T102 [P] [US4] `api/tests/Feature/Auth/RedefinirSenhaErrosTest.php` — link expirado e link já usado devolvem 410; senha inválida devolve 422; rate limit na solicitação

### Implementação de backend

- [ ] T103 [P] [US4] Criar `api/app/UseCases/Account/SolicitarRedefinicaoDeSenha.php` — resposta neutra sempre; conta só-Google recebe e-mail explicando, **sem mudar a resposta da API**
- [ ] T104 [P] [US4] Criar `api/app/UseCases/Account/RedefinirSenha.php` — uso único, revoga as outras sessões
- [ ] T105 [US4] Adicionar `POST /api/v1/senha/esqueci` e `POST /api/v1/senha/redefinir` em `api/app/Http/Controllers/Api/V1/Auth/SenhaController.php` e registrar as rotas

### Testes e implementação de frontend

- [ ] T106 [P] [US4] `web/tests/unit/esqueci-senha.test.tsx` — pós-envio **ensina o próximo passo** ("Confira sua caixa de entrada e o spam; o link vale por 1 hora"); **`axe` sem violações**
- [ ] T107 [P] [US4] `web/tests/e2e/us4-recuperacao.spec.ts` — jornada completa em 360 e 1280, incluindo reuso do link
- [ ] T108 [US4] Criar `web/src/app/esqueci-senha/page.tsx` — ação principal "Enviar link"
- [ ] T109 [US4] Criar `web/src/app/redefinir-senha/page.tsx` — nova senha pelo token do e-mail; link expirado explica e oferece pedir outro

**Checkpoint**: as quatro user stories funcionam de forma independente.

---

## Phase 7: Polish e questões transversais

- [ ] T110 [P] Rodar `php artisan test` inteiro e garantir **verde**, com o mapa regra→teste da spec coberto
- [ ] T111 [P] Rodar `npm run test` e `npx playwright test` — **`axe` sem nenhuma violação** nas cinco telas, nas duas larguras
- [ ] T112 Gerar a doc com `php artisan scramble:export` e **conferir contra [contracts/auth-api.md](./contracts/auth-api.md)** — divergência é defeito, não questão de gosto
- [ ] T113 [P] Revisar que **nenhum token cruza a fronteira servidor→cliente** no `web/`: inspecionar o HTML/payload RSC das cinco telas e confirmar que o token não aparece (lição verificada no spike BORA-32)
- [ ] T114 [P] Revisar que senha, hash e token **não aparecem** em `api/storage/logs/` nem em `activity_log` após percorrer todos os fluxos
- [ ] T115 Remover o andaime do spike: `api/app/Http/Controllers/Spike/`, o bloco `v1/eventos` em `api/routes/api.php` e `web/src/app/eventos/`
- [ ] T116 [P] Rodar o `quickstart.md` inteiro (V1 a V5) manualmente e corrigir o que divergir
- [ ] T117 [P] Atualizar `docs/architecture/data-model.md` e `docs/architecture/api-conventions.md` trocando "projetado, ainda não implementado" pelo estado real
- [ ] T118 **Validação visual do Ícaro — primeiro no celular** (Princípio XI). Tela reprovada no celular **não** se apresenta em desktop. Sem esta aprovação a feature não está pronta e a próxima não começa
- [ ] T119 Rodar `/doc-sync` — CHANGELOG, backlog, error-log e catálogo; commit sem push

---

## Dependências e ordem de execução

### Entre fases

- **Phase 1 (Setup)**: sem dependências; começa imediatamente
- **Phase 2 (Foundational)**: depende da Phase 1 — **bloqueia todas as user stories**
- **Phases 3–6 (User Stories)**: dependem da Phase 2; depois disso, em paralelo (se houver gente) ou em ordem de prioridade
- **Phase 7 (Polish)**: depende das stories desejadas estarem prontas

### Entre user stories

- **US1 (P1)**: só depende da fundação. **É o MVP.**
- **US2 (P2)**: só depende da fundação. É testável sozinha, mas na tela reaproveita a `/entrar` criada na US1
- **US3 (P3)**: exige **US1 e US2 implementadas** — a união é o cruzamento das duas. É a única story com dependência real entre stories
- **US4 (P4)**: só depende da US1 (precisa de conta com senha)

### Dentro de cada story

Testes escritos primeiro e **falhando** → domínio/casos de uso → FormRequests → controllers
→ rotas → tela → testes de tela.

### Oportunidades de paralelismo

- Toda a Phase 1 exceto T001 e T002 (que mexem no mesmo `composer.json`/`composer.lock`)
- Na Phase 2: migrations (T015, T016), modelos (T018–T020), domínio (T022–T025), recursos e configs (T029–T035) e a base do front (T036–T041) são arquivos distintos
- Em cada story, **todos os testes marcados [P]** podem ser escritos juntos
- US1, US2 e US4 podem correr em paralelo depois da fundação; US3 espera US1 e US2

---

## Estratégia de implementação

### MVP primeiro (só a US1)

1. Phase 1 (Setup) → 2. Phase 2 (Foundational) → 3. Phase 3 (US1)
4. **PARE e VALIDE**: US1 sozinha, testes verdes dos dois lados
5. Mostrar ao Ícaro no celular

### Entrega incremental

Fundação → US1 (MVP) → US2 → US3 → US4, validando cada uma antes da seguinte.

### Armadilhas já conhecidas (não redescobrir)

- `composer require laravel/socialite` **sem `-W` falha** — ver T001
- `'expiration'` em `config/sanctum.php` **fica `null`** — valor ali quebra a T027
- Props que cruzam para componente cliente **são serializadas no HTML** — token nunca passa por elas
- `php`/`composer` "não reconhecido" em sessão antiga: PATH herdado (E-007), não é o E-004 voltando
- Scripts `.ps1` bloqueados pela ExecutionPolicy: usar `npm.cmd` nas tasks e `-ExecutionPolicy Bypass` nos scripts do Spec Kit (E-006)

---

## Notas

- `[P]` = arquivos diferentes, sem dependência pendente
- O rótulo `[Story]` dá rastreabilidade da tarefa até a user story
- Commit por tarefa ou por grupo lógico
- **Teste aqui não é opcional** — é o Princípio IX; verifique que falha antes de implementar
- Parar em qualquer checkpoint para validar a story isolada
