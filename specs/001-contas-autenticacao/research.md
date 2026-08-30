# Phase 0 — Pesquisa e decisões técnicas: Fundação de Contas e Autenticação

**Spec**: [spec.md](./spec.md) · **Data**: 2026-08-30

Todas as afirmações abaixo marcadas como **Verificado** foram obtidas abrindo o arquivo ou
rodando o comando indicado nesta sessão — não de memória (regra do `CLAUDE.md`). O que é
opinião está marcado como **Julgamento**.

---

## 0. Estado real da instalação (verificado antes de decidir)

| Item | Estado verificado | Como |
|---|---|---|
| PHP / Laravel / MySQL | 8.4.15 · 13.29.0 · MySQL | `application-info` (Boost) |
| Sanctum | **4.3.3 instalado**; `config/sanctum.php` publicado, `'expiration' => null` | `application-info`, leitura do arquivo |
| Test runner do `api/` | **PHPUnit 12.5.34** (não Pest); só `tests/Feature/ExampleTest.php` e `tests/Unit/ExampleTest.php` | `composer.json`, listagem |
| `laravel/socialite` | **NÃO instalado** — e não instalável sem downgrade do guzzle (ver §2) | `composer require --dry-run` |
| `spatie/laravel-permission` | **NÃO instalado**; resolve limpo em **8.3.0** | `composer require --dry-run` |
| `spatie/laravel-activitylog` | **NÃO instalado**; resolve limpo em **5.1.0** | `composer require --dry-run` |
| `dedoc/scramble` (D3) | **NÃO instalado**; resolve limpo em **v0.13.42** | `composer require --dry-run` |
| `config/cors.php` | **NÃO existe** (não publicado) — vale o default do framework: `paths => ['api/*']`, `allowed_origins => ['*']`, `supports_credentials => false` | listagem de `api/config/`; confirma o achado do spike BORA-32 |
| `bootstrap/app.php` | `withMiddleware` **vazio**; exceções já renderizam JSON para `api/*` | leitura do arquivo |
| Modelo `User` | **não usa `HasApiTokens`**; não implementa `MustVerifyEmail`; usa a sintaxe nova do Laravel 13 — atributos `#[Fillable([...])]` e `#[Hidden([...])]`, não propriedades | leitura do arquivo |
| Schema real | `users` (padrão), `password_reset_tokens`, `personal_access_tokens` (**com `expires_at` e `last_used_at`**), `sessions`, `jobs`, `cache` | `database-schema` (Boost) |
| Vínculo social / papéis / auditoria | **nenhuma tabela existe** | `database-schema` |
| `routes/api.php` | rota `/user` está em **`/api/user`**, fora do prefixo `v1` — inconsistente com o versionamento path-based do Princípio IV | leitura do arquivo |
| `config/services.php` | **já traz a chave `resend`** (vem do Laravel) — D8 encaixa sem estrutura nova | leitura do arquivo |
| `config/mail.php` | default `log` — captura local em dev, como a spec previu | leitura do arquivo |
| `web/` | Next **16.3.3**, React **19.2.8**, Tailwind **4**, TS 5. **Zero ferramenta de teste** (sem Vitest, Testing Library, Playwright, axe) e **sem shadcn/ui ou Radix**. Só `src/app/` com o andaime do spike | `package.json`, listagem de `web/src/` |

### Achado de ambiente (não é do projeto, mas custa tempo na próxima sessão)

**Verificado:** o PATH da máquina **está correto** no registro
(`C:\wamp64\bin\php\php8.4.15`), como o E-004 registrou. Mas o **processo do agente**
herdou o ambiente antigo: seu PATH ainda aponta para `C:\xampp\php`, diretório que **não
tem mais `php.exe`**. Consequência: `php` e `composer` falham dentro de uma sessão que já
estava aberta quando o PATH mudou.

Contorno usado aqui (funciona sem reabrir nada):

```bash
$env:Path = 'C:\wamp64\bin\php\php8.4.15;' + $env:Path
```

**Julgamento:** terminal novo resolve definitivamente; o contorno acima é para quando não
dá para reabrir.

---

## 1. Expiração de sessão: Sanctum **não** tem expiração deslizante nativa

**Decisão**: implementar o deslizamento com middleware próprio, guardando o prazo em
`personal_access_tokens.expires_at` e usando `last_used_at`, que o Sanctum já mantém.

**Verificado (doc do Sanctum 4.3.3 / Laravel 13.x)**: `'expiration'` é "the number of
minutes until an issued token will be considered expired" — contado **da criação**, não do
último uso; por token, o prazo vai como 3º argumento de `createToken`. Não existe opção de
janela deslizante. A tabela `personal_access_tokens` **já tem** `expires_at` e
`last_used_at` (verificado no schema).

**Por quê**: a D7 pede "30 dias **de inatividade**, renovada a cada uso". Com o
`'expiration'` global, quem usa o app todo dia seria deslogado no 30º dia mesmo assim — o
que contraria a decisão. Deixar `'expiration' => null` e controlar por token dá o
comportamento pedido e mantém o prazo como **parâmetro configurável** (exigência da spec).

**Alternativas consideradas**:
- `'expiration' => 43200` global: mais simples, mas é prazo absoluto — **não** é o que a D7
  decidiu.
- Refresh tokens: o Sanctum não tem esse conceito; exigiria construir do zero. Custo alto
  para um ganho que a D7 não pediu.

**Consequência operacional**: agendar `sanctum:prune-expired --hours=24` (comando existe no
Sanctum, verificado na doc) para não acumular token morto.

---

## 2. `laravel/socialite` exige **downgrade do guzzle 8 → 7**

**Decisão**: instalar `laravel/socialite` v5.30.1 aceitando o downgrade de
`guzzlehttp/guzzle` 8.1.0 → 7.15.5.

**Verificado**: `composer require --dry-run laravel/socialite` **falha** — todas as versões
do Socialite até a v5.30.1 exigem `guzzlehttp/guzzle ^6.0|^7.0`, e o projeto está com
guzzle **8.1.0** (veio como dependência transitiva do Laravel 13). Com `-W` a resolução
**funciona**, rebaixando guzzle 8.1.0 → 7.15.5, promises 3.0.2 → 2.5.3 e psr7 3.1.0 →
2.13.1.

**Verificado que o downgrade é seguro** (`composer why guzzlehttp/guzzle`): **nada no
projeto exige guzzle 8**.

- `laravel/framework` 13.29.0 requer `^7.8.2 || ^8.0`
- `laravel/boost` v2.7.0 requer `^7.9|^8.0`
- `league/flysystem` 3.35.3 apenas **conflita com < 7.0**

Ou seja, guzzle 7.15.5 satisfaz todos os consumidores atuais.

**Bônus de segurança verificado**: a resolução conjunta acusou que `firebase/php-jwt`
v6.4.0–v6.11.1 estão sob advisory (`PKSA-y2cr-5h3j-g3ys`); com `-W` o composer trava a
**v7.1.0**, não afetada. Instalar sem `-W` cairia na faixa vulnerável.

**Por que não a alternativa**: implementar o OAuth do Google na mão (via `Http` do Laravel)
manteria guzzle 8, mas a **constituição obriga** `laravel/socialite` no Stack Tecnológico —
trocar exigiria emenda. E escrever fluxo OAuth artesanal é mais superfície de erro de
segurança que ganho.

**Julgamento (não medido)**: o risco de ficar em guzzle 7 é baixo e reversível — quando o
Socialite suportar guzzle 8, um `composer update` reverte. Registrado como item a revisitar.

**Comando que o plano prevê** (o `-W` é obrigatório aqui):

```bash
composer require laravel/socialite -W
```

---

## 3. Autenticação do `web/`: Bearer + `localStorage` (D2 mantida, com ressalva registrada)

**Decisão (Ícaro, 2026-08-30)**: mantém a D2 — token Bearer, guardado em `localStorage`,
cliente chamando a API do Laravel diretamente.

**Ressalva que precisa ficar escrita** — *verificado na doc do Sanctum instalado*: a
documentação oficial **desaconselha** este caminho, textualmente: tokens de API não devem
autenticar a SPA de primeira parte; o recomendado é o modo SPA (cookie de sessão). O motivo
é que cookie `httpOnly` não é legível por JavaScript, e token em `localStorage` é.

**Por que o projeto seguiu contra a recomendação, mesmo assim** (razões do Bora, não da
doc):

1. **Princípio IV** exige que o site consuma a API com a **mesma autenticação** que
   qualquer outro cliente — e o modo SPA (cookie stateful) **não serve para o app mobile**.
   Adotá-lo significaria dois mecanismos de autenticação para a mesma API.
2. O **ADR-0003 já ratificou** "área autenticada renderizada no cliente, chamando a API com
   token", justamente para não combinar SSR com sessão do Sanctum.
3. O modo SPA exige site e API sob o **mesmo domínio-raiz** em produção — restrição de
   hospedagem que ainda está em aberto (BORA-27).

**Risco aceito, explicitamente**: um XSS bem-sucedido no `web/` rouba a sessão da pessoa.

**Mitigações que entram no plano** (não são opcionais):

- **CSP estrita** no `web/`, sem `unsafe-inline` para script.
- Expiração de 30 dias (§1) + revogação no "Sair" e na troca de senha (FR-015).
- **Nenhum token cruzando para prop de componente cliente** — o spike BORA-32 verificou que
  props que atravessam a fronteira servidor→cliente **são serializadas no HTML**. Token só
  é lido no navegador, nunca passado do servidor do Next.
- Nada de `dangerouslySetInnerHTML` nas telas desta feature; saída escapada por padrão.
- Token **nunca** em URL, query string ou log.

**Alternativas consideradas e descartadas por Ícaro**: cookie `httpOnly` gravado por Route
Handler do Next (fecharia o XSS, mas transforma o Next em proxy da área logada, o que
tensiona o Princípio IV — "proibido endpoint privado só do site"); voltar ao modo SPA do
Sanctum (exigiria emenda ao ADR-0003).

**A revisitar**: se a área logada crescer em superfície (upload, conteúdo de terceiros
renderizado), o cálculo de risco de XSS muda e a opção do cookie `httpOnly` volta à mesa.

---

## 4. CORS: publicar `config/cors.php` com origens explícitas

**Decisão**: publicar o arquivo e listar origens explicitamente; `supports_credentials`
permanece **`false`**.

**Verificado**: o arquivo não existe hoje; o default do framework é `allowed_origins:
['*']`. A doc do Sanctum confirma que a publicação se faz com
`php artisan config:publish cors`.

**Por quê**: com Bearer não há cookie atravessando, então `supports_credentials` continua
`false` — e some o conflito que o spike registrou (`'*'` não convive com credenciais).
Ainda assim, `'*'` deixa qualquer origem chamar a API autenticada por token; origem
explícita é o mínimo do Princípio V.

**Julgamento**: manter `'*'` não seria uma falha de segurança grave (o token no header é
que autoriza, e CORS não protege servidor-a-servidor), mas é higiene barata e evita que o
default vire permanente por esquecimento.

---

## 5. Papéis: `spatie/laravel-permission` desde já, com **um** papel

**Decisão**: instalar o pacote (8.3.0, resolve limpo) e criar apenas o papel `rolezeiro`
nesta feature.

**Por quê**: a constituição obriga o pacote para os papéis do Princípio I, e o modelo de
conta precisa **suportar acúmulo de papéis** desde já (FR-002). Instalar depois exigiria
migrar contas existentes.

**Julgamento**: instalar o pacote para um único papel parece exagero agora; é deliberado —
o custo de adotá-lo na feature de cadastro de local, com contas em produção, é maior.

---

## 6. Auditoria: `spatie/laravel-activitylog`

**Decisão**: instalar (5.1.0, resolve limpo) e registrar as escritas da FR-017.

**Verificado**: exigido pela constituição (Princípio VIII) e por `RN-PLAT-004`; nenhuma
tabela de log existe hoje.

**Atenção de segurança (Princípio V)**: o log de auditoria **não pode** guardar senha, hash
de senha nem token. Para a união de credenciais e troca de senha, registrar o **evento**,
não os valores. Isso vira teste (a spec já prevê "senha e tokens nunca aparecem em log").

---

## 7. Documentação da API: Scramble (D3)

**Decisão**: instalar `dedoc/scramble` v0.13.42 (resolve limpo) e expor a doc gerada.

**Por quê**: a D3 escolheu geração automática a partir de FormRequests e Resources — casa
com a regra "documentação no mesmo commit", porque a doc não pode ficar velha sem que o
código mude.

**Ponto de atenção**: Scramble infere o contrato do código. Onde a inferência não bastar
(ex.: respostas de erro 422 e 429), a spec exige que o contrato esteja documentado — usar
as anotações do próprio pacote nesses pontos. Os contratos em [contracts/](./contracts/)
são a fonte de verdade da revisão; o Scramble é o que publica.

---

## 8. Base de UI e testes de front (D4)

**Decisão**: shadcn/ui (primitivas Radix + Tailwind, código copiado para o repo);
**Vitest + React Testing Library + `jest-axe`/`axe-core`** para componente; **Playwright**
para e2e em 360 e 1280.

**Verificado**: o `web/` hoje **não tem nenhuma** dessas dependências — este é o setup
inicial, não um ajuste. Tailwind 4 já está instalado, que é o que o shadcn/ui pede.

**Julgamento (não medido)**: Vitest é o runner de menor atrito com o toolchain do Next 16 +
TS já instalado. Não medi alternativas (Jest); se der atrito, trocar é barato porque os
testes usam a API do Testing Library, não a do runner.

**Ponto de atenção verificado no spike**: componente sem `"use client"` roda só no
servidor. Os formulários desta feature são interativos, logo **são componentes cliente** —
e a fronteira precisa ficar explícita para o token nunca atravessá-la (§3).

---

## 9. Versionamento da API: corrigir `/api/user`

**Decisão**: mover a rota `/user` para dentro de `v1` e entregar tudo desta feature sob
`/api/v1/...`.

**Verificado**: hoje a rota está em `/api/user`, fora do prefixo — o Princípio IV manda
versionamento path-based `/api/v1/...`.

**Julgamento**: é dívida de nascença do `install:api`, não do spike; corrigir agora custa
uma linha e evita que o app mobile encontre dois padrões.

---

## Itens que este plano **não** decide (seguem em aberto)

- **Hospedagem** (BORA-27) — continua PENDENTE; afeta domínio, HTTPS e a origem exata do
  CORS em produção.
- **Domínio próprio + SPF/DKIM** — bloqueia o envio real de e-mail em produção (Resend), não
  o desenvolvimento. Já registrado no backlog junto ao item de marca/INPI.
- **Emenda constitucional** formalizando o Resend no Stack — item de governança no backlog.
- **Credenciais OAuth do Google** (client ID/secret no Google Cloud Console) — precisam ser
  criadas pelo Ícaro; sem elas a US2 não roda nem em desenvolvimento.
