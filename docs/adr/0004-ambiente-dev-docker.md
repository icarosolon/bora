# ADR-0004 — Ambiente de desenvolvimento em Docker, com MySQL e Redis compartilhados

Status: proposto
Deciders: Ícaro · 2026-10-16

## Contexto

Até aqui o ambiente de desenvolvimento rodava direto no Windows: PHP 8.4.15 do WAMP,
MySQL 8.4.7 do WAMP e um Redis 8.2.5 instalado à parte, mais o Node 24.19.0 do host. Esse
arranjo já custou vários registros do `docs/logs/error-log.md`:

- **E-003** — o MySQL do WAMP tem `default_storage_engine=MyISAM` e `sql_mode=""`; as
  migrações quebravam, e `config/database.php` precisou forçar InnoDB.
- **E-004 e E-007** — o `php` do PATH era o do XAMPP (8.2.4), não o do WAMP.
- **E-010** — parar a task do VS Code não matava o servidor de desenvolvimento.
- **E-014** — o PHP do WAMP veio sem pacote de CA e nenhuma chamada HTTPS funcionava.
- A extensão `phpredis` foi instalada à mão (DLL 6.3.0) e o `backlog.md` registra o risco
  de ela deixar de casar se o WAMP atualizar o PHP.

A máquina tem outros projetos (Nexa, raspamoney) que também usam MySQL. A constituição
exige PHP 8.4+, MySQL 8.0+ e Redis 7.0+; a hospedagem de produção está **PENDENTE**.

## Decisão

Duas stacks Docker Compose ligadas pela rede externa `dev-net`:

1. **`infra`** (`C:\docker\infra`, **fora de qualquer repositório**): `mysql:8.4.7` e
   `redis:7.4.11`, compartilhados por todos os projetos. Portas só em `127.0.0.1`
   (3308 e 6380), volumes nomeados, senhas em `.env` da própria stack, um usuário MySQL por
   projeto com privilégio só nos bancos dele (`bora`, `bora_test`), InnoDB e `utf8mb4` por
   padrão, `sql_mode` estrito (o padrão do MySQL 8.4), Redis com senha.
2. **Bora** (`compose.yaml` na raiz deste repositório): `api` (PHP 8.4.15), `queue`
   (`queue:work`, mesma imagem) e `web` (Node 24.19.0), com o código em bind mount e
   `vendor/` e `node_modules/` em volumes nomeados. `api` e `web` publicam 8000 e 3000 em
   todas as interfaces, porque a validação visual é feita no celular (E-011, E-013).

Isolamento de Redis: `REDIS_PREFIX=bora_`, `REDIS_DB=2` e `REDIS_CACHE_DB=3`, deixando os
bancos 0 e 1 (padrões do Laravel) para os outros projetos.

O `web` roda com `next dev --webpack` e `WATCHPACK_POLLING=true`. Ver "Fatos medidos".

## Opções consideradas

**A. Manter o WAMP.**
Prós: nada muda; desempenho nativo de arquivo. Contras: mantém a fonte dos registros
acima; o phpredis e o `cacert` continuam dependendo de arquivos soltos dentro do WAMP; o
MySQL compartilhado com outros projetos segue com MyISAM e `sql_mode` vazio; o ambiente
não é reproduzível em outra máquina.

**B. Docker com o código do Windows em bind mount (escolhida).**
Prós: ambiente descrito em arquivo e reproduzível; InnoDB e modo estrito por padrão; CA e
extensões vêm na imagem; `docker compose stop` para de verdade; reiniciar a fila é um
comando. Contras: escrita em lote no bind mount é lenta (ver abaixo); o hot reload do
Next exige trocar de bundler; o `vendor/` visto pelo host deixa de ser o usado pelo
container; passa a depender do Docker Desktop.

**C. Código dentro do sistema de arquivos do WSL.**
Prós: elimina a lentidão do bind mount e provavelmente o problema do hot reload. Contras:
**não foi testada**. Muda onde o repositório mora, o que afeta Git do Windows, VS Code,
Playwright no host e os caminhos absolutos de `.mcp.json` e `.codex/config.toml`.

## Fatos medidos (2026-10-16, mesma máquina)

Bind mount do Windows para o container, contra o disco local do container:

| Medida | Resultado |
|---|---|
| Criar e apagar 1000 arquivos pequenos, no bind | 7,0 s (contra 0,12 s em `/tmp`) |
| Ler todos os `.php` de `app/` (bind) | 0,35 s |
| `php artisan route:list` | 0,55 s |
| Requisições à API (10×) | 0,07 a 0,21 s |
| Requisições ao Next, página já compilada | 0,05 a 0,07 s |
| `next build` | 17 s no container; 36 s no host |
| `php artisan test` | 189 testes passando, 589 asserções, 32 s |
| Vitest no container | 43 testes passando, 6 s |

Hot reload do Next 16 com a edição feita no Windows:

| Configuração | Resultado |
|---|---|
| Turbopack (padrão), sem polling | a edição **não** apareceu em 69 s |
| Turbopack, com `WATCHPACK_POLLING` | a edição **não** apareceu em 69 s |
| `--webpack` com `WATCHPACK_POLLING` | a edição apareceu em 2,6 s |

Outros fatos verificados: o `php artisan serve` descarta as variáveis de ambiente do
container no processo filho (a API caía nos valores do `.env` e dava 500); por isso a `api`
usa o servidor embutido do PHP (`php -S`), que o `serve` usa por baixo e que herda o
ambiente. O isolamento de `bora_test` foi provado com uma tabela sentinela em `bora`, que
sobreviveu à suíte. O worker consumiu e executou um job colocado no Redis do container.

## Opinião do assistente (não medida)

- O webpack foi a única configuração que testei com hot reload funcionando; não sei se o
  Turbopack passaria a funcionar em outra configuração de mount.
- Rodar `web` com bundler diferente do `npm run dev` do host é um risco de comportamento
  (hidratação, CSP): as telas das specs 001 e 002 foram validadas com o Turbopack.
- A lentidão de escrita em lote (58×) só aparece em operações em massa; no uso normal
  não senti diferença nas requisições medidas.

## Consequências

Fica mais fácil: subir o ambiente em outra máquina; manter MySQL e Redis em versões
fixas e iguais para todos os projetos; reiniciar o worker; parar os servidores de verdade.

Fica mais difícil: o `vendor/` do host (usado pelo IDE) e o do container divergem;
escrita em lote no bind mount; depender de Docker Desktop e do WSL; `.mcp.json` e
`.codex/config.toml` ainda apontam para o PHP do WAMP e **quebram se o WAMP for
desligado**.

**A confirmar:** comportamento da stack depois de reiniciar o Windows e o Docker Desktop;
paridade com a hospedagem (PENDENTE) — o Redis de desenvolvimento passa de 8.2.5 para
7.4.11, ambos dentro do mínimo 7.0 da constituição; se o Firewall do Windows deixa o
celular alcançar as portas 8000 e 3000 publicadas pelo Docker.

## Action items

- [ ] Ícaro valida as telas no celular com o `web` em webpack (E-009, E-012, E-013).
- [ ] Decidir `.mcp.json` e `.codex/config.toml` (hoje usam o PHP do WAMP).
- [ ] Migrar os dados de desenvolvimento do MySQL do WAMP para o `bora` da stack infra
  (hoje o banco está vazio).
- [ ] Rodar o Playwright contra a stack depois de confirmar que os specs podem criar
  dados no banco de desenvolvimento.
- [ ] Decidir, mais adiante e à parte, o desligamento do WAMP.
- [ ] Rever esta decisão se o hot reload ou a escrita em lote incomodarem no dia a dia
  (alternativa C).
