# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere a [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
- **Caminho visível para "Definir senha"** (spec 001, FR-012/US2-5). Faixa abaixo do
  cabeçalho, exibida **apenas** enquanto a conta não tem senha (`signs_in_with` sem
  `password`), levando a `/definir-senha` em um toque; some dentro da própria tela e some
  sozinha assim que a senha é definida. Fica em faixa, e não no cabeçalho, porque a 360px a
  barra já ocupa a largura toda com o nome e "Sair". Decidida a partir do estado que o
  `AccountHeader` já mantém, sem uma segunda consulta a `/eu`. Cobertura nova: 6 testes de
  componente com `axe` e 3 e2e em 360 e 1280, incluindo o caso negativo e o silêncio quando
  a API não informa `signs_in_with`.

### Fixed
- **E-019 — a tela "Definir senha" estava implementada e inalcançável.** Caso de uso, rota,
  tela e teste de backend existiam desde a US2; **nada no produto levava até lá**, então
  quem nascia do Google não tinha caminho visível para ganhar uma senha — tentava entrar
  por e-mail e senha, era corretamente mandado de volta ao Google, e ficava sem saída. A
  seção "Telas entregues" da spec listava cinco telas e não listava esta, então o
  `spec-check` não tinha caminho a cobrar. A spec passou a listá-la **com o ponto de entrada
  declarado**. Encontrado na validação visual (T118), por pergunta do Ícaro — nenhuma das
  três suítes podia pegar, porque todas perguntam se a tela funciona, não se existe porta
  para ela.
- **E-018 — worker de fila obsoleto engolia todos os e-mails** depois da refatoração de
  nomenclatura. O `queue:work` é daemon: tinha o `AppServiceProvider` antigo em memória
  (binding com o nome velho da porta) e carregava o Job novo do disco, que pede
  `App\Ports\EmailSender`. Resultado: verificação de cadastro, redefinição de senha e link
  de união falhavam **em silêncio** — a API respondia 200, porque falha de envio não derruba
  a operação (D5). Worker reiniciado e os três fluxos verificados ao vivo. O `quickstart.md`
  passou a mandar reiniciar processos de longa duração depois de renomear classe ou mexer em
  binding. Nenhuma suíte pegou porque em teste a fila é `sync` — 185 testes verdes não dizem
  nada sobre processos que já estavam no ar.

### Changed
- **`FRONTEND_URL` passou a existir no `api/.env.example`.** Ela não estava lá: um clone
  novo caía no default embutido no código sem saber que a variável existia. São duas
  variáveis parecidas e fáceis de trocar uma pela outra — `FRONTEND_URLS` (plural) é a lista
  de origens do CORS; `FRONTEND_URL` (singular) é a base dos links enviados por e-mail. O
  comentário agora diz a diferença e lembra de apontar para o IP da máquina ao validar no
  celular, senão o link chega com `localhost`, que no celular é o próprio celular (E-011).
- **Convenção de nomenclatura adotada e aplicada retroativamente** (2026-08-31, decisão do
  Ícaro): **identificador em inglês, prosa em português, e a única exceção é o caminho da
  URL**. Escrita em `docs/architecture/naming-conventions.md`, referenciada em `CLAUDE.md`
  e em `development-workflow.md` §5.1, para as próximas specs já nascerem no padrão.
  Feita agora porque nada foi para produção — é o momento mais barato que vai existir.
  - **Renomeado no `api/`**: 38 classes e arquivos (`ContaController` → `AccountController`,
    `TokenDeEmail` → `EmailToken`, `PoliticaDeSessao` → `SessionPolicy`, `Auditoria` →
    `AuditLog`, `UnirCredenciais` → `MergeCredentials`…), métodos, variáveis, constantes,
    ações de controller (`esqueci` → `forgot`, `reenviar` → `resend`), chaves de
    `config/bora.php` e variáveis de ambiente `BORA_*`.
  - **Renomeado no banco**, editando as migrations existentes em vez de criar migrations de
    rename — nada em produção, e um `create` em português seguido de um `rename` seria ruído
    permanente: `contas_sociais` → `social_accounts`, `tokens_de_email` → `email_tokens`, e
    as colunas (`provedor` → `provider`, `expira_em` → `expires_at`, `usado_em` → `used_at`,
    `vinculado_em` → `linked_at`, `ultimo_acesso_em` → `last_seen_at`, `dados` → `payload`).
  - **Renomeado no `web/`**: componentes (`FormularioBase` → `BaseForm`, `CabecalhoConta` →
    `AccountHeader`, `Aviso` → `Alert`, `Campo` → `Field`…), `lib/sessao.ts` →
    `lib/session.ts`, `lib/hidratacao.ts` → `lib/hydration.ts`, e as chaves de storage
    (`bora.sessao.token` → `bora.session.token`).
  - **Renomeado nos testes**: 22 arquivos e **155 métodos** de teste do backend, mais os
    testes de componente e e2e. As *descrições* de teste em string (Playwright, Vitest)
    ficaram em português — string é prosa, não identificador; é a regra aplicada
    mecanicamente, não uma exceção improvisada.
- **Campos do JSON da API passaram para inglês** — decisão revista no meio do trabalho, a
  pedido do Ícaro. A primeira decisão foi manter o corpo em português junto com a rota; ele
  questionou o custo e estava certo: com o banco em inglês e o corpo em português, toda
  coluna nova custaria uma linha de tradução no Resource e no FormRequest **para sempre**, e
  a chave do erro de validação poderia dessincronizar do nome do campo em silêncio — o que
  faz a tela perder o destaque no campo certo. O caminho da rota continua em português
  (`/sessoes/atual`, `/email/verificar/reenviar`), porque endereço é coisa que a pessoa vê
  e compartilha; o corpo, não.
  - `{"nome","senha","dispositivo"}` → `{"name","password","device"}`;
    `{"conta","expira_em","entra_com","papeis","criada_em","email_verificado"}` →
    `{"account","expires_at","signs_in_with","roles","created_at","email_verified"}`;
    `uniao_token` → `merge_token`; `situacao: "uniao_necessaria"` → `status: "merge_required"`.
  - `contracts/auth-api.md` e a spec 001 atualizados junto. **Verificado**: o OpenAPI que o
    Scramble gera tem as mesmas 14 rotas, com caminho em português e **zero campo em
    português**.
- **Vocabulário do produto não foi traduzido**: `rolezeiro` continua `rolezeiro`, inclusive
  como valor em banco. Padronizar isso não seria padronizar; seria apagar a voz do produto.
- **Uma asserção de teste ficou mais precisa, não mais frouxa.** `the_response_never_exposes_the_password`
  barrava a palavra `password` na resposta inteira. Com a API em inglês, `signs_in_with`
  passou a carregar o valor legítimo `"password"` — que diz por qual caminho a conta entra e
  não é segredo. A asserção passou a barrar a **chave** `"password":`, o valor em claro e
  qualquer hash `$2y$`. Afrouxar seria perder a rede; detector que grita sem motivo ensina a
  ser ignorado.
- Suítes após a mudança: **185 backend** (573 asserções), **37 de componente**, **76 e2e**,
  build e TypeScript limpos.

### Added
- **Spec 001 — fase de Polish (T110–T117)** concluída, 117/119. Falta só a validação visual
  final e o fechamento.
  - **Documentação da API conferida contra o contrato**: as 14 rotas geradas pelo Scramble
    batem **exatamente** com `contracts/auth-api.md`, verificado por comparação
    automatizada. Fecha o item "API documentada" do Princípio XI.
  - **Andaime do spike removido** (BORA-32): `api/app/Http/Controllers/Spike/`, o bloco
    `v1/eventos` e `web/src/app/eventos/`. Zero referências sobrando; a doc caiu de 15 para
    14 rotas, confirmando.
  - **Verificação de vazamento**: nenhum token cruza para componente de servidor
    (`lib/session` só é importado por componentes cliente), o HTML servido não contém token
    de sessão, e nos logs há **zero** hashes bcrypt, tokens Bearer ou campos de senha. A
    auditoria não guarda chave sensível.
  - **Quickstart contra a API real**: conta criada, token autentica, duplicata recusada
    **mesmo com o e-mail em caixa diferente**, e as respostas de "esqueci minha senha"
    idênticas para e-mail existente e inexistente.
  - `docs/architecture/{data-model,api-conventions}.md` atualizados para o estado real.
- **O token de união saiu da URL** (decisão do Ícaro no Polish). Ele viajava em
  `/unir-contas?token=...` e caía no histórico do navegador, no log de servidor e no
  `Referer`. Passou a viajar por `sessionStorage`, entre a página de retorno do Google e a
  tela de união. Risco anterior era baixo — token de 15 minutos, uso único, que não
  autentica nada —, mas contrariava a regra "token nunca em URL" que a própria spec fixou;
  agora a regra vale sem exceção. Continua na URL apenas o link de e-mail do plano B, onde
  é inevitável.
- **Spec 001 — US3 (unir credenciais) e US4 (recuperar senha) entregues e validadas**
  (T088–T109, 109/119, 2026-08-31). Com elas, **as quatro user stories** estão prontas e
  validadas visualmente pelo Ícaro. Testes: **185 no backend** (572 asserções), **37 de
  componente** com `axe` e **66 e2e** em 360 e 1280.
  - **US3 — API**: `POST /api/v1/uniao-credenciais` (confirma com a senha),
    `.../link` (plano B) e `.../link/confirmar`. **Telas**: `/unir-contas` e
    `/unir-contas/confirmar`.
  - **`MergeInvariantTest` é a prova do bloqueio do Princípio I** no único ponto do
    produto em que duas identidades se encontram: verifica **uma conta só** em todos os
    desfechos — confirmada, abandonada, senha errada, token expirado, três tentativas, e
    senha depois de pedir o link —, mais o índice único do banco.
  - **O plano B fica visível desde o começo**, não escondido atrás de um erro: quem já sabe
    que não lembra a senha não deveria precisar errar primeiro para achar a saída. E a tela
    **explica antes de pedir** — ser interrompido por um pedido de senha logo após um login
    com Google assusta, se não for justificado.
  - **US4 — API**: `POST /api/v1/senha/esqueci` (resposta **neutra**, sempre igual) e
    `.../redefinir`. **Telas**: `/esqueci-senha` e `/redefinir-senha`.
  - **Prova da não-enumeração**: o teste compara as respostas **byte a byte**, exista ou não
    a conta. Conta que só entra pelo Google tem resposta idêntica, mas recebe e-mail
    explicando que ali não há senha a redefinir — senão a pessoa esperaria um link que
    nunca vem.
  - **Redefinir revoga TODAS as sessões e não faz login automático**: é o caminho de quem
    pode ter tido a conta comprometida, e quem abriu o link provou que lê o e-mail, não que
    é a pessoa naquele aparelho.
- **Spec 001 — US2 (entrar com Google) entregue e validada** (T072–T087, 87/119,
  2026-08-31). Segunda feature a cumprir a Definition of Done do Princípio XI por inteiro,
  com **validação visual do Ícaro**. Testes: **130 no backend** (379 asserções), **24 de
  componente** com `axe` e **32 e2e** em 360 e 1280.
  - **API**: `GET /api/v1/auth/google/url` (URL de autorização + `state`),
    `POST /api/v1/auth/google/sessoes` (troca o `code` por sessão; **409** quando o e-mail
    já tem conta; **401** em qualquer falha do provedor) e `POST /api/v1/senha` (primeira
    senha de conta nascida no Google — decisão D1, direção inversa).
  - **Telas**: botão "Entrar com Google" **acima** do formulário de e-mail/senha,
    `/entrar/google/retorno` (a URL registrada no console) e `/definir-senha`.
  - **O token nunca passa pela URL**: o Google redireciona para uma página do `web/`, que
    troca o `code` por sessão num POST. Há teste e2e que falha se o token aparecer na URL.
  - **`state` de uso único, consumido antes de falar com o provedor** — se sobrevivesse a
    uma tentativa malsucedida, deixaria um valor válido circulando, que é justamente o que
    ele existe para impedir.
  - **O vínculo casa pelo `provider_user_id`, não pelo e-mail**: quem troca o endereço no
    Google continua entrando na mesma conta, com teste dedicado.
  - **Guarda contra o efeito duplo do StrictMode** na página de retorno: sem ele, o `code`
    seria trocado duas vezes, e como o Google só aceita uma, a segunda tentativa
    sobrescreveria um login bem-sucedido com mensagem de erro.
  - **`email_verified_at` fica fora do `#[Fillable]`** de propósito: adicioná-lo permitiria
    que um payload de cadastro marcasse a própria conta como verificada. A marcação é feita
    deliberadamente no caso de uso, com comentário explicando por que não pode voltar.
  - **O botão do Google ganhou o mesmo guarda de hidratação do formulário** (E-012), com
    sintoma diferente: antes de hidratar, o toque era silenciosamente ignorado — a pessoa
    apertava e nada acontecia, o que o `ux-requirements.md` proíbe.
- **Spec 001 — US1 (MVP) entregue e validada** (T045–T071, 71/119, 2026-08-31). É a
  primeira feature do Bora a cumprir a **Definition of Done do Princípio XI** por inteiro:
  API documentada + telas no `web/` + testes dos dois lados aprovados + **validação visual
  do Ícaro no celular**.
  - **API**: `POST /api/v1/contas` (cadastro), `POST /api/v1/sessoes` (entrar),
    `DELETE /api/v1/sessoes/atual` (sair), `GET /api/v1/eu`,
    `POST /api/v1/email/verificar` e `.../reenviar`.
  - **Telas**: Entrar, Criar conta e Confirmar e-mail, mais o cabeçalho com estado
    autenticado e "Sair" com rótulo de texto.
  - **Testes**: 99 no backend (284 asserções), 18 de componente com `axe` e 14 e2e em
    **360 e 1280**. Inclui os que **provam bloqueios**: conta paralela recusada nos dois
    caminhos e por variação de escrita do e-mail (Princípio I), jornada inteira sem
    nenhuma menção a pagamento (Princípio II), senha e token fora do log (Princípio V).
  - **Decisões de implementação**: comparação de hash mesmo quando o e-mail não existe, para
    o tempo de resposta não entregar quais e-mails têm conta; `LoginRequest` sem mínimo de
    senha, porque na entrada a senha é comparada e não avaliada; verificação de e-mail
    **pública** (a pessoa abre o link noutro aparelho), com só o reenvio exigindo sessão.
  - Corrigido o `title` do scaffold, que ainda dizia "Create Next App".
- **Testes de feature passam a rodar em MySQL** (decisão do Ícaro, 2026-08-30), na base
  `bora_test`, em vez de SQLite em memória. A invariante central da feature é um **índice
  único**, e testar num banco enquanto se roda em outro esconderia justamente o tipo de
  falha que esses testes existem para pegar. Custo aceito: a suíte foi de ~1s para ~10s.
- **Spec 001 — fundação implementada** (T001–T044 de 119, 2026-08-30). Setup e Foundational
  concluídos, com `php artisan test` (36 testes) e `npm run test` (7 testes) verdes e
  `npm run build` passando type-check. As quatro user stories ainda não começaram.
  - **`api/`**: instalados Socialite v5.30.1 (com `-W` — guzzle rebaixado de 8.1.0 para
    7.15.5, como o plano previu), `spatie/laravel-permission` 8.3.0,
    `spatie/laravel-activitylog` 5.1.0 e `dedoc/scramble` v0.13.42.
  - **Banco**: `users` ganha `password` **nullable** (conta que nasce no Google não tem
    senha) e `last_seen_at`; novas `social_accounts` (vínculo pelo `provider_user_id`,
    **não** pelo e-mail, com dois índices únicos) e `email_tokens` (uso único, só o
    **hash** — o valor em claro nunca é persistido). 18 tabelas, todas InnoDB.
  - **Domínio sem framework** (Princípio VII): `Email` (normalização que sustenta a
    invariante de conta única), `PasswordPolicy`, `SessionPolicy`, `OpenSession` e as
    portas `IdentityProvider` / `EmailSender`. As políticas recebem o parâmetro
    por **construtor**, não por `config()` — assim o núcleo se testa sem subir o Laravel.
  - **Janela deslizante da sessão** (D7) implementada em `RefreshTokenExpiration`, com
    teste que também **trava `sanctum.expiration` em `null`**: valor ali sobrepõe o
    `expires_at` por token e quebraria o deslizamento em silêncio.
  - **API**: tudo sob `/api/v1`; `/api/user` **deixou de existir** (virou `/api/v1/eu`).
    Envelope de erro da constituição em `bootstrap/app.php` (422 com `errors`, 429 com
    `Retry-After`, 5xx sem vazar detalhe). CORS com origem explícita e
    `supports_credentials` em `false`; `Retry-After` **exposto** — sem isso a tela não
    conseguiria dizer quanto esperar após um 429.
  - **Auditoria**: classe `AuditLog` com lista de chaves barradas, para que um descuido
    futuro num caso de uso não consiga gravar senha, hash ou token no log (Princípio V).
  - **`web/`**: `lib/api.ts` (resultado discriminado por status, sem regra de negócio),
    `lib/session.ts` (token em `localStorage`, módulo só de cliente), **CSP estrita** em
    `next.config.ts` como mitigação obrigatória da D2, primitivas acessíveis (`Campo`,
    `Aviso`) e `AuthLayout` mobile-first literal.
  - **Removido o "Test User" do `DatabaseSeeder`**: numa feature cuja invariante é "uma
    conta por e-mail", seeder que cria conta silenciosamente atrapalha o teste manual.
    Conta de teste passa a nascer por factory, dentro do teste que precisa dela.
  - **Limitação registrada em teste**: `Email` recusa acento no endereço e domínio
    internacionalizado (limite do `filter_var`). Não atinge o público real do Bora; fica
    documentado, com o lugar exato de corrigir se aparecer usuário reprovado por isso.
- **Tarefas da spec 001 geradas** (`/speckit-tasks`, 2026-08-30):
  `specs/001-contas-autenticacao/tasks.md` — **119 tarefas** (T001–T119), organizadas por
  user story: Setup (13), Foundational (31), US1 cadastro/login (27, o **MVP**), US2 Google
  (16), US3 união (12), US4 recuperação de senha (10) e Polish (10). 85 paralelizáveis.
  **Desvio deliberado do template do Spec Kit**: ele trata teste como opcional; aqui os
  Princípios IX e XI tornam obrigatório, então toda story tem tarefas de teste de back e
  front — inclusive as que **provam bloqueio** (T046 conta duplicada, T054 gratuidade,
  T091 invariante da união, T100 resposta neutra). Registrada também a única dependência
  real entre stories: a **US3 exige US1 e US2**, porque unir credenciais é o cruzamento das
  duas — US1, US2 e US4 podem correr em paralelo.
- **Credenciais OAuth do Google criadas** (Ícaro, 2026-08-30): projeto `bora-507117`,
  cliente Aplicativo da Web, app Externo, escopos no mínimo (`openid`, `email`, `profile`).
  `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e `GOOGLE_REDIRECT_URI` em `api/.env`
  (ignorado pelo git) e verificados — o Laravel lê os três. Redirecionamento aponta para o
  `web/` (`http://localhost:3000/entrar/google/retorno`), **não** para a API, mantendo o
  token fora da URL. Decisão de segurança: este cliente é **de desenvolvimento e não vai a
  produção** — outro será criado quando a hospedagem for definida (BORA-27).
- **Plano técnico da spec 001 gerado** (`/speckit-plan`, 2026-08-30):
  `specs/001-contas-autenticacao/{plan,research,data-model,quickstart}.md` e
  `contracts/auth-api.md`. A spec passou a **Aprovada** (Ícaro, 2026-08-30). O plano foi
  escrito sobre o **estado verificado** da instalação (Boost + `composer --dry-run`), não
  sobre suposição, e isso mudou três coisas:
  - **`laravel/socialite` não instala neste projeto sem `-W`.** Todas as versões até a
    v5.30.1 exigem `guzzle ^6|^7` e o projeto está em **guzzle 8.1.0** (transitivo do
    Laravel 13). Decisão: aceitar o downgrade para guzzle 7.15.5 — verificado que **nada
    exige guzzle 8** (framework aceita `^7.8.2||^8.0`, boost `^7.9|^8.0`, flysystem só
    conflita com `<7.0`). A constituição obriga Socialite no Stack; a alternativa exigiria
    emenda. Reversível. Bônus: sem `-W` a resolução cai numa faixa do `firebase/php-jwt`
    sob advisory de segurança; com `-W` trava a v7.1.0, limpa.
  - **O Sanctum não tem expiração deslizante.** A `'expiration'` é prazo absoluto desde a
    criação, e a D7 pediu 30 dias **de inatividade**. O plano implementa o deslizamento com
    middleware próprio sobre `expires_at`, mantendo `'expiration' => null`.
  - **Ressalva sobre a D2 registrada.** A doc oficial do Sanctum instalado desaconselha
    token de API para SPA de primeira parte. Ícaro **reconfirmou** a D2 com o risco à vista
    e definiu a guarda do token (`localStorage`); as razões do Bora (Princípio IV e
    ADR-0003) e as mitigações obrigatórias ficaram escritas em `research.md` §3 e no
    Complexity Tracking do `plan.md`.
  Também levantado: três pacotes exigidos pela constituição **não estão instalados**
  (`socialite`, `spatie/laravel-permission`, `spatie/laravel-activitylog`), o `web/` **não
  tem nenhuma ferramenta de teste**, e a rota `/api/user` está **fora do versionamento**
  `/api/v1` — o plano corrige as três coisas.
- **Spec 001 — Fundação de Contas e Autenticação** escrita e **aprovada no portão
  `spec-check`** (2026-08-29): `specs/001-contas-autenticacao/spec.md`. Cobre RN-PLAT-001
  (conta única multi-papel) e RN-PLAT-002 (login Google/e-mail, união de credenciais),
  com as telas Entrar, Criar conta, Unir contas, Esqueci minha senha e Redefinir senha,
  seção "Tela e Experiência" completa (360px, polegar, estados, acessibilidade, testes em
  360 e 1280 com axe) e mapa de testes por regra e princípio — incluindo os que provam os
  bloqueios dos Princípios I (nenhuma conta paralela) e II (nada atrás de pagamento).
  As **oito decisões que a bloqueavam** foram tomadas pelo Ícaro na sessão e registradas
  na spec (D1–D8): união confirmada por senha com link como plano B; token Bearer + CORS
  de origens explícitas; OpenAPI via Scramble; shadcn/ui + Vitest/Testing Library/axe +
  Playwright; verificação de e-mail sem bloquear; recuperação de senha no escopo; sessão
  de 30 dias renovada no uso; Resend como e-mail transacional (emenda constitucional
  pendente para formalizar — ver backlog). Catálogo `plataforma.md` (RN-PLAT-002 sem
  PENDENTE), ADR-0002 e ADR-0003 (action items fechados) e backlog sincronizados no mesmo
  commit.

### Fixed
- **A barra continuava mostrando "Entrar" depois do login** (E-016, reportado pelo Ícaro):
  o login funcionava e o token era guardado, mas `AccountHeader` vive no **layout raiz** e
  só consultava `/eu` ao montar — navegação client-side não remonta o layout, então a barra
  ficava congelada até um recarregamento. Corrigido com um evento de sessão que o cabeçalho
  escuta; escuta também o `storage`, então **sair numa aba atualiza as outras**. Nenhum dos
  74 testes e2e pegava, porque nenhum fazia login de verdade e depois olhava a barra.
- **Premissa contraditória em testes e2e**, exposta pela correção acima: três testes
  guardavam sessão e ao mesmo tempo mockavam `/eu` como 401 — e o cliente descarta o token
  nesse caso, comportamento correto do produto. Auxiliar `withValidSession` extraído para
  `tests/e2e/base.ts`, com a explicação escrita, em vez de corrigir caso a caso.
- **Divergência de hidratação em `/verificar-email`** (E-015): `isAuthenticated()` era
  chamado durante a renderização e lê `localStorage`, que não existe no servidor — o React
  acusava HTML divergente e **desistia de corrigir a subárvore**, deixando a tela com o
  conteúdo errado em silêncio. Passou por 66 e2e, 37 de componente e o build, porque jsdom
  não hidrata e **a tela não tinha nenhum teste e2e**. Corrigido, com duas redes novas: um
  `test` estendido em `web/tests/e2e/base.ts` que **reprova erro no console do navegador**,
  e cobertura e2e para a tela de confirmação de e-mail.
- **O limitador de tentativas estava mal chaveado.** Ele usava só o campo `email`, mas as
  rotas do Google e da união não enviam esse campo — então todas colapsavam num balde único
  por IP. Efeito real: quem entrasse pelo Google e depois confirmasse a união **se trancava
  sozinho**, misturando fluxos sem relação. A chave passou a ser o **alvo** (e-mail, ou o
  `merge_token` que identifica a conta), mantendo o teto por IP. **Atenção ao adicionar
  rota nova com `throttle:authentication`:** ela precisa mandar um alvo identificável.
- **O detector de dado sensível da auditoria dava falso positivo.** Ele varria o JSON
  inteiro procurando palavras proibidas, e o valor legítimo `confirmed_via: "password"`
  disparava o alarme. Passou a inspecionar **chaves**, recursivamente — detector que grita
  sem motivo ensina a ser ignorado.
- **Guarda de hidratação extraído para o hook `useHydrated`** depois de o mesmo defeito
  aparecer pela terceira vez (formulário, botão do Google, plano B da união). Sempre pego
  pelo e2e, **nunca** pelo teste de componente: jsdom não tem essa janela. Ver E-012.
- **PHP do WAMP não conseguia fazer NENHUMA chamada HTTPS** (E-014): `curl.cainfo` e
  `openssl.cafile` vazios e nenhum `cacert.pem` na máquina, então a troca do `code` com o
  Google falhava com `cURL error 60`. Corrigido com o `cacert.pem` oficial do projeto curl
  (121 raízes da Mozilla) instalado em `bin/php/php8.4.15/extras/ssl/` e apontado nos dois
  `.ini`, com backup. Destrava também o **Resend em produção** e qualquer API externa
  futura. Desativar a verificação de TLS foi descartado sem discussão.
- **O cadastro não funcionava no celular — três defeitos encadeados** (E-011, E-012, E-013),
  descobertos porque o Ícaro tentou usar a tela de verdade no aparelho. Todos corrigidos e
  confirmados por ele em 2026-08-31.
  - **API presa no loopback** (E-011): a task do VS Code subia `artisan serve` com
    `--host=127.0.0.1` enquanto o Next escuta em todas as interfaces. A porta 3000 chegava
    ao celular e a 8000 não — telas carregavam, nenhuma ação funcionava. Task corrigida
    para `0.0.0.0`.
  - **Senha ia parar na URL** (E-012): antes da hidratação o `onSubmit` do React não
    existe, e o toque no botão fazia o navegador submeter nativamente, em GET, com os
    campos na query string. O botão de envio agora só habilita depois de montar, o `form`
    ganhou `method="post"` e há teste de regressão que falha se a senha voltar à URL.
  - **403 fora do localhost** (E-013): o servidor de dev do Next recusa origem diferente de
    `localhost`, então chunks voltavam 403 e o React nunca hidratava — deixando o botão
    permanentemente desabilitado. Resolvido com `allowedDevOrigins` alimentado por
    `os.networkInterfaces()`.
- **Endereço da API deixa de ser configuração e passa a ser derivado.** O front resolve o
  host a partir de onde a página foi aberta (`web/src/lib/api.ts`) e a CSP faz o mesmo pelo
  header `Host` (`web/src/middleware.ts`). Abrindo em `localhost:3000` fala com
  `localhost:8000`; abrindo pelo IP no celular, com aquele IP. Elimina o `.env.local` com
  IP fixo, que quebrava a cada mudança de DHCP.
- **O botão de envio admite quando a tela não está pronta**: enquanto não hidrata, o rótulo
  é "Carregando…" em vez de exibir a ação como se estivesse disponível. Falha de hidratação
  passa a ser visível em vez de virar botão morto sem explicação.
- **A CSP quebrava a hidratação do Next — o app parecia certo e não funcionava** (E-009):
  header estático com `script-src 'self'` bloqueava os scripts inline de hidratação, então
  formulário não enviava e cabeçalho não atualizava, **sem erro visível na tela**. Os 18
  testes de componente passavam (jsdom não aplica CSP) e o build também; só o e2e em
  navegador real pegou. Corrigido com **CSP por nonce** em `web/src/middleware.ts` —
  afrouxar para `'unsafe-inline'` foi descartado por devolver o buraco de XSS que a CSP
  existe para fechar.
- **Classe errada de exceção no envelope de erro**: o handler capturava
  `Symfony\...\ThrottleRequestsException`, mas o Laravel lança a
  `Illuminate\Http\Exceptions\`. O 429 respondia "Too Many Attempts." em vez da mensagem
  em português que a tela mostra.
- **Um teste dava falso verde** (`LogoutTest`): a requisição após o logout passava porque,
  dentro de um mesmo teste, o guard mantém o usuário já resolvido. O token *era* apagado do
  banco. Adicionado `forgetGuards()` com comentário — sem ele, o teste afirmava algo que
  não verificava.
- **`composer require` abortava com "Permission denied" no zip temporário** (E-008): falha
  transitória de escrita (antivírus segurando o `.zip`), não permissão de pasta. Repetir
  resolveu. O `require` interrompido deixou `composer.json` com os pacotes e `vendor/`
  vazio, e gravou restrições como `"*"` — fixadas em `^0.13.42`, `^5.1` e `^8.3`.
- **`npm run build` quebrava com os testes passando**: `jest-axe` não traz tipos e
  `toHaveNoViolations` não é conhecido do Vitest, então o type-check reprovava mesmo com
  tudo verde em runtime. Corrigido com `@types/jest-axe` e a declaração do matcher em
  `web/tests/vitest.d.ts`.
- **Tasks do `web/` falhavam com `UnauthorizedAccess`** (E-006): task `shell` no Windows
  roda em PowerShell e o `npm` do PATH resolve para `npm.ps1`, bloqueado porque a
  ExecutionPolicy da máquina é `Restricted` (LocalMachine — verificado). Corrigido com
  `npm.cmd` (batch, não passa pela ExecutionPolicy) nas tasks `web: dev` e `web: build`,
  em vez de afrouxar a ExecutionPolicy global. Correção feita por outro agente a pedido do
  Ícaro; registrada no error-log nesta sessão, quando se descobriu que o comentário do
  `tasks.json` apontava para `E-005` — número já ocupado. Ponteiro corrigido para `E-006`.
  **Ampliado em 2026-08-30**: o alcance é maior do que se registrou — o
  `setup-tasks.ps1` do próprio Spec Kit falhou com o mesmo erro. A política atinge
  **qualquer `.ps1`**, não só as tasks do VS Code; contorno para scripts:
  `powershell.exe -NoProfile -ExecutionPolicy Bypass -File <script>`.
- **Achado de ambiente registrado** (E-007): dentro de sessão aberta **antes** da correção
  do PATH (E-004), `php` e `composer` continuam falhando, porque processo herda o ambiente
  de quando nasceu. O E-004 está resolvido — conferido no registro da máquina. Contorno
  para sessão em andamento documentado no error-log e no `quickstart.md` da spec 001.
- **Doc do método apontava comandos inexistentes** (E-005): `CLAUDE.md` e
  `development-workflow.md` diziam `/specify`, `/plan` e `/tasks`, mas as skills
  instaladas pelo Spec Kit 0.15.1 são `speckit-specify`, `speckit-plan` e `speckit-tasks`
  (não há `.claude/commands/`). Corrigido para os nomes reais; `/spec-check` e
  `/doc-sync` já estavam certos.

### Added
- **Spike descartável de frontend concluído** (BORA-32, 2026-08-29). No `api/`,
  `php artisan install:api` (Sanctum 4.3.3, `routes/api.php`, migration
  `personal_access_tokens`) e o endpoint **andaime** `GET /api/v1/eventos` com 3 eventos
  fixos, sem banco e sem auth (`app/Http/Controllers/Spike/EventoSpikeController.php`).
  No `web/`, a página `/eventos` renderizada no servidor, com um componente cliente só
  para provar CORS. **Código descartável, fora da Definition of Done** (Princípio XI):
  não é feature, não tem teste e **não define padrão de tela** — isso é da spec 001.
  As quatro perguntas do spike foram respondidas com evidência (comando + saída no
  comentário de fechamento da BORA-32):
  1. **SSR confirmado** — os três nomes aparecem no HTML cru de
     `curl -s http://localhost:3000/eventos`. O `await fetch` fica direto no componente de
     página, **sem `<Suspense>`**: os docs do Next 16 instalado dizem que `fetch` não é
     cacheado por padrão e bloqueia a renderização, e Suspense mandaria o conteúdo por
     streaming, fora do HTML inicial.
  2. **CORS do navegador OK sem configurar nada** — `Access-Control-Allow-Origin: *` vem
     do default do framework (`paths => ["api/*"]`), com `HandleCors` já no stack global.
     **Isso não fecha** o item de backlog "Setup de CORS/Sanctum SPA": `allowed_origins: *`
     não convive com `supports_credentials: true`, que o Sanctum vai exigir na spec 001.
  3. **360px sem rolagem horizontal** (`scrollWidth == clientWidth == 360`, nenhum elemento
     estourando); idem a 1280.
  4. **Fronteira servidor/cliente entendida** — **o Next fica**; não se aciona a
     alternativa React Router v7 prevista no ADR-0003.
- `web/src/app/layout.tsx`: `lang="en"` → `lang="pt-BR"`, para o leitor de tela anunciar o
  idioma certo (`ux-requirements.md`, acessibilidade técnica).
- **Projetos `api/` e `web/` criados** (BORA-31, 2026-08-29). `api/`: Laravel 13.29.0 sobre
  PHP 8.4.15 do WAMP, MySQL 8.4.7 (base `bora`), migrações rodadas, tabelas em InnoDB.
  `web/`: Next 16.3.3, React 19.2.8, TypeScript 5, Tailwind 4, App Router com `src/` e alias
  `@/*` — `npm run build` verificado. Node atualizado de 18.18.0 para **24.19.0 LTS**
  (Next 16 exige ≥ 20.9.0). `artisan serve` verificado respondendo 200.
- **Redis ativo em desenvolvimento** (2026-08-29): servidor Redis 8.2.5 em
  `127.0.0.1:6379`; extensão `phpredis` 6.3.0 instalada no PHP 8.4 do WAMP (que não a
  trazia) e habilitada nos dois `php.ini`; `CACHE_STORE`, `SESSION_DRIVER` e
  `QUEUE_CONNECTION` passam de `database` para `redis`. Fecha a pendência aberta no setup e
  alinha o ambiente ao Stack da constituição. Verificado pela facade `Cache`.
- `.vscode/tasks.json` — task de build padrão **`Bora: dev`** sobe `api/` (8000) e `web/`
  (3000) e abre o navegador em `localhost:3000` só depois do Next sinalizar `Ready`; mais
  `api: migrar banco` e `web: build`. Os padrões de detecção vieram da saída real dos dois
  servidores, não de suposição.
- `laravel/boost` v2.7.0 em `require-dev` + `.mcp.json` — **apenas o servidor MCP**
  (`--mcp`), sem diretrizes e sem skills, para dar acesso verificável ao estado da aplicação
  (esquema, config, log, docs da versão instalada) sem importar orientações que conflitam
  com o método. Servidor testado respondendo ao `initialize` do protocolo MCP. A autoridade
  segue sendo `CLAUDE.md` da raiz + constituição.

### Fixed
- **`artisan install:api` revertia a instalação do Sanctum** (E-004): o `composer.bat` do
  Windows roda `php composer.phar`, e o único `php` no PATH da máquina era o do XAMPP
  8.2.4, que não satisfaz o `"php": "^8.3"` do projeto. Ícaro trocou a entrada do PATH de
  `C:\xampp\php` para `C:\wamp64\bin\php\php8.4.15`. Desinstalar o XAMPP foi avaliado e
  descartado — ver E-004.
- **Migrações quebravam por MyISAM** (E-003): o MySQL do WAMP tem
  `default_storage_engine = MyISAM`, que limita índice a 1000 bytes e não tem transação nem
  chave estrangeira. Corrigido com `'engine' => 'InnoDB'` em `api/config/database.php` — no
  projeto, não no servidor, para não afetar o outro sistema da mesma máquina.

### Added
- **ADR-0003 — framework do frontend: Next.js (App Router) + React + TypeScript**
  (2026-08-29, decisão BORA-28). Critérios que decidiram: SEO do catálogo público e
  acessibilidade, ambos eliminatórios. Regras vinculantes: catálogo público renderizado no
  servidor, área logada renderizada no cliente (evita SSR + sessão do Sanctum) e nenhuma
  regra de negócio no `web/`. Descartados: Astro + ilhas React, React Router v7, Nuxt/Vue,
  SPA pura e Flutter Web (SEO), Blade/Livewire/Inertia (Princípio IV).
- Spike descartável de frontend no M0 (BORA-32) antes da spec 001, para absorver a curva de
  Next/React/CORS fora da Definition of Done.
- Decisão da tecnologia do app mobile (Flutter, React Native ou PWA) registrada no backlog
  como **deliberadamente adiada para a Fase 3** (BORA-33). O Princípio IV mantém as três
  portas abertas sem custo; a escolha da web não dependeu dessa e não a antecipa.
- **Landing page registrada como entregável futuro** (backlog, 2026-08-29). Decisão do
  Ícaro: não se constrói agora — entra no **lançamento do MVP**, alinhada com o que
  estiver documentado como produção *naquele momento*. Ficam registrados o gatilho, os
  bloqueios (marca/INPI para material público, identidade visual PENDENTE, setup do `web/`
  que é action item da spec 001) e a recomendação de modularização (seções como
  componentes + copy em módulo tipado; sem CMS/blocos configuráveis; primitivos
  compartilhados em `web/src/components/ui`). Escopo de uma eventual página de
  pré-lançamento fica em aberto, a informar pelo Ícaro.

### Fixed
- **O portão `/spec-check` não fazia o que a documentação dizia que ele fazia.** A skill não
  mencionava `ux-requirements.md`, tela, acessibilidade ou mobile, e o
  `spec-template.md` era o padrão de fábrica do Spec Kit — sem nenhuma seção de tela (e com
  um exemplo de premissa "Mobile support is out of scope for v1", incompatível com os
  Princípios XI e XII). Na prática, nada obrigava a spec a especificar a tela. Corrigido nos
  três pontos: o template ganha a seção obrigatória **"Tela e Experiência"**, a skill
  `spec-check` ganha critérios Bloqueantes explícitos (tela declarada, comportamento a
  360px, polegar, estados, acessibilidade, testes em 360 e 1280) e o
  `development-workflow.md` passa a descrever o portão que existe de fato.

### Changed
- `ux-requirements.md` ganha a seção **"Dispositivo principal: o celular"** (2026-08-29):
  mobile-first deixa de ser adjetivo e vira requisito verificável — estilo base do celular,
  piso de 360px sem rolagem horizontal, ação principal ao alcance do polegar, nada
  dependente de `hover`, uma coluna no celular, e teste de tela em duas larguras (360 e
  1280). Vale inclusive para o painel do estabelecimento. `vision.md` alinhado.
- Constituição 1.1.1 → **1.2.0** (Emenda 2, 2026-08-29): o Stack Tecnológico Obrigatório
  deixa de ter o framework de frontend como PENDENTE e passa a exigir Next.js + React +
  TypeScript com as três regras acima. Nenhum princípio adicionado, redefinido ou removido.
  `CLAUDE.md`, `development-workflow.md`, `backlog.md`, ADR-0001 e ADR-0002 sincronizados.
- **Linear: o Bora passou a ter time próprio** — time `Bora`, key `BORA` (2026-08-29),
  revendo a decisão de usar o time do Nexa. No Linear o prefixo do identificador vem do
  time, não do projeto: dentro do time Nexa as issues saíam como `NEX-nn` e não
  identificavam o produto. As 31 issues migraram sem perda (projeto, marcos M0–M5,
  prioridades, status e label `decisao-pendente` preservados); a renumeração ficou
  invertida — `BORA-n` = `NEX-(40−n)`. `CLAUDE.md`, `development-workflow.md`,
  `backlog.md` e `linear-import.md` atualizados.
- **Repositório renomeado de `ibar` para `bora`** (`C:\wamp64\www\bora`), aposentando o
  codinome de trabalho. Referências atualizadas em `CLAUDE.md`, `vision.md`, `brand.md`,
  `development-workflow.md`, `linear-import.md` e ADR-0001; constituição 1.1.0 → **1.1.1**
  (PATCH, só redação do Escopo). O projeto no Linear já se chamava "Bora".
- Constituição 1.0.0 → **1.1.0** (Emenda 1, 2026-08-28): novos Princípios XI (Entrega
  Vertical com Validação Visual — feature pronta = API documentada + tela 100% no front +
  testes back/front aprovados + validação visual do Ícaro antes da próxima feature) e XII
  (Usabilidade Universal — design interativo, simples e acessível a todos os perfis,
  incluindo idosos); Princípio IV expandido (frontend desacoplado consumindo só a API
  pública; prontidão mobile — app quando o site tiver boa aceitação); Princípio IX
  expandido (testes automatizados também no frontend).
- **Bora** promovido de nome de trabalho a **nome oficial da solução** (registro INPI
  segue no backlog); iBar permanece só como codinome de repositório. `vision.md`,
  `brand.md`, `CLAUDE.md`, `development-workflow.md` e `linear-import.md` sincronizados.
- Design do Figma original oficialmente aposentado — substituído pelos requisitos de
  `docs/product/ux-requirements.md`.
- `development-workflow.md`: ciclo ganha o passo de validação visual e a Definition of
  Done da feature; estrutura passa a prever `api/` e `web/`.

### Added
- `docs/product/investment-plan.md` — orçamento de 18 meses (R$ 350 mil), cronograma
  M0–M5 em 12 meses, TAM/SAM/SOM, dados de campo das duas cidades (IBGE 2025, guia
  comercial) e a lista de premissas ainda não validadas. Base do pitch para anjo.
- Projeto **Bora** criado no Linear com marcos M0–M5, issues de setup e issues
  `decisao-pendente` espelhando o backlog (31 issues; hoje `BORA-1..BORA-31` — ver a
  migração de time em *Changed*).
- ADR-0002 — frontend desacoplado no mesmo repositório (`api/` + `web/`), comunicação
  exclusivamente via API pública.
- `docs/product/ux-requirements.md` — requisitos vinculantes de UX e acessibilidade
  (WCAG 2.1 AA, alvos de toque, linguagem simples, foco em idosos).
- Constituição 1.0.0 ratificada: Princípios I–X (conta única multi-papel, gratuidade do
  usuário final, conformidade de conteúdo de terceiros, API-first, segurança, assíncrono,
  simplicidade, auditoria, qualidade verificável, preservação de histórico) e stack
  (Laravel 13/MySQL/Redis, banco único).
- ADR-0001 — reuso da stack do Nexa sem multi-tenancy.
- Visão de produto aprovada (`docs/product/vision.md`): plataforma de três lados, ideia
  extraída do Figma "App Rolezeiros" + anotações; fases MVP → monetização → bilheteria.
- Modelo de negócio (`docs/product/monetization.md`): Freemium B2B em fases, gratuito
  para o usuário final.
- Estudo de marca (`docs/product/brand.md`): nome de trabalho **Bora** (registro
  PENDENTE), análise da paleta (contrastes medidos, recomendação dark-first) e avaliação
  da logo (conceito pin+nota mantido; execução a modernizar).
- Catálogo de regras: `plataforma.md` (RN-PLAT-001..006), `locais.md` (RN-LOCAL-001..004),
  `artistas.md` (RN-ART-001..003), `eventos.md` (RN-EVENTO-001..004), `avaliacoes.md`
  (RN-AVAL-001..004), `descoberta.md` (RN-DESC-001..006), `divisao-conta.md`
  (RN-CONTA-001).
- Método de desenvolvimento espelhado do Nexa (Spec Kit + skills `spec-check`,
  `domain-rule`, `adr-new`, `screen-help`, `doc-sync`) e rastreio no Linear
  (`docs/logs/linear-import.md` — aguardando autenticação do conector).
- Backlog com as decisões pendentes que bloqueiam spec; rascunho de modelo de dados.
- Telas originais do Figma preservadas em `docs/product/design/figma/`.
