# Feature Specification: Fundação de Contas e Autenticação

**Feature Branch**: `001-contas-autenticacao`

**Created**: 2026-08-29

**Status**: **Em implementação** (portão `spec-check`: SIM em 2026-08-29; aprovada por
Ícaro em 2026-08-30). Plano em [plan.md](./plan.md), tarefas em [tasks.md](./tasks.md).
**Progresso: 44/119 tarefas** — Setup e Foundational concluídos e verdes; as quatro user
stories ainda não começaram.

**Input**: User description: "fundação de contas e autenticação: conta única multi-papel
(RN-PLAT-001) com login via Google OAuth ou e-mail/senha (RN-PLAT-002), incluindo as telas
de login e cadastro"

## Decisões ratificadas nesta spec

Registradas aqui porque eram PENDENTE no catálogo/backlog e **bloqueavam esta feature**.
Todas decididas por **Ícaro em 2026-08-29** (nesta sessão) — nenhuma é suposição do
assistente:

| # | Decisão | Escolha |
|---|---------|---------|
| D1 | Fluxo de confirmação ao unir credenciais (RN-PLAT-002) | **Senha da conta existente confirma a união; link por e-mail é o plano B** para quem esqueceu a senha. Direção inversa (conta que nasceu Google define senha) exige sessão ativa. |
| D2 | Autenticação do `web/` + política de CORS | **Token Bearer** (mesmo mecanismo do futuro app mobile — Princípio IV). CORS com **origens explícitas** e sem credenciais de cookie (o spike provou que `allowed_origins: "*"` não convive com `supports_credentials: true`; com Bearer, `supports_credentials` permanece `false`). **Ressalva registrada em 2026-08-30:** a doc oficial do Sanctum instalado *desaconselha* token de API para SPA de primeira parte, recomendando o modo cookie. Ícaro reconfirmou a D2 com o risco à vista, e definiu a guarda do token: **`localStorage`**, com mitigações obrigatórias. Razões e mitigações em [research.md](./research.md) §3 e em [plan.md](./plan.md) (Complexity Tracking). |
| D3 | Padrão de documentação da API | **OpenAPI gerado automaticamente a partir do código (Scramble)** — a doc acompanha cada feature por construção. |
| D4 | Base de UI e testes de front (action item do ADR-0003) | **shadcn/ui (primitivas Radix + Tailwind, código copiado para o repo)**. Testes: **Vitest + React Testing Library + axe** nos componentes; **Playwright** e2e nas larguras 360 e 1280. |
| D5 | Verificação de e-mail no cadastro próprio | **Envia, mas não bloqueia**: a pessoa usa o site imediatamente; aviso discreto até confirmar. Ações sensíveis futuras podem exigir e-mail verificado. |
| D6 | Recuperação de senha | **Entra no escopo desta spec** ("Esqueci minha senha" na tela de login). |
| D7 | Validade da sessão | **30 dias de inatividade, renovada a cada uso** — valor inicial de um **parâmetro configurável** (a política vive no domínio; o prazo é dado). |
| D8 | Provedor de e-mail transacional (PENDENTE constitucional) | **Resend** em produção, atrás de porta & adapter; em dev, captura local. |

> D2–D4 são decisões de tecnologia registradas de propósito nesta seção (eram action items
> do ADR-0002/0003 endereçados "na spec 001"). Os requisitos funcionais abaixo permanecem
> agnósticos de tecnologia.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Criar conta com e-mail e senha e entrar (Priority: P1)

Uma pessoa que quer saber "onde tem rolê hoje" cria sua conta no Bora com nome, e-mail e
senha, e passa a entrar no site com essas credenciais. A conta é **única e multi-papel**
(RN-PLAT-001): nasce com o papel de rolezeiro e, no futuro, acumulará papéis (gestor,
artista) — nunca contas separadas.

**Why this priority**: sem conta e login não existe nenhuma outra feature da plataforma;
é a fundação que todo o resto pressupõe.

**Independent Test**: com o sistema no ar e nenhum dado prévio, uma pessoa consegue criar
conta, sair e entrar de novo — valor entregue: identidade persistente na plataforma.

**Acceptance Scenarios**:

1. **Given** nenhuma conta com o e-mail X, **When** a pessoa preenche nome, e-mail X e
   senha válida e confirma, **Then** a conta é criada com papel rolezeiro, a pessoa fica
   autenticada e vê confirmação de boas-vindas; um e-mail de verificação é enviado **sem
   bloquear o uso** (D5), e um aviso discreto fica visível até a confirmação.
2. **Given** uma conta existente com e-mail X e senha S, **When** a pessoa entra com X e S,
   **Then** fica autenticada e é levada à tela de origem (ou à home).
3. **Given** uma pessoa autenticada, **When** toca em "Sair", **Then** a sessão é
   encerrada naquele aparelho e a próxima visita pede login.
4. **Given** uma conta existente com e-mail X, **When** alguém tenta **criar conta** com o
   mesmo e-mail X, **Then** o sistema **não cria conta paralela** (Princípio I): informa em
   linguagem humana que o e-mail já tem conta e oferece ir para o login (ou recuperar a
   senha).
5. **Given** o formulário de cadastro, **When** a pessoa envia e-mail malformado ou senha
   abaixo do mínimo, **Then** o erro aparece **no campo**, em linguagem humana, dizendo o
   que corrigir — nada é criado.
6. **Given** uma conta com e-mail X e senha S, **When** a pessoa erra a senha, **Then** a
   mensagem diz que e-mail ou senha não conferem e oferece "Esqueci minha senha"; após
   exceder o limite de tentativas, novas tentativas são bloqueadas temporariamente com
   mensagem informando quanto tempo esperar.
7. **Given** o link do e-mail de verificação (D5), **When** a pessoa o abre dentro do
   prazo, **Then** o e-mail fica marcado como verificado e o aviso some; expirado, a tela
   oferece reenviar.

---

### User Story 2 - Entrar com Google (Priority: P2)

Uma pessoa entra no Bora com sua conta Google, sem criar senha. Na primeira vez, isso cria
a sua conta única na plataforma; nas seguintes, apenas autentica.

**Why this priority**: é o caminho de menor fricção para o público-alvo e reduz abandono
no primeiro contato; depende da fundação da US1 (modelo de conta), por isso P2.

**Independent Test**: com uma conta Google real de teste, a pessoa completa o fluxo e sai
autenticada; repetir o fluxo autentica na **mesma** conta, nunca numa segunda.

**Acceptance Scenarios**:

1. **Given** nenhuma conta com o e-mail G, **When** a pessoa conclui o login Google com o
   e-mail G, **Then** uma conta única é criada (papel rolezeiro), com e-mail já
   considerado verificado, e a pessoa fica autenticada.
2. **Given** uma conta criada via Google com e-mail G, **When** a pessoa repete o login
   Google, **Then** autentica **na mesma conta** — nunca cria duplicata (Princípio I).
3. **Given** a pessoa iniciou o login Google, **When** cancela ou o provedor recusa/falha,
   **Then** volta à tela de entrar com mensagem em linguagem humana e a opção de tentar de
   novo ou usar e-mail/senha — nenhuma conta é criada.
4. **Given** uma conta criada via Google (sem senha) com e-mail G, **When** alguém tenta
   **cadastrar** e-mail/senha com o mesmo e-mail G, **Then** o sistema não cria conta
   paralela: informa que esse e-mail já entra com Google e orienta a entrar por lá (e que
   poderá definir uma senha depois, já autenticada — D1, direção inversa).
5. **Given** uma conta criada via Google, **When** a pessoa, autenticada, define uma senha
   para a conta, **Then** passa a poder entrar também por e-mail/senha — mesma conta,
   mesmas duas credenciais (RN-PLAT-002).

---

### User Story 3 - Unir credenciais Google ↔ e-mail/senha (Priority: P3)

Uma pessoa que criou conta com e-mail/senha resolve entrar com Google usando o mesmo
e-mail (ou vice-versa). O sistema **une as credenciais na mesma conta, mediante
confirmação do titular** (RN-PLAT-002) — nunca cria uma segunda conta.

**Why this priority**: é a garantia viva do Princípio I no cruzamento dos dois métodos;
depende de US1 e US2 existirem.

**Independent Test**: criar conta por e-mail/senha, entrar com Google com o mesmo e-mail,
confirmar a união e verificar que existe **uma** conta com os dois meios de entrada.

**Acceptance Scenarios**:

1. **Given** uma conta com e-mail X criada por e-mail/senha, **When** a pessoa entra com
   Google usando X, **Then** o sistema apresenta a tela de **unir contas** explicando o que
   vai acontecer e pede **a senha da conta existente** para confirmar (D1).
2. **Given** a tela de unir contas, **When** a pessoa confirma com a senha correta,
   **Then** as credenciais ficam unidas na mesma conta, a pessoa fica autenticada e vê
   confirmação; das próximas vezes, qualquer um dos dois métodos entra na mesma conta.
3. **Given** a tela de unir contas, **When** a pessoa não lembra a senha, **Then** pode
   pedir **um link de confirmação por e-mail** (plano B da D1); ao abrir o link dentro do
   prazo, a união se completa como no cenário 2.
4. **Given** a tela de unir contas, **When** a pessoa erra a senha, **Then** vê erro em
   linguagem humana e pode tentar de novo (com o mesmo limite de tentativas do login) ou
   usar o plano B; **When** cancela, **Then** nada é unido e nenhuma conta nova é criada.
5. **Given** um link de união expirado ou já usado, **When** a pessoa o abre, **Then** vê
   mensagem explicando e o caminho para gerar um novo — a união não ocorre.
6. **Given** qualquer desfecho da união (confirmada, cancelada, expirada), **Then** o
   número de contas com o e-mail X é **exatamente um** (Princípio I — invariante).

---

### User Story 4 - Recuperar senha esquecida (Priority: P4)

Uma pessoa que esqueceu a senha recupera o acesso sozinha, pelo e-mail, sem depender de
suporte (D6).

**Why this priority**: sem esse caminho, quem esquece a senha fica trancado para sempre —
cenário de falha previsível (Princípio IX); depende apenas de US1.

**Independent Test**: com uma conta existente, disparar "Esqueci minha senha", usar o link
recebido, definir nova senha e entrar com ela.

**Acceptance Scenarios**:

1. **Given** a tela de entrar, **When** a pessoa toca "Esqueci minha senha" e informa seu
   e-mail, **Then** vê a mensagem neutra "Se este e-mail estiver cadastrado, você receberá
   um link para redefinir a senha" — a mesma mensagem exista ou não a conta (não revela
   quais e-mails têm cadastro).
2. **Given** o link de redefinição válido, **When** a pessoa define uma senha nova válida,
   **Then** a senha antiga deixa de valer, as sessões ativas dos demais aparelhos são
   encerradas, e a pessoa consegue entrar com a nova senha.
3. **Given** um link de redefinição expirado ou já usado, **When** a pessoa o abre,
   **Then** vê mensagem explicando e o botão para pedir um novo link.
4. **Given** uma conta criada **só via Google** (sem senha), **When** seu e-mail é usado em
   "Esqueci minha senha", **Then** a mensagem pública é a mesma neutra do cenário 1, e o
   e-mail enviado orienta: essa conta entra com Google (definir senha se faz autenticado —
   D1).

---

### Edge Cases

- **Dupla submissão** (toque duplo no botão em rede lenta): cadastro, login e união são
  idempotentes — jamais criam duas contas ou duas uniões.
- **E-mail com maiúsculas/espaços** ("  Maria@Gmail.com "): normalizado antes de comparar
  e armazenar — `RN-PLAT-001` não pode ser burlada por variação de caixa.
- **Provedor Google indisponível ou sem retornar e-mail**: mensagem em linguagem humana +
  caminho alternativo (e-mail/senha); nenhum estado parcial de conta.
- **Sessão expirada no meio do uso** (30 dias de inatividade — D7): a próxima ação leva à
  tela de entrar com mensagem explicando; após entrar, a pessoa volta para onde estava.
- **Limite de tentativas** de login/união excedido: bloqueio temporário com mensagem
  dizendo quanto esperar; o bloqueio vale também para o fluxo de união (mesma porta).
- **Links de e-mail** (verificação, união, redefinição) expirados, já usados ou
  adulterados: sempre mensagem explicativa + caminho para gerar novo; nunca erro cru.
- **Cadastro durante indisponibilidade do envio de e-mail**: a conta é criada e o uso não
  é bloqueado (D5); o envio da verificação é reprocessado depois (operação assíncrona,
  Princípio VI).
- **E-mail da conta Google mudou** desde o vínculo: a entrada com Google continua caindo
  **na mesma conta** — o vínculo é pelo identificador do provedor, não pelo e-mail; o
  e-mail da conta no Bora não muda sozinho (troca de e-mail está fora de escopo).
- **Senha no limite mínimo exato** aceita; um caractere a menos, recusada com mensagem no
  campo.

### Cenários de teste por regra e princípio (Princípio IX)

Mapa de rastreabilidade — cada regra referenciada tem teste que a exercita, incluindo
caminho de erro; cada princípio NON-NEGOTIABLE tocado tem teste que **prova o bloqueio**:

| Regra / Princípio | Cenário(s) que provam | Tipo |
|---|---|---|
| RN-PLAT-001 (conta única multi-papel) | US1-4 (cadastro duplicado bloqueado), US2-2 (Google repetido não duplica), US3-6 (invariante: 1 conta por e-mail), edge de normalização de e-mail | back + front |
| RN-PLAT-002 (autenticação; união com confirmação) | US1-1/2 (e-mail/senha), US2-1/3 (Google, incl. falha), US3-1..5 (união: senha, plano B, erro, expiração, cancelamento), US2-5 (senha em conta Google exige sessão ativa) | back + front |
| RN-PLAT-003 (gratuidade do usuário final) | Teste que percorre cadastro, login, união e recuperação e **prova que nenhum passo exige, menciona ou condiciona pagamento**; a API desta feature não expõe nenhuma operação de cobrança (Princípio II — bloqueio provado) | back + front |
| RN-PLAT-004 (auditoria de escrita) | Criação de conta, união de credenciais, definição/troca de senha e verificação de e-mail geram registro de auditoria (quem, quando, o quê); teste verifica presença e conteúdo do registro | back |
| Princípio I (NON-NEGOTIABLE) | Os mesmos de RN-PLAT-001 — em especial: tentativa deliberada de criar segunda conta com e-mail existente (pelo cadastro **e** pelo Google) é **rejeitada/unificada**, nunca duplicada | back + front |
| Princípio II (NON-NEGOTIABLE) | O mesmo de RN-PLAT-003 — nenhuma funcionalidade de consumo atrás de pagamento | back |
| Princípio III (NON-NEGOTIABLE) | Tocado apenas como **autenticação via OAuth oficial do Google** (FR-009) — nenhum conteúdo de terceiros é importado ou exibido nesta feature, logo não há superfície de scraping a bloquear; teste garante que o fluxo Google usa exclusivamente o mecanismo oficial de autorização e que só os dados consentidos no OAuth (nome, e-mail, identificador) são armazenados | back |
| Princípio V (segurança por padrão) | Limite de tentativas bloqueia (com teste do caminho bloqueado); senha e tokens **nunca** aparecem em log; recuperação de senha responde neutro (não enumera e-mails) | back |
| Princípio X | Não tocado nesta feature: exclusão/anonimização de conta está **fora de escopo** (ver Out of scope) | — |

## Requirements *(mandatory)*

### Functional Requirements

**Conta única (RN-PLAT-001)**

- **FR-001**: O sistema MUST manter **uma conta por pessoa**, identificada pelo e-mail
  normalizado (minúsculas, sem espaços nas pontas); papéis são perfis vinculados à conta.
- **FR-002**: Toda conta nova MUST nascer com o papel **rolezeiro**; a estrutura MUST
  suportar acúmulo de papéis futuros (gestor, artista) **sem** criação de nova conta.
- **FR-003**: Tentativa de criar conta com e-mail já existente MUST ser recusada com
  orientação (ir ao login / recuperar senha / entrar com Google, conforme o caso) — nunca
  criar conta paralela.

**Cadastro e login por e-mail/senha (RN-PLAT-002)**

- **FR-004**: O sistema MUST permitir cadastro com nome, e-mail e senha; a senha MUST
  respeitar comprimento mínimo definido por **parâmetro configurável** (valor inicial: 8).
- **FR-005**: Após o cadastro próprio, o sistema MUST enviar e-mail de verificação **sem
  bloquear o uso** (D5), exibir aviso discreto até a confirmação e permitir reenvio.
- **FR-006**: O sistema MUST autenticar por e-mail/senha e MUST responder a falhas com
  mensagem única ("e-mail ou senha não conferem") sem revelar qual dos dois errou.
- **FR-007**: O sistema MUST limitar tentativas de autenticação (parâmetro configurável;
  valor inicial: 5 por minuto por combinação e-mail+origem) e informar o tempo de espera.
- **FR-008**: A pessoa autenticada MUST poder encerrar a sessão do aparelho atual ("Sair").

**Login com Google (RN-PLAT-002)**

- **FR-009**: O sistema MUST permitir entrar com Google (OAuth oficial — Princípio III);
  primeira entrada com e-mail inédito cria a conta única (e-mail já verificado);
  entradas seguintes autenticam na mesma conta.
- **FR-010**: Cancelamento, recusa ou falha no provedor MUST retornar à tela de entrar com
  mensagem em linguagem humana e alternativa por e-mail/senha, sem estado parcial.

**União de credenciais (RN-PLAT-002 — D1)**

- **FR-011**: Entrada com Google usando e-mail de conta existente por e-mail/senha MUST
  disparar o fluxo de **união mediante confirmação do titular**: confirmação pela **senha
  da conta existente**, com **link por e-mail como plano B**; união concluída resulta em
  uma conta com os dois meios de entrada.
- **FR-012**: Conta criada via Google MUST poder **definir senha** apenas com sessão ativa
  (D1 — direção inversa), passando a aceitar os dois métodos.
- **FR-013**: União cancelada, negada ou expirada MUST deixar o sistema exatamente como
  antes — nenhuma conta criada, nenhuma credencial vinculada.

**Recuperação de senha (D6)**

- **FR-014**: O sistema MUST oferecer "Esqueci minha senha": solicitação por e-mail com
  resposta **neutra** (não confirma existência de cadastro), link com validade por
  **parâmetro configurável** (valor inicial: 60 min), uso único.
- **FR-015**: Redefinição bem-sucedida MUST invalidar a senha anterior e encerrar as
  sessões ativas dos demais aparelhos.

**Sessão (D2, D7)**

- **FR-016**: A sessão MUST expirar após período de inatividade definido por **parâmetro
  configurável** (valor inicial: 30 dias), renovando-se a cada uso; sessão expirada leva à
  tela de entrar com retorno ao ponto de origem após autenticar.

**Transversais**

- **FR-017**: Toda operação de escrita desta feature (criação de conta, união, senha
  definida/trocada, verificação) MUST gerar registro de auditoria — quem, quando, o quê
  (RN-PLAT-004).
- **FR-018**: Nenhum passo de cadastro, login, união ou recuperação PODE exigir ou
  mencionar pagamento (RN-PLAT-003, Princípio II).
- **FR-019**: Senhas, tokens e dados pessoais MUST ficar fora de logs (Princípio V);
  e-mails transacionais MUST ser enviados de forma assíncrona (Princípio VI), atrás de
  porta & adapter (provedor: D8).
- **FR-020**: Toda a funcionalidade MUST ser exposta pela API pública documentada (D3)
  antes/junto da tela, pronta para o futuro app mobile sem mudança estrutural
  (Princípio IV); a tela consome exclusivamente essa API (ADR-0002).

### Key Entities *(include if feature involves data)*

- **Conta**: a pessoa na plataforma — nome, e-mail (único, normalizado), indicador de
  e-mail verificado, senha (opcional — conta pode nascer via Google), datas de criação/uso.
- **Papel**: perfil vinculado à conta (rolezeiro nesta feature; gestor e artista em
  features futuras). Uma conta, N papéis — nunca o inverso.
- **Vínculo Google**: credencial externa ligada à conta (identificador do provedor);
  presença do vínculo habilita entrar com Google.
- **Sessão**: autenticação ativa de um aparelho — criada no login, renovada no uso,
  expirável (D7), revogável no "Sair" e na troca de senha.
- **Token de e-mail**: solicitação de uso único com validade (verificação de e-mail, união
  de credenciais, redefinição de senha).

## Tela e Experiência *(obrigatório neste projeto — Princípios XI e XII)*

Referência vinculante: `docs/product/ux-requirements.md` — todos os requisitos daquele
documento se aplicam a todas as telas abaixo (mobile-first literal, piso 360px, uma
coluna no celular, linguagem simples, alvos ≥ 44px, contraste AA, feedback de toda ação).

### Telas entregues nesta feature

- **Entrar** — ação principal: entrar (botão "Entrar"). Caminho: ação "Entrar" visível no
  topo de qualquer página pública, 1 toque. Contém: botão "Entrar com Google", campos
  e-mail/senha, links "Esqueci minha senha" e "Criar conta".
- **Criar conta** — ação principal: criar a conta (botão "Criar conta"). Caminho: 1 toque
  a partir da tela Entrar (ou direto pela ação "Criar conta" pública). Contém: nome,
  e-mail, senha, botão Google como alternativa.
- **Unir contas** — ação principal: confirmar a união (botão "Unir e entrar"). Caminho:
  aparece automaticamente no fluxo do login Google quando o e-mail já tem conta (US3);
  explica em linguagem simples o que será unido; campo de senha + alternativa "Receber
  link por e-mail".
- **Esqueci minha senha** — ação principal: enviar o link (botão "Enviar link"). Caminho:
  1 toque a partir de Entrar.
- **Redefinir senha** — ação principal: salvar a nova senha. Caminho: link recebido por
  e-mail.
- Complemento de navegação (não é tela): estado autenticado no cabeçalho, com nome/avatar
  e ação "Sair" (rótulo de texto, não só ícone).

### Comportamento no celular (dispositivo principal)

- **A 360px**: uma coluna; formulário em largura total com margens confortáveis; botão
  "Entrar com Google" e formulário empilhados (Google primeiro, por ser o caminho de menor
  fricção); sem rolagem horizontal; cada tela cabe em uma altura de tela ou rola
  verticalmente sem cortar conteúdo. Estilo base é o do celular; media queries só ampliam.
- **Alcance do polegar**: o botão da ação principal fica imediatamente abaixo dos campos —
  na metade inferior da tela nos formulários desta feature (formulários curtos); nenhuma
  ação principal exige alcançar o topo da tela.
- **A partir de 768px / 1280px**: o formulário vira um cartão centralizado com largura
  máxima legível (o computador não espalha controles nem vira "celular esticado"); o
  restante do layout permanece idêntico — mais respiro, não mais controles.

### Estados obrigatórios

- **Carregando**: botão da ação principal mostra indicador e fica desabilitado (evita
  dupla submissão); campos bloqueados durante o envio; no retorno do Google, tela
  intermediária "Entrando…" com indicador.
- **Vazio**: formulários chegam com rótulos sempre visíveis e dicas de preenchimento
  (ex.: "Use um e-mail que você acessa — enviaremos confirmações para ele"); a tela
  "Esqueci minha senha" pós-envio ensina o próximo passo ("Confira sua caixa de entrada e
  o spam; o link vale por 1 hora").
- **Erro**: mensagem **no campo** correspondente, em linguagem humana, dizendo o que
  fazer ("Digite um e-mail válido, como nome@exemplo.com"; "A senha precisa de pelo menos
  8 caracteres"); falhas gerais (Google indisponível, rede) em faixa no topo do formulário
  com ação de tentar de novo; nunca código de erro cru.
- **Sucesso**: confirmação visível ("Conta criada! Boas-vindas ao Bora") e
  redirecionamento ao destino de origem; união concluída confirma explicitamente ("Pronto
  — agora você pode entrar com Google ou com sua senha").

### Acessibilidade (`docs/product/ux-requirements.md`)

- Itens do documento que estas telas cumprem explicitamente: fonte base ≥ 16px respeitando
  ajuste do aparelho; contraste AA em todo texto (o laranja da marca não é cor de texto);
  formulários mínimos com um assunto por etapa; linguagem do dia a dia sem estrangeirismo
  evitável ("Entrar", "Criar conta", "Sair"); caminho de volta sempre visível; nada de
  gesto obscuro.
- Alvos de toque ≥ 44×44px (botões, links "Esqueci minha senha"/"Criar conta" com área de
  toque completa), contraste AA, **ícone sempre com rótulo** (ex.: mostrar/ocultar senha),
  foco visível em todos os controles, navegação completa por teclado (ordem lógica,
  submissão por Enter), **nada dependente de `hover`**: confirmado — formulários usam
  HTML semântico (`form`, `label` associado a campo, mensagens de erro anunciadas a
  leitores de tela), zoom de 200% sem quebra.

### Testes de tela (Princípio IX)

- Larguras testadas: **360 e 1280** (obrigatórias), via testes e2e (Playwright — D4),
  incluindo asserção de ausência de rolagem horizontal a 360px.
- Verificação automatizada de acessibilidade: **axe** integrado aos testes de componente
  (Vitest + React Testing Library — D4) e às páginas nos testes e2e; zero violações como
  critério de aprovação.
- Cenários de erro da tela cobertos por teste: e-mail inválido e senha curta no cadastro
  (erro no campo); credenciais erradas no login (mensagem única + oferta de recuperação);
  limite de tentativas excedido (mensagem com tempo de espera); cancelamento/falha do
  Google (retorno com alternativa); senha errada na união + caminho do plano B; link
  expirado (união e redefinição); dupla submissão não duplica conta; sessão expirada leva
  ao login e retorna ao ponto de origem.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Uma pessoa cria conta e chega autenticada em **menos de 2 minutos** pelo
  celular, sem ajuda.
- **SC-002**: Entrar com Google leva **menos de 30 segundos** do toque ao estado
  autenticado.
- **SC-003**: **Zero contas duplicadas** por e-mail em qualquer sequência de cadastro,
  login Google e união — inclusive sob tentativa deliberada (invariante do Princípio I).
- **SC-004**: **100% dos caminhos de erro** desta feature exibem mensagem em linguagem
  humana com o próximo passo — nenhum código de erro cru chega à tela.
- **SC-005**: As cinco telas passam a verificação automatizada de acessibilidade **sem
  violações**, nas larguras 360 e 1280.
- **SC-006**: Quem esqueceu a senha recupera o acesso **sozinho em menos de 5 minutos**
  (do "Esqueci minha senha" ao login com a senha nova).
- **SC-007**: Nenhum passo de cadastro, login, união ou recuperação exige pagamento
  (Princípio II — verificado por teste).

## Assumptions

- Toda conta nova nasce com o papel **rolezeiro** — o consumo é gratuito e aberto
  (Princípio II), e os papéis de gestor/artista serão vinculados nas features de cadastro
  de local e de artista. *(Default do assistente, coerente com a constituição; não decide
  regra nova.)*
- Formulário de cadastro mínimo: **nome, e-mail, senha** (ux-requirements: pedir só o
  necessário). Dados de perfil adicionais ficam para a feature de perfil.
- Valores iniciais de parâmetros configuráveis (a política vive no domínio; o número é
  dado): senha mínima 8 caracteres; limite de 5 tentativas/minuto; link de redefinição e
  de união valem 60 minutos; link de verificação de e-mail vale 7 dias; sessão expira em
  30 dias de inatividade (D7 — este último é decisão do Ícaro, os demais são defaults do
  assistente registrados aqui para não estarem "hardcoded e invisíveis").
- Resposta neutra na recuperação de senha (não confirma existência do e-mail) — padrão de
  segurança contra enumeração de contas.
- Em desenvolvimento, e-mails são capturados localmente; em produção, provedor da D8.
  **Dependência externa registrada**: envio em produção exige domínio próprio com
  SPF/DKIM — o domínio está no backlog (item marca/INPI/domínio) e **não** bloqueia o
  desenvolvimento, mas bloqueia o go-live do envio real.
- O celular é o dispositivo principal e a tela faz parte da feature (Princípios XI e XII)
  — "mobile fica para depois" e "só backend" não são premissas válidas.

## Out of scope (desta spec)

- Exclusão/anonimização de conta (RN-PLAT-005) — spec própria, com os fluxos LGPD.
- Vinculação dos papéis gestor de estabelecimento e artista (chegam com as features de
  local e de artista; o modelo de conta desta spec já os suporta).
- Edição de perfil (nome, foto), troca de e-mail, gerenciamento de aparelhos conectados,
  autenticação em dois fatores, outros provedores sociais (Apple/Facebook).
- Telas do app mobile (Fase 3) — a API desta feature já nasce pronta para ele
  (Princípio IV).
