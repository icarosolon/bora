# Error Log

Registro de erros no formato `E-NNN` (sintoma, causa, resolução, status), mantido pela
skill `doc-sync`.

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
