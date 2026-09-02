# Phase 1 — Contrato da API: Fundação de Contas e Autenticação

**Spec**: [../spec.md](../spec.md) · **Plano**: [../plan.md](../plan.md) · **Data**: 2026-08-30

Este arquivo é a **fonte de verdade da revisão** do contrato. Em produção quem **publica** a
documentação é o Scramble (D3), gerado a partir de FormRequests e Resources — se os dois
divergirem, o código está errado ou este arquivo está desatualizado; não se resolve
"escolhendo um".

## Convenções (Princípio IV — constituição)

- Base: **`/api/v1`**. Nada desta feature fora do prefixo. A rota `/api/user`, que hoje
  existe fora do versionamento, **é movida** para `/api/v1/eu`.
- Envelope: `data` em recursos, `message` em ações, `errors` em validação 422.
- Toda resposta sai por **API Resource** — nunca Model serializado direto.
- Datas em **ISO 8601** (`2026-08-30T14:32:07-03:00`).
- Autenticação: header `Authorization: Bearer <token>`. **Nunca** token em URL ou query.
- Idioma das mensagens: **português**, em linguagem humana — a mensagem da API é a que a
  tela mostra (`ux-requirements.md`: erro diz o que fazer).
- Idioma dos **nomes de campo**: **inglês** (`name`, `password`, `expires_at`,
  `merge_token`). Só o **caminho** da rota fica em português (`/sessoes`,
  `/email/verificar/reenviar`), porque endereço é coisa que a pessoa vê e compartilha.
  Convenção completa: [`docs/architecture/naming-conventions.md`](../../../docs/architecture/naming-conventions.md).
  Adotada em 2026-08-31, com a spec 001 já implementada e nada em produção.

## Códigos de status usados

| Código | Quando |
|---|---|
| 200 | ação concluída |
| 201 | conta criada |
| 204 | sessão encerrada |
| 401 | credenciais inválidas ou token ausente/expirado |
| 409 | **união de credenciais necessária** (não é erro do cliente — é um passo a mais) |
| 410 | link de e-mail expirado ou já usado |
| 422 | validação — sempre com `errors` por campo |
| 429 | limite de tentativas excedido — sempre com `Retry-After` |

---

## Conta

### `POST /api/v1/contas` — criar conta (US1)

**Corpo**: `name` (obrigatório), `email` (obrigatório, e-mail válido), `password`
(obrigatório, mínimo configurável — inicial 8).

**201**
```json
{
  "message": "Conta criada! Boas-vindas ao Bora.",
  "data": {
    "account": { "id": 1, "name": "Maria", "email": "maria@exemplo.com",
               "email_verified": false, "roles": ["rolezeiro"],
               "signs_in_with": ["password"],
               "created_at": "2026-08-30T14:32:07-03:00" },
    "token": "1|abc...", "expires_at": "2026-09-29T14:32:07-03:00"
  }
}
```

> **`signs_in_with`** — quais caminhos de entrada a conta tem hoje: `"password"` quando há
> senha definida, mais um item por provedor vinculado (`"google"`). É o **mesmo objeto
> conta** em toda resposta que a devolve (`POST /contas`, `POST /sessoes`,
> `POST /auth/google/sessoes`, `POST /uniao-credenciais`, `GET /eu`), produzido por um único
> `AccountResource`.
>
> Não é enfeite: a tela decide com ele. Sem `"password"` na lista, o `web/` oferece o
> caminho para **Definir senha** (FR-012/US2-5); com `"password"`, não oferece. Cliente que
> ignore este campo deixa a pessoa nascida do Google sem caminho para ganhar uma senha —
> foi exatamente esse o defeito E-019.
>
> **Ficou fora deste contrato até 2026-09-02**, embora a API o devolva desde a US2. A T112
> conferiu as **rotas** contra o contrato (as 14 batem), não os **campos** de cada payload;
> por isso a divergência sobreviveu ao Polish.

**422 — e-mail já cadastrado** (Princípio I: não cria conta paralela). A mensagem muda
conforme o meio de entrada que a conta já tem, porque a tela precisa orientar o próximo
passo (US1-4, US2-4):

```json
{ "message": "Este e-mail já tem conta no Bora.",
  "errors": { "email": ["Este e-mail já tem conta. Entre com sua senha ou use \"Esqueci minha senha\"."] } }
```
Se a conta existente só entra pelo Google: `"Este e-mail já entra com o Google. Toque em \"Entrar com Google\"."`

> **Decisão consciente sobre enumeração de contas.** Aqui a API **revela** que o e-mail já
> existe — ao contrário da recuperação de senha, que responde neutro. Sem isso, a pessoa não
> tem como saber o que fazer e a tela falharia o `ux-requirements.md`. É o comportamento
> padrão de qualquer cadastro; o mitigante é o rate limit desta rota.

**422 — validação**: `email` malformado, `password` curta, `name` vazio — erro por campo, em
linguagem humana (US1-5).

---

## Sessão

### `POST /api/v1/sessoes` — entrar com e-mail e senha (US1)

**Corpo**: `email`, `password`, `device` (opcional — rótulo legível da sessão).

**200**: mesmo formato de `data` do cadastro (`account`, `token`, `expires_at`).

**401 — credenciais erradas** (mensagem única, não revela qual campo errou — FR-006):
```json
{ "message": "E-mail ou senha não conferem. Confira e tente de novo, ou use \"Esqueci minha senha\"." }
```

**401 — conta sem senha** (nasceu no Google):
```json
{ "message": "Esta conta entra com o Google. Toque em \"Entrar com Google\"." }
```

**429 — limite excedido** (FR-007; inicial 5/min por e-mail+origem), com `Retry-After`:
```json
{ "message": "Muitas tentativas. Aguarde 1 minuto e tente de novo." }
```

### `DELETE /api/v1/sessoes/atual` — sair · **autenticado**

Revoga **apenas** o token da request. **204**, sem corpo.

### `GET /api/v1/eu` — conta autenticada · **autenticado**

Substitui `/api/user`. **200** com a conta **em `data`** (não `data.account`): é um recurso
único, então `data` É o recurso, como faz todo API Resource do Laravel. Corrigido aqui em
2026-08-31, na implementação — o contrato dizia `data.account` por engano de escrita.
**401** se o token expirou — a tela leva ao
login preservando o destino de origem (edge case de sessão expirada).

> Cada request autenticada **empurra `expires_at` para agora + 30 dias** (janela deslizante
> — [research.md](../research.md) §1). Isso vale para todos os endpoints autenticados, não
> só este.

---

## Google (US2)

Fluxo escolhido para manter o **token fora da URL**: a API devolve a URL de autorização, o
Google redireciona para **uma página do `web/`**, e o `web/` troca o `code` por sessão via
POST. O token nunca aparece em barra de endereço, histórico ou log de servidor.

### `GET /api/v1/auth/google/url`

**200**
```json
{ "data": { "url": "https://accounts.google.com/o/oauth2/v2/auth?...", "state": "aleatorio-opaco" } }
```
O `state` é gerado e validado pela API (o Socialite em modo `stateless` não o verifica
sozinho) — é a proteção contra CSRF do fluxo OAuth.

### `POST /api/v1/auth/google/sessoes`

**Corpo**: `code`, `state`.

**200 — entrou ou conta criada**: mesmo `data` de sessão. Conta nova nasce com
`email_verified: true` (o Google já verificou) e papel `rolezeiro`.

**409 — união necessária** (US3-1): existe conta com este e-mail criada por e-mail/senha.
**Nada é gravado** neste passo.
```json
{
  "message": "Você já tem conta no Bora com este e-mail. Confirme para unir e entrar com o Google também.",
  "data": {
    "status": "merge_required",
    "email": "maria@exemplo.com",
    "merge_token": "opaco-de-uso-unico",
    "expires_at": "2026-08-30T14:47:07-03:00"
  }
}
```
O `merge_token` é **de uso único e curto** (inicial: 15 min) e só serve para concluir esta
união — não autentica nada.

**401 — cancelado ou recusado pelo Google** (US2-3):
```json
{ "message": "Não deu para entrar com o Google agora. Tente de novo ou use seu e-mail e senha." }
```
Mesma mensagem quando o provedor falha ou não devolve e-mail. **Nenhuma conta é criada**
em nenhum desses casos.

**422**: `state` inválido/expirado — a tela recomeça o fluxo.

---

## União de credenciais (US3 — D1)

### `POST /api/v1/uniao-credenciais` — confirmar com a senha

**Corpo**: `merge_token`, `password` (a senha da conta existente).

**200**: união concluída — devolve `data` de sessão.
```json
{ "message": "Pronto — agora você pode entrar com Google ou com sua senha." }
```

**401 — senha errada** (US3-4): `{ "message": "Senha não confere. Tente de novo ou receba um link por e-mail." }`
Sujeito ao **mesmo rate limit** do login (FR-007) — a spec exige que o bloqueio valha
também nesta porta.

**410 — `merge_token` expirado ou já usado** (US3-5):
`{ "message": "Este pedido expirou. Entre com o Google de novo para recomeçar." }`

### `POST /api/v1/uniao-credenciais/link` — plano B (US3-3)

**Corpo**: `merge_token`. Envia link de confirmação ao e-mail da conta (Job na fila).
**200**, resposta sempre igual: `{ "message": "Enviamos um link para o seu e-mail. Ele vale por 1 hora." }`

### `POST /api/v1/uniao-credenciais/link/confirmar`

**Corpo**: `token` (o do e-mail). **200** com sessão, igual à confirmação por senha.
**410** se expirado ou já usado.

### `POST /api/v1/senha` — definir senha · **autenticado** (US2-5, D1 inversa)

Para conta que nasceu no Google. Exige **sessão ativa** — é isso que faz as vezes de
confirmação do titular.

**Corpo**: `password`. **200**: `{ "message": "Senha definida. Agora você também entra com e-mail e senha." }`
**422** se a conta já tem senha (aí o caminho é trocar senha, fora do escopo desta feature).

---

## Recuperação de senha (US4 — D6)

### `POST /api/v1/senha/esqueci`

**Corpo**: `email`.

**200 — resposta neutra, sempre a mesma** (FR-014; exista ou não a conta):
```json
{ "message": "Se este e-mail estiver cadastrado, você receberá um link para redefinir a senha." }
```
Conta que só entra pelo Google recebe um e-mail explicando isso (US4-4) — mas a **resposta
da API não muda**, senão a neutralidade se perde.

**429** com `Retry-After` — impede varredura de e-mails.

### `POST /api/v1/senha/redefinir`

**Corpo**: `token`, `password`.

**200**: `{ "message": "Senha alterada. Você já pode entrar com ela." }` — invalida a senha
anterior e **revoga as sessões dos outros aparelhos** (FR-015).

**410** se o link expirou ou já foi usado (US4-3). **422** se a senha nova é inválida.

---

## Verificação de e-mail (D5)

### `POST /api/v1/email/verificar`

**Corpo**: `token`. **200**: `{ "message": "E-mail confirmado. Obrigado!" }`
**410** se expirado — a tela oferece reenviar (US1-7).

### `POST /api/v1/email/verificar/reenviar` · **autenticado**

**200**: `{ "message": "Enviamos um novo link para o seu e-mail." }`. **422** se já
verificado. Sujeito a rate limit.

---

## Regras que valem para **todos** os endpoints

1. **Nenhum endpoint desta feature toca cobrança** — Princípio II; há teste que percorre os
   fluxos e prova isso (SC-007).
2. **Entrada sempre por FormRequest** com `authorize()` e `rules()`, sempre `validated()` —
   nunca `$request->all()` (Princípio V).
3. **Senha e token nunca em log**, nem em auditoria, nem em mensagem de erro.
4. **E-mail sempre normalizado** antes de consultar ou gravar — é o que sustenta a
   invariante de conta única.
5. **E-mails saem em Job** na fila Redis, nunca no ciclo da request (Princípio VI). O
   endpoint responde sem esperar o provedor; falha de envio **não** impede o cadastro (D5).
6. **Toda escrita audita** o evento, sem valores sensíveis (Princípio VIII).
7. Erros 5xx nunca vazam detalhe interno para a tela — mensagem humana e genérica; o
   diagnóstico vai para o log, sem dado pessoal.

## Rotas resumidas

```text
POST   /api/v1/contas                          criar conta
POST   /api/v1/sessoes                         entrar (e-mail/senha)
DELETE /api/v1/sessoes/atual         [auth]    sair
GET    /api/v1/eu                    [auth]    conta autenticada
GET    /api/v1/auth/google/url                 URL de autorização + state
POST   /api/v1/auth/google/sessoes             troca code por sessão (ou 409 união)
POST   /api/v1/uniao-credenciais               confirmar união com senha
POST   /api/v1/uniao-credenciais/link          enviar link (plano B)
POST   /api/v1/uniao-credenciais/link/confirmar  confirmar união por link
POST   /api/v1/senha                 [auth]    definir senha (conta Google)
POST   /api/v1/senha/esqueci                   solicitar redefinição
POST   /api/v1/senha/redefinir                 redefinir com token
POST   /api/v1/email/verificar                 confirmar e-mail
POST   /api/v1/email/verificar/reenviar [auth] reenviar verificação
```
