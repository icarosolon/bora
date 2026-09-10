# Contrato — API de Locais (`/api/v1`)

**Spec**: [../spec.md](../spec.md) · **Modelo**: [../data-model.md](../data-model.md) ·
**Data**: 2026-09-09

Convenções herdadas da spec 001 e do Princípio IV: versionamento no caminho, envelope
`data`/`meta`/`links` em lista e `message` em ação, `errors` em validação 422, sempre via
API Resource, datas em ISO 8601.

**Nomenclatura**: o **caminho** é em português (é endereço, é produto); o **corpo** do JSON
é em inglês (`docs/architecture/naming-conventions.md`).

---

## Rotas

| Método | Caminho | Sessão | História |
|---|---|---|---|
| `GET` | `/api/v1/locais` | não | P2 |
| `GET` | `/api/v1/locais/{slug}` | não | P1 |
| `POST` | `/api/v1/locais` | **sim** | P1 |
| `GET` | `/api/v1/locais/semelhantes` | **sim** | P1 |
| `PATCH` | `/api/v1/locais/{slug}` | **sim** | P4 |
| `GET` | `/api/v1/categorias-de-local` | não | P1 |
| `POST` | `/api/v1/locais/{slug}/reivindicacoes` | **sim** | P3 |
| `GET` | `/api/v1/reivindicacoes` | **sim** (operação) | P3 |
| `POST` | `/api/v1/reivindicacoes/{id}/aprovar` | **sim** (operação) | P3 |
| `POST` | `/api/v1/reivindicacoes/{id}/recusar` | **sim** (operação) | P3 |

> **São três as rotas públicas** — `GET /api/v1/locais`, `GET /api/v1/locais/{slug}` e
> `GET /api/v1/categorias-de-local`. Elas respondem **sem token**: é o Princípio II, e existe
> teste que prova o bloqueio. Nenhuma delas aceita nem lê `Authorization`, o que também
> garante o ADR-0003 — o componente de servidor do Next as consome sem jamais tocar em token.
>
> **`GET /api/v1/locais/semelhantes` fica de fora e exige sessão**, como a tabela acima já
> diz (decisão do Ícaro, 2026-09-09). Ela só é chamada de dentro do formulário de cadastro,
> que é área autenticada — não faz parte do catálogo que o Princípio II manda abrir. A
> fronteira só é testável nos dois sentidos: o teste prova que as três públicas respondem
> **sem** token **e** que `semelhantes` **recusa** a requisição sem sessão. Provar o lado de
> fora sem provar o lado de dentro deixaria a fronteira sem guarda.

---

## `GET /api/v1/locais/{slug}` — perfil público

Renderizado no servidor pelo `web/` (catálogo público, ADR-0003).

```json
{
  "data": {
    "slug": "bar-do-ze-centro",
    "name": "Bar do Zé",
    "claimed": false,
    "categories": [{ "slug": "bar", "name": "Bar" }],
    "phone": "+5574999990000",
    "address": {
      "street": "Rua da Orla", "number": "120", "complement": null,
      "district": "Centro", "city": "Juazeiro", "state": "BA",
      "postal_code": "48900-000",
      "formatted": "Rua da Orla, 120 — Centro, Juazeiro/BA"
    },
    "description": null,
    "instagram": null,
    "logo_url": null,
    "created_at": "2026-09-09T14:03:00-03:00"
  }
}
```

- **`claimed`** é o único sinal de estado; a tela decide o texto do selo. O back **não**
  manda texto de interface (prosa de tela é do `web/`).
- Perfil não reivindicado devolve os campos ricos como **`null`** — nunca omite a chave, para
  o contrato ser estável, e nunca inventa valor (FR-002, FR-015).
- **`address.formatted`** existe para o `web/` montar o link do app de mapas sem remontar
  endereço na tela — é dado, não regra (`RN-DESC-004`, R5).
- **404** quando o local está inativo (Princípio X: inativa, não exclui — mas some do
  público).

## `GET /api/v1/locais` — lista pública

Query: `?categoria={slug}&cidade={nome}&busca={termo}&pagina={n}`

```json
{
  "data": [
    { "slug": "bar-do-ze-centro", "name": "Bar do Zé",
      "district": "Centro", "city": "Juazeiro", "state": "BA",
      "claimed": false, "categories": [{ "slug": "bar", "name": "Bar" }] }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 37 },
  "links": { "next": "...", "prev": null }
}
```

- Cada item traz **`district`** — é o que desambigua unidades e responde à pergunta
  geográfica (FR-006).
- **Nenhum campo depende de quem está olhando** (FR-017, `D15`). Não há `saved`, não há
  `following`. O servidor não sabe quem é, e campo que fingisse saber mentiria.

## `POST /api/v1/locais` — criar

```json
{
  "name": "Bar do Zé",
  "phone": "+5574999990000",
  "category_ids": [1],
  "address": { "postal_code": "48900-000", "street": "Rua da Orla",
               "number": "120", "complement": null, "district": "Centro",
               "city": "Juazeiro", "state": "BA" }
}
```

- Aceita **apenas** estes campos. Campo rico enviado aqui é **ignorado**, não aceito
  silenciosamente — `validated()`, nunca `all()` (Princípio V).
- **201** com o recurso criado, incluindo o `slug` — é dele que a tela monta o endereço
  compartilhável (SC-002).
- **422** com `errors` por campo, em português (`{"phone": ["Digite o telefone com DDD."]}`).
- **Idempotência de toque duplo** (FR-018): a requisição carrega um identificador de
  submissão; a segunda com o mesmo identificador devolve o **mesmo** recurso, não um novo.

## `GET /api/v1/locais/semelhantes` — aviso de duplicata

Query: `?nome={nome}&bairro={bairro}&cidade={cidade}`

Devolve os candidatos por **nome normalizado + mesmo bairro** (R4). **Não bloqueia nada** —
a decisão é de quem cadastra (FR-020). Lista vazia é resposta normal, não erro.

## `POST /api/v1/locais/{slug}/reivindicacoes` — pedir

```json
{
  "requester_name": "Maria Souza",
  "requester_role": "Proprietária",
  "best_contact_time": "Terça a sábado, depois das 18h",
  "ask_for": "Maria ou Seu Antônio"
}
```

- **Os quatro campos são obrigatórios** (FR-026). Sem evidência, a aprovação manual viraria
  palpite.
- **201** e situação `pending`.
- **Segundo pedido pendente para o mesmo local é aceito** (FR-022) — não é 409.
- **409** apenas quando o local **já está reivindicado**.

## `POST /api/v1/reivindicacoes/{id}/aprovar`

Cria o vínculo em `venue_managers`, **transfere sem recriar** (FR-009), encerra os demais
pendentes daquele local como recusados com motivo automático e **enfileira o aviso a cada
solicitante** (FR-023). Registra `verification_method: "manual"`.

## `POST /api/v1/reivindicacoes/{id}/recusar`

```json
{ "reason": "Não conseguimos confirmar pelo telefone do estabelecimento." }
```

- **`reason` é obrigatório** (FR-021) e vai no aviso ao solicitante, junto com o caminho para
  falar com a plataforma.
- Permite **novo pedido** depois.

## `GET /api/v1/categorias-de-local`

Devolve a lista **de dado** (`RN-LOCAL-002`). O front nunca traz a lista embutida — existe
teste provando que acrescentar categoria não exige mudar código.

---

## O que este contrato **não** expõe

- Nenhuma rota de evento. O **bloqueio** (FR-013) é verificado no caso de uso de publicação,
  que chega na spec de eventos — aqui existe o teste que prova que local não reivindicado é
  recusado.
- Nenhuma rota de salvar/seguir (`RN-DESC-003`, outra spec).
- Nenhuma rota de exclusão de local. Inativar é operação de plataforma, e a **tela** de
  inativar não é desta feature (FR-014 é guarda, não funcionalidade).

## Documentação

Gerada pelo `dedoc/scramble`, já instalado. O padrão de documentação por feature é a
**BORA-26** e segue em aberto — esta feature usa o que a spec 001 já pratica, sem inventar
convenção nova.
