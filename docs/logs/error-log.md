# Error Log

Registro de erros no formato `E-NNN` (sintoma, causa, resolução, status), mantido pela
skill `doc-sync`.

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
  reprovar de verdade uma spec sem tela.
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
