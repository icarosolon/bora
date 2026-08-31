# Phase 1 — Guia de validação: Fundação de Contas e Autenticação

**Spec**: [spec.md](./spec.md) · **Contrato**: [contracts/auth-api.md](./contracts/auth-api.md)
· **Data**: 2026-08-30

Como provar que a feature funciona ponta a ponta. Este é um guia de **execução e
validação** — o passo a passo de implementação sai no `/speckit-tasks`.

---

## Pré-requisitos

**Já prontos** (verificados em 2026-08-30):

- PHP 8.4.15 (WAMP), MySQL 8.4.7 na 3306 com a base `bora`, Redis 8.2.5 na 6379.
- `api/` com Laravel 13.29.0 e Sanctum 4.3.3; `web/` com Next 16.3.3 e Node 24.19.0.

**Armadilha de ambiente** — se `php` ou `composer` derem "não é reconhecido", o terminal
herdou o PATH antigo (E-004 corrigiu o PATH da máquina, mas processos abertos antes disso
ficaram com o valor velho). Abrir terminal novo resolve; sem isso:

```bash
$env:Path = 'C:\wamp64\bin\php\php8.4.15;' + $env:Path
```

**Pacote de CA no PHP (E-014)** — sem ele, **nenhuma** chamada HTTPS do PHP funciona: nem o
Google, nem o Resend. Conferir antes de investigar qualquer falha de integração externa:

```bash
php -r 'echo ini_get("curl.cainfo") ?: "VAZIO — ver E-014"; echo PHP_EOL;'
```

Sintoma quando falta: `cURL error 60: SSL certificate problem`. Se o WAMP atualizar o PHP,
a configuração se perde e o sintoma volta.

**Google OAuth — feito em 2026-08-30** (Ícaro). Projeto `bora-507117`, cliente OAuth 2.0
do tipo Aplicativo da Web, app **Externo**. `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e
`GOOGLE_REDIRECT_URI` estão em `api/.env` e **verificados** (o Laravel lê os três). No
console: origem `http://localhost:3000`, redirecionamento
`http://localhost:3000/entrar/google/retorno`.

Faltam duas confirmações antes de a US2 rodar:

1. O "Salvar" do console aplicado — ele mesmo avisa que leva de 5 min a algumas horas.
   Sintoma se não aplicou: `redirect_uri_mismatch`.
2. O Gmail do Ícaro em **Público-alvo → Usuários de teste** (app Externo nasce em modo
   Teste). Sintoma se faltar: `access_denied`.

Este cliente é **de desenvolvimento e não vai a produção** — quando a hospedagem for
definida (BORA-27), cria-se outro, com secret próprio. As demais user stories (US1, US3
por link, US4) rodam sem o Google.

---

## Instalação das dependências novas

```bash
composer require laravel/socialite -W
```

> O `-W` **é obrigatório**: o Socialite exige guzzle ^7 e o projeto está em guzzle 8. Sem
> `-W` a instalação falha; com ele, o guzzle é rebaixado para 7.15.5 — verificado como
> seguro para todos os consumidores ([research.md](./research.md) §2). O `-W` também evita
> a faixa de `firebase/php-jwt` sob advisory de segurança.

```bash
composer require spatie/laravel-permission spatie/laravel-activitylog dedoc/scramble
```

```bash
php artisan config:publish cors
```

No `web/`, a base de UI e a suíte de testes (D4) — hoje o `web/` **não tem nenhuma**
dependência de teste:

```bash
npm install -D vitest @vitejs/plugin-react jsdom @testing-library/react @testing-library/jest-dom @testing-library/user-event jest-axe @playwright/test
```

---

## Migrações

```bash
php artisan migrate
```

Cria `contas_sociais`, `tokens_de_email`, as tabelas de papéis e `activity_log`, e altera
`users` (`password` nullable, `ultimo_acesso_em`). Detalhe em
[data-model.md](./data-model.md).

**Conferir que nasceram InnoDB** (E-003 — o MySQL do WAMP tem MyISAM como padrão; o projeto
força InnoDB em `config/database.php`, que **não se desfaz**):

```bash
php artisan db:table contas_sociais
```

---

## Subir os dois lados

A task `Bora: dev` do VS Code sobe `api/` (8000) e `web/` (3000). Manualmente:

```bash
php artisan serve
```

```bash
npm run dev
```

Em desenvolvimento o `MAIL_MAILER` está em `log`: os e-mails de verificação, união e
redefinição **não** são enviados de verdade — saem em `api/storage/logs/laravel.log`. É de
lá que se copia o link para validar os fluxos.

A fila precisa estar rodando, senão nenhum e-mail é processado (Princípio VI — envio é Job):

```bash
php artisan queue:work
```

---

## Cenários de validação manual

Cada bloco abaixo corresponde a uma user story da spec. O caminho é sempre **pelo `web/`**,
não por `curl` — a feature só está pronta com a tela (Princípio XI).

### V1 — Criar conta e entrar (US1)

1. Abrir `http://localhost:3000/criar-conta`, preencher nome, e-mail e senha, confirmar.
2. **Esperado**: confirmação de boas-vindas, pessoa autenticada, aviso discreto de e-mail
   não confirmado. No `laravel.log`, o e-mail de verificação.
3. Sair, entrar de novo em `/entrar` com o mesmo e-mail e senha. **Esperado**: autentica.
4. Tentar **criar outra conta** com o mesmo e-mail. **Esperado**: recusa com orientação para
   entrar — e **nenhuma segunda conta** no banco (é a prova do Princípio I).
5. Abrir o link de verificação do log. **Esperado**: e-mail confirmado, aviso some.

### V2 — Entrar com Google (US2)

1. Em `/entrar`, tocar "Entrar com Google" com um e-mail **inédito**.
2. **Esperado**: conta criada já com e-mail verificado, pessoa autenticada, papel
   `rolezeiro`.
3. Sair e repetir. **Esperado**: entra **na mesma conta** — conferir que
   `SELECT COUNT(*) FROM users WHERE email = ...` devolve 1.
4. Cancelar no meio do consentimento do Google. **Esperado**: volta para `/entrar` com
   mensagem humana e alternativa por e-mail/senha; **nenhuma conta criada**.

### V3 — Unir credenciais (US3)

1. Criar conta por e-mail/senha (V1). Sair.
2. Entrar com Google usando **o mesmo e-mail**.
3. **Esperado**: tela "Unir contas" explicando o que será unido, pedindo a senha.
4. Confirmar com a **senha correta**. **Esperado**: união feita, autenticado, mensagem
   "agora você pode entrar com Google ou com sua senha". Conferir: **uma** linha em `users`,
   **uma** em `contas_sociais`.
5. Repetir do zero e, na tela de união, escolher **"Receber link por e-mail"**; abrir o link
   do log. **Esperado**: união concluída igual ao passo 4.
6. Repetir e **cancelar**. **Esperado**: nada unido, nenhuma conta nova — a contagem de
   contas com aquele e-mail continua **exatamente 1** em todos os desfechos.

### V4 — Recuperar senha (US4)

1. Em `/entrar`, "Esqueci minha senha", informar o e-mail.
2. **Esperado**: mensagem neutra ("Se este e-mail estiver cadastrado..."). Repetir com um
   e-mail **inexistente** — **a mensagem tem de ser idêntica** (é a prova de que a API não
   enumera contas).
3. Abrir o link do log, definir senha nova. **Esperado**: entra com a nova; a antiga não
   funciona mais; sessões dos outros aparelhos caem.
4. Abrir o **mesmo link de novo**. **Esperado**: mensagem de link já usado + botão para
   pedir outro.

### V5 — Limites e erros

- Errar a senha 6 vezes seguidas. **Esperado**: bloqueio temporário com mensagem dizendo
  quanto esperar (429 com `Retry-After`). O mesmo limite vale na tela de união.
- Tocar duas vezes rápido no botão de criar conta (rede lenta). **Esperado**: **uma** conta.
- Cadastrar com `"  Maria@Gmail.com "`. **Esperado**: normalizado; tentar de novo com
  `maria@gmail.com` é reconhecido como o mesmo e-mail.

---

## Testes automatizados (Princípio IX — os dois lados)

```bash
php artisan test
```

```bash
npm run test
```

```bash
npx playwright test
```

**Critério de aprovação** (o que faz a feature passar, não "quase passar"):

- Backend: todo cenário do mapa regra→teste da spec verde, **incluindo** os que provam os
  bloqueios — conta paralela recusada nos dois caminhos, nenhum passo exigindo pagamento,
  senha/token fora do log, resposta neutra na recuperação.
- Front (componente): formulários com erro no campo em linguagem humana; **`axe` sem
  nenhuma violação** nas cinco telas.
- Front (e2e): as cinco telas em **360 e 1280**, com asserção explícita de **ausência de
  rolagem horizontal a 360px**.

---

## Documentação da API

```bash
php artisan scramble:export
```

A doc gerada tem de bater com [contracts/auth-api.md](./contracts/auth-api.md). Divergiu:
ou o código saiu do contrato, ou o contrato mudou e o arquivo não foi atualizado — não se
resolve escolhendo um dos dois no olho.

---

## Validar no celular de verdade (Princípio XI)

A validação visual do Ícaro é feita **primeiro no celular** (`ux-requirements.md`).
Depois dos erros E-011, E-012 e E-013, isso ficou **sem configuração**:

1. Subir a task `Bora: dev` do VS Code (ou os comandos acima). A API já sobe com
   `--host=0.0.0.0`; o Next escuta em todas as interfaces por padrão.
2. Descobrir o IP da máquina: `Get-NetIPAddress -AddressFamily IPv4`.
3. Abrir `http://<IP>:3000/entrar` no celular, **na mesma rede**.

Não é preciso mexer em `web/.env.local` nem apontar IP em lugar nenhum: o front deriva o
endereço da API de onde a página foi aberta, a CSP faz o mesmo pelo header `Host`, e o
`allowedDevOrigins` é preenchido a partir das interfaces de rede da máquina.

**Único ajuste manual que sobra:** o CORS da API usa origens explícitas (Princípio V), então
`FRONTEND_URLS` em `api/.env` precisa conter `http://<IP>:3000`. Se o DHCP mudar o IP, esse
valor precisa acompanhar. Sintoma quando não acompanha: a tela carrega e a ação falha com a
mensagem de conexão.

**Como saber que a tela está mesmo pronta:** o botão da ação principal mostra **"Carregando…"**
enquanto o JavaScript não assumiu, e só então vira "Entrar"/"Criar conta". Botão travado em
"Carregando…" significa que a hidratação falhou — não é lentidão, é defeito (E-013).

**Erro de verificação a não repetir (E-011):** testar com `curl` da própria máquina para o
próprio IP **não prova** que o celular alcança — esse tráfego não atravessa o firewall.
A prova é o aparelho.

**Login com Google no celular** exige túnel HTTPS: o IP de rede local não serve como URI de
redirecionamento (o Google só aceita HTTP em loopback). Item aberto no backlog; não bloqueia
as telas sem Google.

## Portão final (Princípio XI — Definition of Done)

A feature **não** está pronta antes de, juntos:

1. API completa e documentada, pronta para o app mobile sem mudança estrutural;
2. as cinco telas 100% no `web/`, consumindo só a API pública;
3. testes de backend **e** frontend executados e **aprovados**;
4. **validação visual do Ícaro — primeiro no celular** (`ux-requirements.md`: tela
   reprovada no celular não se apresenta em desktop).

Só depois disso a próxima feature começa. E é nesse momento que o andaime do spike sai:
`api/app/Http/Controllers/Spike/`, o bloco `v1/eventos` em `api/routes/api.php` e
`web/src/app/eventos/`.
