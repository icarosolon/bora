# Error Log

Registro de erros no formato `E-NNN` (sintoma, causa, resolução, status), mantido pela
skill `doc-sync`.

## E-017 — `sed` no Git Bash come barra invertida passada por argumento (2026-08-31)

- **Sintoma:** durante a refatoração de nomenclatura, 68 dos 185 testes de backend
  quebraram com `Class "App\UseCases\Account\AuditLog" not found`. O arquivo tinha
  `use AppSupportAuditLog;` — as barras invertidas do namespace **sumiram**.
- **Causa:** o Git Bash do Windows (MSYS) converte argumentos que **parecem caminho** antes
  de entregá-los ao programa. `sed -e 's/.../App\\Support\\AuditLog/'` chega ao `sed` já
  mutilado. Não é bug do `sed`: é a camada de tradução de caminhos do MSYS. Vale para
  qualquer executável nativo chamado com barra invertida no argumento.
- **Resolução:** **nunca passar barra invertida por argumento** neste ambiente. Escrever as
  substituições num **arquivo de script** (`sed -f arquivo.sed`) — o conteúdo do arquivo não
  passa pela conversão. `cat <<'EOF'` também funciona, porque o conteúdo vai por stdin.
- **Status:** resolvido; namespaces restaurados e 185/185 verdes.
- **Já tinha mordido antes**, como nota lateral no E-014: o caminho do `cacert.pem` saiu
  corrompido pelo mesmo motivo, e o PHP aceitou calado. Duas ocorrências com causa idêntica
  e sintomas completamente diferentes — por isso agora tem entrada própria, para ser
  encontrável na terceira.
- **Lição:** a diferença entre as duas ocorrências foi **quão alto o erro gritou**. No
  E-014 o PHP engoliu o caminho inválido e o sintoma apareceu semanas depois, numa chamada
  HTTPS. Aqui, os testes acusaram na hora. A suíte não evitou o erro — ela o tornou barato.

## E-016 — Barra continuava mostrando "Entrar" depois do login (2026-08-31)

- **Sintoma:** o Ícaro reportou que, após unir as contas, conseguia entrar pelo Google mas
  "não conseguia mais entrar com e-mail e senha". Na segunda mensagem ele mesmo refinou:
  **o login funcionava** — o que não funcionava era a barra superior, que continuava com o
  botão "Entrar" mesmo autenticado.
- **Causa:** `AccountHeader` vive no **layout raiz**. Ele consulta `/api/v1/eu` num
  `useEffect` de montagem, e navegação client-side **não remonta o layout** — então, ao
  entrar, a barra ficava congelada no estado anterior até um recarregamento completo.
  Medido no navegador antes de mexer: depois do login, `token: guardado`, URL `/`, barra
  `"Bora Entrar"`; depois de recarregar, `"Bora Teste Uniao Sair"`. O token sempre esteve
  válido — e o backend também: teste direto na API confirmou 200 e
  `entra_com: ["senha","google"]` após a união. (Este é o payload **como foi medido na
  hora**; no mesmo dia a API passou a responder em inglês — hoje o campo é
  `signs_in_with: ["password","google"]`. Ver `docs/architecture/naming-conventions.md`.)
- **Resolução:** `storeToken` e `forgetToken` passam a emitir o evento `bora:session`;
  o cabeçalho escuta e reconsulta. Escuta também o evento nativo `storage`, então **sair
  numa aba atualiza as outras** — antes, uma aba esquecida seguiria mostrando a pessoa como
  logada.
- **Status:** resolvido; teste de regressão em `us1-account.spec.ts`, verificado nos dois
  sentidos (falha com o bug, passa com a correção).
- **Por que 74 testes e2e não pegaram:** nenhum fazia **login de verdade e depois olhava a
  barra**. Havia teste de que a barra oferece "Entrar" para quem não entrou, e testes de
  que o login guarda o token — mas nada ligava as duas coisas.
- **Efeito colateral que a correção expôs, e vale mais que ela:** ao fazer o cabeçalho
  reagir ao login, três testes começaram a falhar por **premissa contraditória deles
  próprios** — guardavam sessão e ao mesmo tempo mockavam `/eu` como 401, e o cliente então
  descartava o token (comportamento correto do produto). A mesma contradição já tinha
  causado uma falha intermitente antes. Em vez de corrigir caso a caso, o auxiliar
  `withValidSession` foi para `tests/e2e/base.ts`, com a explicação escrita.
- **Lição:** o relato do usuário raramente vem com a causa certa — e não deve vir. Aqui a
  primeira formulação ("não consigo mais entrar com e-mail e senha") apontava para o
  backend, e o backend estava certo. O que resolveu foi **reproduzir e medir** antes de
  mexer: token guardado, API 200, barra desatualizada.

## E-015 — Divergência de hidratação: `localStorage` lido durante a renderização (2026-08-31)

- **Sintoma:** o Ícaro reportou erro no console do navegador — *"A tree hydrated but some
  attributes of the server rendered HTML didn't match the client properties. This won't be
  patched up."*
- **Causa:** `web/src/app/verificar-email/page.tsx` chamava `isAuthenticated()` **dentro do
  JSX**, durante a renderização. A função lê `localStorage`, que não existe no servidor:
  ele renderizava o ramo "não autenticado" e o cliente hidratava com o ramo "autenticado".
  O React acusa a divergência e, como a própria mensagem diz, **desiste de corrigir aquela
  subárvore** — a tela fica com o conteúdo errado, em silêncio.
- **Resolução:** o estado passou a ser lido em `useEffect` e guardado em `useState`, como já
  era feito no `AccountHeader`. Regra que vale para toda tela do projeto: **nada que dependa
  do navegador — `localStorage`, `window`, data/hora — pode ser lido durante a renderização.**
- **Status:** resolvido e confirmado por teste de regressão.
- **Por que passou por 66 e2e, 37 de componente e o build:** o teste de componente roda em
  **jsdom**, que não faz renderização de servidor nem hidratação — a divergência é
  impossível ali. E **não havia nenhum teste e2e que visitasse `/verificar-email`**: a tela
  simplesmente não tinha cobertura de ponta a ponta.
- **O que foi feito para não repetir**, em duas camadas:
  1. `web/tests/e2e/base.ts` — um `test` estendido que **reprova quando o navegador registra
     erro no console**. Pega, de uma vez, divergência de hidratação, violação de CSP, erro
     de JavaScript não tratado e recurso bloqueado. Ignora só ruído de desenvolvimento e as
     respostas 4xx que os próprios testes simulam (filtradas por URL de `/api/v1/`, para um
     403 em chunk de JavaScript continuar reprovando — foi assim que o E-013 se manifestou).
  2. Testes e2e para a tela de confirmação de e-mail, que não existiam.
- **Lição — rede de proteção também se testa.** Ao criar a rede acima, afirmei que ela
  pegaria o bug e **fui verificar**: com o defeito reintroduzido, os testes **passaram**. O
  motivo é que a divergência só ocorre com token no `localStorage`, e nenhum teste semeava
  um. Só depois de semear o token com `addInitScript` o teste passou a falhar. Rede de
  proteção não verificada é rede que dá falsa confiança — pior que não ter.

## E-014 — PHP sem pacote de CA: nenhuma chamada HTTPS funcionava (2026-08-31)

- **Sintoma:** ao autorizar no Google, a tela voltava com "Não deu para entrar com o Google
  agora. Tente de novo ou use seu e-mail e senha." O Ícaro suspeitou de ser o caso de união
  de contas (US3); **não era**.
- **Causa:** o log da aplicação trazia o motivo exato —
  `cURL error 60: SSL certificate problem: unable to get local issuer certificate for
  https://www.googleapis.com/oauth2/v4/token`. O PHP 8.4 do WAMP estava **sem nenhum pacote
  de autoridades certificadoras**: `curl.cainfo` e `openssl.cafile` vazios, e nenhum
  `cacert.pem` em lugar algum da máquina (procurado no WAMP e no Composer). Ou seja,
  **nenhuma** chamada HTTPS saindo do PHP funcionava — a troca do `code` com o Google só foi
  a primeira a esbarrar nisso. O Resend em produção e qualquer API externa futura falhariam
  igual.
- **Resolução (autorizada pelo Ícaro):** baixado o `cacert.pem` oficial do projeto curl
  (<https://curl.se/ca/cacert.pem> — extrato do repositório de raízes da Mozilla; 188 KB,
  121 certificados, versão de 13/08/2026) para
  `C:\wamp64\bin\php\php8.4.15\extras\ssl\cacert.pem`, e `curl.cainfo` + `openssl.cafile`
  apontados para ele em **`php.ini` e `phpForApache.ini`**, com backups `.bak-antes-cacert`
  ao lado (mesmo procedimento da instalação do Redis). **Caminho gravado com barras normais**
  (`C:/wamp64/...`): a primeira tentativa usou barras invertidas e o `sed` as consumiu,
  produzindo um caminho corrompido que o PHP aceitou calado.
  Verificado depois: `googleapis.com` e `api.resend.com` respondem com TLS **verificado**.
  Desativar a verificação foi descartado sem discussão — seria abrir a porta para
  interceptação em vez de consertar.
- **Status:** resolvido; US2 validada pelo Ícaro logo em seguida.
- **Lição 1 — teste com dublê não cobre integração real.** Os 130 testes de backend usam um
  provedor de identidade falso e **nunca fazem chamada HTTPS**. É o desenho certo (teste que
  depende de rede e conta externa é lento e frágil), mas deixa a integração de verdade sem
  nenhuma cobertura. Foi a validação manual que encontrou. Toda feature com integração
  externa tem esse ponto cego — ele precisa ser coberto por validação manual explícita, não
  por confiança nos testes.
- **Lição 2 — o log pagou por si.** O `GoogleController` guarda o motivo técnico e mostra
  mensagem humana na tela. Diagnóstico em um minuto. Se a exceção tivesse vazado para a
  tela, o Ícaro veria lixo técnico e o motivo estaria perdido.
- **Lição 3 — hipótese do usuário também se verifica.** A suspeita era união de contas;
  aquele caminho devolve 409 e leva a `/unir-contas`, não àquela mensagem. Concordar por
  educação teria mandado a investigação para o lado errado.

## E-013 — Servidor de dev do Next devolve 403 fora do localhost: tela viva sem hidratar (2026-08-31)

- **Sintoma:** abrindo pelo IP da rede (celular), as telas carregavam mas **nada
  funcionava**; com o guarda de hidratação do E-012 já no lugar, o botão de enviar ficava
  **permanentemente desabilitado**.
- **Causa:** o servidor de **desenvolvimento** do Next recusa requisições de origem
  diferente de `localhost`. Vários chunks de JavaScript voltavam **403 Forbidden**, então o
  React nunca hidratava. Medido no navegador: `formHidratou: false`, 403 em
  `/_next/static/chunks/...`.
- **Resolução:** `allowedDevOrigins` em `web/next.config.ts`, alimentado por
  `os.networkInterfaces()` — a máquina se autoriza na própria rede, e os IPs são
  descobertos em tempo de execução, **não fixados** (IP muda com DHCP; valor fixo foi a
  armadilha do E-011). Verificado depois: `formHidratou: true`, botão habilitado, zero 403.
  Vale só em desenvolvimento.
- **Status:** resolvido e confirmado pelo Ícaro no celular.
- **Lição:** **validar no aparelho é diferente de validar na máquina.** Três verificações
  minhas passaram — `curl` local, testes de componente e e2e — e todas rodavam em
  `localhost`, onde este bloqueio não existe. O caminho do celular é uma configuração
  distinta e precisa ser exercitada como tal.

## E-012 — Formulário submetia nativamente antes de hidratar, com a senha na URL (2026-08-31)

- **Sintoma:** ao investigar outra falha, o teste e2e registrou a navegação
  `/criar-conta?nome=&email=maria%40exemplo.com&senha=senhaforte1`. Nenhuma requisição à
  API acontecia.
- **Causa:** antes da hidratação o `onSubmit` do React não existe. Um toque no botão fazia
  o **navegador** submeter o formulário do jeito clássico — GET para a mesma página, com
  todos os campos na query string. **A senha ia para a URL**, e daí para o histórico do
  navegador, o log de servidor e o cabeçalho `Referer`. Contradiz frontalmente a regra da
  própria spec ("token e senha nunca em URL").
- **Gravidade real, não teórica:** a janela é curta num computador rápido, mas o público do
  Bora usa **aparelho modesto em rede lenta** (`ux-requirements.md`). Lá a pessoa tocaria,
  perderia o que digitou e vazaria a senha, sem nada na tela indicando problema.
- **Resolução:** em `BaseForm`, o botão de envio só habilita depois de o componente
  montar, e o `<form>` ganhou `method="post"` como defesa em profundidade. O rótulo diz
  **"Carregando…"** enquanto não está pronto — para uma falha de hidratação **admitir** que
  a tela não está pronta, em vez de exibir um botão morto (foi assim que o E-013 passou
  despercebido). Teste de regressão em `us1-account.spec.ts` falha se a senha voltar à URL.
- **Status:** resolvido.
- **Aconteceu mais duas vezes depois (2026-08-31), com sintoma diferente.** No botão
  "Entrar com Google" (US2) e no plano B da união (US3) — que são `type="button"`, sem
  submissão nativa — o toque antes da hidratação era **silenciosamente ignorado**: a pessoa
  aperta e nada acontece, o que o `ux-requirements.md` proíbe. Nas três vezes quem pegou
  foi o **teste e2e**; o de componente nunca pegou, porque o jsdom não tem essa janela.
  Na terceira, o guarda foi extraído para o hook `useHydrated` (`web/src/lib/hydration.ts`),
  com a explicação inteira num lugar só. **Todo controle que dispara ação usa esse hook.**
- **Lição:** formulário controlado por JavaScript tem um estado intermediário — HTML
  pronto, JavaScript não — e nesse estado o navegador faz o que o HTML manda. Vale para
  toda tela com formulário deste projeto, não só para esta.

## E-011 — API presa no loopback: celular abria as telas e nenhuma ação funcionava (2026-08-31)

- **Sintoma:** pelo celular, as telas carregavam normalmente, mas o cadastro respondia
  "Não conseguimos falar com o Bora agora."
- **Causa:** `.vscode/tasks.json` subia a API com `artisan serve --host=127.0.0.1`,
  enquanto o Next escuta em **todas** as interfaces por padrão. Resultado: a porta 3000
  chegava ao celular e a 8000 não. Confirmado por `netstat`: o processo PHP iniciado pela
  task escutava só em `127.0.0.1:8000`. **Não era firewall** (regras de `php.exe` e
  `node.exe` permitem no perfil Public, sem regra de bloqueio) **nem CORS**.
- **Resolução:** a task passa a usar `--host=0.0.0.0`, com comentário explicando. E a causa
  raiz foi eliminada: o front **deriva o endereço da API de onde a página foi aberta**
  (`web/src/lib/api.ts`), e a CSP faz o mesmo a partir do header `Host`
  (`web/src/middleware.ts`) — em vez de um IP fixo em `.env.local`, que quebra quando o
  DHCP muda o endereço. Efeito colateral aceito: enquanto a task roda, a API de
  desenvolvimento fica visível na LAN (o Next já estava assim; a **inconsistência** entre
  os dois é que causava o bug).
- **Status:** resolvido.
- **Lição — erro de verificação, e é o que mais importa aqui:** na véspera dei o ambiente
  como verificado usando `curl` **da própria máquina para o próprio IP**. Esse tráfego não
  atravessa o firewall nem prova alcance externo: testei algo que não testava o caso real.
  Verificação só vale se percorrer o mesmo caminho do usuário.

## E-010 — Parar a task do VS Code / do agente não mata o servidor de dev (2026-08-31)

- **Sintoma:** depois de encerrar as tasks dos servidores, as portas 3000 e 8000
  continuavam ocupadas. Numa depuração anterior isso levou a suspeitar de bug na
  aplicação quando o problema era um servidor velho respondendo.
- **Causa:** `npm run dev` e `php artisan serve` sobem processos filhos. Encerrar a task
  (ou o comando que a iniciou) mata o pai; o filho fica órfão, ainda ouvindo na porta. O
  `reuseExistingServer` do Playwright então **reaproveita o órfão**, que pode estar
  servindo estado antigo.
- **Resolução:** conferir a porta por PID e matar explicitamente:
  `netstat -ano | grep -E ':(3000|8000)\s+.*LISTENING'` e `Stop-Process -Id <PID> -Force`.
  Nesta sessão sobraram dois processos depois de as três tasks terem sido paradas "com
  sucesso".
- **Status:** contornado; não há correção definitiva do lado do projeto.
- **Lição:** "parei o servidor" só é verdade depois de a porta aparecer livre. Antes de
  investigar comportamento estranho no front, **conferir se a porta está servida pelo
  processo que você acha que subiu**.

## E-009 — CSP estática quebrou a hidratação do Next: app parecia certo e não funcionava (2026-08-31)

- **Sintoma:** as telas da spec 001 renderizavam perfeitamente — títulos, campos, rótulos,
  layout —, mas **nada interativo funcionava**: o formulário não enviava, o cabeçalho não
  saía do estado de carregando. Nenhum erro visível para quem olhava a tela.
- **Causa:** a Content-Security-Policy adicionada como header estático em
  `web/next.config.ts` (tarefa T038, mitigação da decisão D2) usava
  `script-src 'self'` sem nonce. O Next injeta **scripts inline** para hidratar a página;
  a CSP os bloqueava, o React nunca hidratava e a aplicação virava HTML morto.
- **Por que quase passou batido:** os 18 testes de componente rodam em **jsdom**, que não
  aplica CSP — todos verdes. O `npm run build` também passava. Só o **teste e2e**, que usa
  navegador real, pegou: o snapshot do Playwright mostrou o `<header>` vazio, sinal de que
  o componente cliente nunca chegou a rodar no navegador.
- **Resolução:** CSP por **nonce**, gerada por requisição em `web/src/middleware.ts` —
  caminho suportado pelo Next, que carimba o nonce nos próprios scripts. O `next.config.ts`
  ficou só com os headers que não dependem da requisição, com comentário explicando por que
  a CSP não pode voltar para lá. **Afrouxar para `'unsafe-inline'` foi descartado:**
  devolveria exatamente o buraco de XSS que a CSP existe para fechar — e é justamente onde
  o token da sessão mora, em `localStorage` (decisão D2).
- **Status:** resolvido e verificado: 14 testes e2e verdes em 360 e 1280.
- **Lição:** **teste de componente em jsdom não prova que a tela funciona no navegador.**
  Cabeçalho de segurança, CSP, service worker e afins só aparecem em navegador de verdade.
  Toda tela desta spec tem e2e obrigatório por causa do `ux-requirements.md`; este erro
  mostra que a exigência não é burocracia — foi ela que evitou entregar um app quebrado.

## E-008 — `composer require` falha com "Permission denied" ao gravar zip temporário (2026-08-30)

- **Sintoma:** `composer require spatie/laravel-permission spatie/laravel-activitylog
  dedoc/scramble` gravou o `composer.lock`, mas abortou no meio do download com
  `The "https://api.github.com/.../zipball/..." file could not be written to
  vendor/composer/tmp-<hash>.zip: Failed to open stream: Permission denied` e
  `Source fallback is disabled`. Resultado: `composer.json` já listava os três pacotes,
  `vendor/` não tinha **nenhum** deles.
- **Causa:** falha **transitória** ao escrever o arquivo temporário — típico de antivírus
  do Windows segurando o `.zip` durante a varredura. Não é permissão de pasta: o
  `composer require laravel/socialite` tinha acabado de gravar em `vendor/` sem problema,
  na mesma sessão e na mesma pasta.
- **Resolução:** repetir. `composer install` (o lock já estava escrito) completou os cinco
  pacotes de primeira, sem nenhuma mudança de permissão.
- **Status:** resolvido.
- **Lição:** falha de escrita no `vendor/composer/tmp-*` **não** se investiga como
  permissão de diretório — repete-se o comando primeiro. Mesmo padrão da instabilidade de
  rede já conhecida neste ambiente: repetir resolve, investigar custa tempo à toa. E,
  quando o `require` aborta no meio, conferir **os dois lados** — `composer.json` pode
  estar atualizado com o `vendor/` vazio, estado que engana quem só olha um.
- **Efeito colateral que valeu corrigir:** o `require` interrompido deixou as restrições
  como `"*"` (`"dedoc/scramble": "*"`). Num projeto que precisa ser recriável do zero isso
  é armadilha — foram fixadas em `^0.13.42`, `^5.1` e `^8.3`, com `composer update --lock`
  para o lock reconhecer.

## E-007 — `php` e `composer` somem dentro de sessão aberta antes da correção do PATH (2026-08-30)

- **Sintoma:** em plena sessão de trabalho, `php -v` e `composer` falhavam com
  `'php' não é reconhecido como um comando interno ou externo`, embora o E-004 já tivesse
  corrigido o PATH da máquina e o `php` funcionar em terminal novo.
- **Causa:** processo herda o ambiente de quando **nasceu**. A sessão do agente foi aberta
  antes da troca do PATH, então continuava com `C:\xampp\php` — diretório que hoje **nem
  tem mais `php.exe`** (verificado: `Test-Path C:\xampp\php\php.exe` → False). Conferido o
  registro na mesma hora: a Machine PATH **está correta**
  (`C:\wamp64\bin\php\php8.4.15`), e o binário existe. Ou seja, **o E-004 está resolvido**;
  o que falhava era só o ambiente velho carregado pelo processo.
- **Resolução:** abrir terminal novo. Quando não dá para reabrir (sessão de agente já em
  andamento), prefixar o PATH na própria invocação:
  `$env:Path = 'C:\wamp64\bin\php\php8.4.15;' + $env:Path`. Verificado: `php -v` → 8.4.15
  e `composer` passa a resolver.
- **Status:** resolvido (contorno documentado; não há o que corrigir no projeto).
- **Lição:** "o PATH foi corrigido" e "o PATH está corrigido **neste processo**" são
  afirmações diferentes. Antes de concluir que a correção falhou, comparar o ambiente do
  processo (`$env:Path`) com o registro
  (`[Environment]::GetEnvironmentVariable("Path","Machine")`). Vale para qualquer variável
  de ambiente mudada com sessões abertas.

## E-006 — ExecutionPolicy `Restricted` bloqueia qualquer `.ps1`: tasks do `web/` e scripts do Spec Kit (2026-08-29, ampliado em 2026-08-30)

- **Sintoma:** as tasks `web: dev` e `web: build` do VS Code falhavam com
  `UnauthorizedAccess` ao chamar `npm`.
- **Causa:** task do tipo `shell` no Windows roda em **PowerShell**, e o `npm` do PATH
  resolve para `npm.ps1`. A ExecutionPolicy desta máquina é **`Restricted` no escopo
  LocalMachine** (verificado por `Get-ExecutionPolicy -List`: todos os outros escopos
  `Undefined`), o que proíbe execução de qualquer `.ps1`.
- **Resolução:** `"command": "npm.cmd"` nas tasks do `web/` — o `.cmd` é batch e não passa
  pela ExecutionPolicy. Mexer na ExecutionPolicy da máquina foi evitado: é configuração de
  segurança global. O comentário no topo de `.vscode/tasks.json` registra o porquê, para
  ninguém "simplificar" de volta para `npm`.
- **Status:** resolvido e em uso — as tasks voltaram a funcionar.
- **AMPLIAÇÃO (2026-08-30): o alcance é maior do que esta entrada dizia.** Ao rodar o
  `/speckit-tasks`, o `.specify/scripts/powershell/setup-tasks.ps1` falhou com o **mesmo**
  `UnauthorizedAccess`. Ou seja, a política não atinge só as tasks do VS Code: atinge
  **qualquer `.ps1`**, incluindo os scripts do próprio Spec Kit. Contorno para esses:
  `powershell.exe -NoProfile -ExecutionPolicy Bypass -File <script>`. Não tinha aparecido
  antes porque a ferramenta PowerShell do agente já invoca com bypass — o problema só
  surge ao chamar `powershell.exe` direto.
- **Procedência (para quem reler):** a correção das tasks foi feita por outro agente, a
  pedido do Ícaro, fora desta sessão; eu **não presenciei a falha original**. O que
  verifiquei: a ExecutionPolicy `Restricted`, o conteúdo atual do `tasks.json`, a
  existência do PHP no caminho absoluto que ele usa e, depois, a falha do script do Spec
  Kit. O sintoma inicial vem do comentário deixado no arquivo, não de observação minha.
- **Lição:** no Windows com ExecutionPolicy restrita, **todo ponto de entrada `.ps1` é
  suspeito** — tasks, scripts de ferramenta, hooks. Preferir `.cmd` onde existir e
  `-ExecutionPolicy Bypass` onde não existir. E ponteiro para o error-log (`Ver E-NNN`) só
  se escreve **depois** que a entrada existe — este comentário nasceu apontando para
  `E-005`, número que já pertencia a outro erro (colisão corrigida em 2026-08-30).

## E-005 — Doc do método apontava comandos que não existem: `/specify`, `/plan`, `/tasks` (2026-08-29)

- **Sintoma:** `CLAUDE.md` e `docs/development-workflow.md` instruíam a rodar `/specify`,
  `/plan` e `/tasks`, mas nenhum desses comandos existe nesta instalação.
- **Causa:** o Spec Kit 0.15.1 instala as skills com o prefixo `speckit-`
  (`.claude/skills/speckit-specify/`, `speckit-plan/`, `speckit-tasks/`...) e **não existe**
  `.claude/commands/` no repositório — verificado por listagem antes da correção. A doc do
  método foi escrita com os nomes genéricos do fluxo (espelhando o Nexa), não com os nomes
  reais das skills instaladas. Mesma família do E-002: doc descrevendo comportamento sem
  conferir o arquivo que o executa.
- **Resolução:** `CLAUDE.md` (linha do fluxo) e `development-workflow.md` (§1 e diagrama do
  §3) corrigidos para `/speckit-specify`, `/speckit-plan` e `/speckit-tasks`. `/spec-check`
  e `/doc-sync` ficaram como estavam — as skills têm exatamente esses nomes.
- **Status:** resolvido.
- **Lição:** nome de comando em doc é afirmação sobre comportamento — conferir
  `.claude/skills/` (e `.claude/commands/`, se houver) antes de escrever, e reconferir
  quando o tooling for atualizado (o prefixo veio do instalador do Spec Kit).

## E-004 — `artisan install:api` revertia a instalação do Sanctum: PHP errado no PATH (2026-08-29)

- **Sintoma:** `php artisan install:api` publicava `routes/api.php` e rodava a migration,
  mas a instalação do pacote falhava com
  `laravel/framework v13.29.0 requires php ^8.3 -> your php version (8.2.4) does not satisfy
  that requirement` e terminava em
  `Installation failed, reverting ./composer.json and ./composer.lock`. O Sanctum não
  aparecia em `vendor/`.
- **Causa:** o `findComposer()` do Laravel (lido em
  `vendor/laravel/framework/src/Illuminate/Support/Composer.php`) devolve `[composer]`
  quando não há `composer.phar` no projeto, e o `composer.bat` do Windows é literalmente
  `php "%~dp0composer.phar" %*` — ou seja, usa o `php` do **PATH**. E o PATH da máquina
  tinha só `C:\xampp\php` (PHP 8.2.4); o PHP 8.4.15 do WAMP **não estava no PATH**.
  Verificado por `which -a php` (retornava apenas `/c/xampp/php/php`) e por
  `[Environment]::GetEnvironmentVariable("Path","Machine")`.
- **Resolução:** o Ícaro trocou a entrada do PATH da máquina de `C:\xampp\php` para
  `C:\wamp64\bin\php\php8.4.15`. Verificado em terminal com o PATH novo: `php -v` →
  8.4.15; `composer --version` → Composer 2.10.3 sobre PHP 8.4.15;
  `composer check-platform-reqs` → `php 8.4.15 success`; `composer require --dry-run
  laravel/sanctum` roda limpo. **Desinstalar o XAMPP foi avaliado e descartado:** ele era o
  único `php` do PATH, então removê-lo deixaria o composer sem PHP nenhum — e
  `C:\xampp\htdocs` guarda ~20 projetos antigos (incluindo `rolezeiros-api`, embrião do
  Bora) e bases em `C:\xampp\mysql\data`. O XAMPP segue instalado, só fora do PATH.
- **Status:** resolvido e verificado.
- **Lição:** a mesma do E-003, em outra roupa — **conferir qual binário o PATH está
  entregando antes de culpar a ferramenta**. O `install:api` não estava quebrado; ele
  chamava um `composer` que rodava sobre o PHP errado. Em máquina com dois stacks (WAMP e
  XAMPP), `which -a` antes de depurar. Efeito colateral aceito: `php` global passa a ser
  8.4.15 para todos os projetos da máquina; os antigos do `htdocs` não serão mais mexidos
  (decisão do Ícaro, 2026-08-29) e o WAMP tem `php8.2.29` se algum precisar.

## E-003 — Migrações falhavam: MySQL do WAMP com MyISAM como engine padrão (2026-08-29)

- **Sintoma:** `php artisan migrate` quebrava na primeira migration com
  `SQLSTATE[42000]: 1071 Specified key was too long; max key length is 1000 bytes` ao criar
  o índice único de `users.email`.
- **Causa:** o MySQL 8.4.7 do WAMP está configurado com `default_storage_engine = MyISAM`,
  que limita índice a 1000 bytes — e `varchar(255)` em `utf8mb4` ocupa 1020. Verificado por
  `SHOW VARIABLES`. Primeira hipótese (conexão indo para o MariaDB) foi **descartada**: a
  porta 3306 é o MySQL 8.4.7; o MariaDB 11.4.9 está na 3307 e não é usado.
- **Resolução:** `'engine' => 'InnoDB'` na conexão `mysql` de `api/config/database.php`,
  com comentário explicando o porquê. Corrigido **no projeto, não no servidor** — mexer no
  `my.ini` do WAMP afetaria o `nexa-api-v2`, que roda na mesma máquina. Confirmado por
  `information_schema`: as 9 tabelas nasceram InnoDB.
- **Status:** resolvido e verificado.
- **Lição:** MyISAM não tem transação nem chave estrangeira — Laravel não funciona direito
  nele. Em máquina nova, conferir `default_storage_engine` antes de culpar a migration. E
  conferir **em qual porta está cada servidor**: WAMP sobe MySQL e MariaDB ao mesmo tempo.

## E-002 — O portão `/spec-check` não cobrava tela, UX nem acessibilidade (2026-08-29)

- **Sintoma:** o `development-workflow.md` afirmava que o portão reprova "tela que não
  referencie `ux-requirements.md`". Ao conferir a skill, essa checagem não existia — e nem
  qualquer outra sobre tela, mobile ou acessibilidade. Descoberto porque o Ícaro
  desconfiou da afirmação de que a nova regra de mobile-first "já estaria valendo".
- **Causa:** a documentação do método descrevia um comportamento pretendido como se fosse
  implementado. Além disso, o `spec-template.md` era o padrão de fábrica do Spec Kit, sem
  nenhuma seção de tela — e com o exemplo de premissa "Mobile support is out of scope for
  v1", diretamente contrário aos Princípios XI e XII. A cadeia inteira que deveria fazer
  valer esses princípios estava quebrada nos três elos.
- **Resolução:** (1) `spec-template.md` ganhou a seção obrigatória "Tela e Experiência"
  (telas e ação principal, comportamento a 360px, polegar, estados obrigatórios,
  acessibilidade, testes em 360 e 1280) e o exemplo de premissa venenoso foi substituído;
  (2) a skill `spec-check` passou a ler `ux-requirements.md` e a tratar cada um desses itens
  como **Bloqueante**, com instrução explícita de não rebaixá-los a Aviso; (3) o
  `development-workflow.md` passou a descrever o portão que existe de fato.
- **Status:** resolvido. A verificar na primeira spec real (001 — contas): o portão precisa
  reprovar de verdade uma spec sem tela. **Atualização 2026-08-29 (spec 001):** o portão
  foi exercitado pela primeira vez. Registro honesto: ele **não** chegou a reprovar uma
  spec sem tela, porque a spec 001 já foi escrita com a seção "Tela e Experiência"
  completa; o que a varredura ativa do passo 5 pegou de verdade foi **um cenário de falha
  previsível não coberto** (e-mail da conta Google mudado desde o vínculo), corrigido
  antes do veredito. O caminho "reprovar spec sem tela" segue não exercitado.
- **Lição:** princípio que não está escrito na skill que o cobra **não está valendo** — está
  só valendo enquanto alguém lembrar. Ao afirmar que uma regra é obrigatória, abrir o
  arquivo que a executa e conferir, em vez de confiar na doc que a descreve.

## E-001 — Issues do Bora nasceram com prefixo `NEX` (2026-08-29)

- **Sintoma:** todas as 31 issues do projeto Bora no Linear vinham identificadas como
  `NEX-9`..`NEX-39`. Olhando a tarefa não dava para saber de qual produto ela era.
- **Causa:** a importação de 2026-08-28 seguiu a decisão de colocar o projeto Bora dentro
  do time `Nexa`. No Linear o prefixo do identificador é atributo do **time**, não do
  projeto — projeto não tem prefixo. Logo, qualquer issue criada naquele time sairia `NEX`,
  independentemente do projeto.
- **Resolução:** criado o time próprio `Bora` (key `BORA`) e trocado o time do projeto.
  O Linear migrou as 31 issues automaticamente, preservando projeto, marcos, prioridades,
  status e a label `decisao-pendente`; renumerou em ordem inversa (`BORA-n` = `NEX-(40−n)`)
  e mantém redirect dos IDs antigos. Docs sincronizadas no mesmo commit.
- **Status:** resolvido. Efeito colateral aceito pelo Ícaro: a numeração não segue a ordem
  de prioridade (BORA-1 é a decisão mais distante, BORA-31 é o setup) — não há como
  renumerar no Linear sem recriar as issues e perder o histórico.
- **Lição:** no Linear, um produto que precisa de identificador próprio precisa de **time**
  próprio. Decidir isso antes de importar issues, não depois.
