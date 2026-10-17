# Phase 1 — Modelo de dados: Cadastro e Perfil de Estabelecimento

**Spec**: [spec.md](./spec.md) · **Plano**: [plan.md](./plan.md) ·
**Pesquisa**: [research.md](./research.md) · **Data**: 2026-09-09

Nomes de tabela e coluna em **inglês**; prosa em português
(`docs/architecture/naming-conventions.md`).

---

## Visão geral

```text
accounts (users) ──N:N── venues ──N:N── venue_categories
      │                    │
      └────── venue_claims ┘
```

**Cinco tabelas novas**: `venues`, `venue_categories`, `venue_venue_category`,
`venue_managers` e `venue_claims` — uma seção deste documento para cada uma. Nenhuma tabela
existente é recriada; `users` ganha apenas as relações.

---

## `venues` — o estabelecimento

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | pk | |
| `slug` | string, único | **imutável** depois de criado (R3). Derivado do nome, desambiguado pelo bairro na colisão |
| `name` | string | |
| `name_normalized` | string, indexada | minúscula, sem acento, sem pontuação, sem termo genérico inicial — alimenta o aviso de duplicata (FR-020, R4) |
| `phone` | string | telefone público; é **o número para o qual se liga** ao verificar reivindicação (FR-026) |
| `postal_code` | string | |
| `street` / `number` / `complement` | string | `number` é string **obrigatória** e aceita `"S/N"`; `complement` é nulo |
| `district` | string, indexada | **bairro** — exibido em listagem (FR-006) e usado na desambiguação |
| `city` / `state` | string, indexada | |
| `latitude` / `longitude` | `decimal(10,7)`, **nulo** | vazios na Fase 1. Existem para a BORA-8 não exigir migração de tabela depois (R5) |
| `description` | text, nulo | **P4** — só para perfil reivindicado |
| `instagram` | string, nulo | **P4** |
| `logo_path` | string, nulo | **P4** — logo. **Não há coluna de capa**: `cover_path` foi retirado em 2026-09-10 por não ter destino no contrato, na spec nem em tarefa. Volta por migration quando a capa for pedida |
| `active` | boolean, **default `true`** | Princípio X: inativa, nunca exclui |
| `created_by_account_id` | fk → `users`, `restrict` | quem criou — não se apaga quando o perfil é transferido |
| `timestamps` | | |

**Invariantes** (no domínio, não na borda):

- Perfil **não reivindicado** não aceita valor em `description`, `instagram` nem
  `logo_path` (FR-002, FR-015). A invariante vive na entidade — a borda só a reporta.
- `slug` não muda depois de criado (R3).
- Ao menos **uma** categoria (FR-003).

**Chaves estrangeiras** (decisão do Ícaro, 2026-10-16): todas com tabela referenciada
explícita (`users` para os `*_account_id`), `restrict` e **sem `cascade`** — nada some por
exclusão de linha vizinha (Princípio X).

**Atribuição em massa** (`$fillable`/`$hidden` do `Venue`): ficam **fora** do `$fillable`
`slug`, `name_normalized`, `active` e `created_by_account_id` — só o caso de uso os define.
Ficam **ocultos** na serialização `created_by_account_id` e `name_normalized`.

## `venue_categories` — a lista, como dado

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | pk | |
| `slug` | string, único | `bar`, `restaurante`, `casa-de-shows` |
| `name` | string | rótulo exibido |
| `active` | boolean, **default `true`** | |
| `position` | int | ordem de exibição: 1 bar, 2 restaurante, 3 casa de shows |

Semeada com **bar, restaurante, casa de shows** (`RN-LOCAL-002`), por seeder **idempotente
por `slug`** (`updateOrCreate`). Acrescentar categoria é
inserir linha — e existe teste que prova que **não exige mudar código**.

## `venue_venue_category` — N:N, sem limite

| Coluna | Tipo |
|---|---|
| `id` | pk |
| `venue_id` | fk |
| `venue_category_id` | fk |

Chave única no par. **Sem teto** de categorias por local (`RN-LOCAL-002`, BORA-51).

## `venue_managers` — o vínculo de gestão, N:N

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | pk | |
| `venue_id` | fk | |
| `account_id` | fk → `users` | |
| `granted_at` | timestamp | |
| `granted_by_claim_id` | fk, **obrigatório** | qual reivindicação originou o vínculo |
| `revoked_at` | timestamp, nulo | revogar é preencher esta coluna: **a linha nunca se apaga** (Princípios VIII e X) |
| `active_marker` | coluna gerada | `IF(revoked_at IS NULL, 1, NULL)` |
| `timestamps` | | |

**Unicidade do par `(venue_id, account_id)` só entre os vínculos ativos**: índice único
`venue_managers_active_unique` sobre `(venue_id, account_id, active_marker)`. `NULL` não
colide em índice único no MySQL, então o histórico de vínculos revogados do mesmo par se
preserva.

**N:N por decisão explícita** (FR-012, `RN-LOCAL-004`): uma conta gerencia vários locais com
a **mesma conta**, nunca contas paralelas (Princípio I). Implementar como um-para-um passaria
despercebido até a primeira rede aparecer — por isso está escrito aqui e tem teste próprio.

## `venue_claims` — o pedido de reivindicação

| Coluna | Tipo | Notas |
|---|---|---|
| `id` | pk | |
| `venue_id` | fk | |
| `account_id` | fk | quem pediu |
| `status` | `string(16)` | `pending`, `approved`, `rejected` — enum PHP `App\Enums\VenueClaimStatus`, **não** enum MySQL; sem `UNIQUE` |
| **`requester_name`** | string | evidência (FR-026) |
| **`requester_role`** | string | função no estabelecimento |
| **`best_contact_time`** | string | melhor horário para ligar |
| **`ask_for`** | string | a quem perguntar ao ligar |
| `decided_by_account_id` | fk, nulo | |
| `decided_at` | timestamp, nulo | |
| `decision_reason` | text, nulo | **obrigatório quando `rejected`** (FR-021) |
| `verification_method` | string, nulo | `manual` na Fase 1; existe porque **o método vai mudar** (BORA-49) |
| `timestamps` | | |

**Os quatro campos de evidência não são burocracia.** Sem eles a aprovação manual — que é
julgamento — não teria matéria, e a tela de aprovar mostraria "a conta X quer o Bar do Zé".
Foram escolhidos por serem **o mesmo sinal** que o método automático vai usar (código no
telefone do local), feito à mão: sobrevivem à troca em vez de virar dado órfão.

**A verificação liga para `venues.phone`**, nunca para um número que o solicitante informe —
esse provaria apenas que ele tem telefone.

**Atribuição em massa** (`VenueClaim`): o `$fillable` aceita só os **quatro campos de
evidência**; ficam **ocultos** a evidência e `account_id`.

### Transições de estado

```text
                    ┌──────────────► rejected  (motivo obrigatório, avisa)
                    │
(criado) ──► pending┤
                    │
                    └──────────────► approved  (transfere, avisa)
                                          │
                                          └──► demais pending do mesmo venue
                                               viram rejected com motivo
                                               "reivindicado por outra pessoa",
                                               e cada solicitante é avisado (FR-023)
```

- **Vários `pending` para o mesmo `venue` são permitidos** (FR-022) — perder o registro de
  quem pediu antes destruiria o dado que o Princípio VIII manda guardar para arbitrar.
- Aprovar **cria o vínculo** em `venue_managers`; **não** recria o `venue` (FR-009,
  Princípio X). Avaliações e histórico continuam ligados ao mesmo `venue_id`.
- Recusar **permite novo pedido** (FR-021).
- **O estado do local é derivado** (R6): existe claim `approved` ⇒ reivindicado. Não há
  `enum` de estado no `venues` que possa divergir do histórico.

---

## Relações em `User` (`users`)

- `venues()` = locais **criados** pela conta (`created_by_account_id`); **não concede
  gestão**.
- `managedVenues()` = N:N via `venue_managers`, **só com vínculos ativos**
  (`revoked_at` nulo).

---

## O que esta feature **não** modela

Declarado para ninguém procurar depois:

- **Evento** — outra spec. Aqui existe só o **bloqueio**: local não reivindicado não publica
  (FR-013, `RN-EVENTO-001`).
- **Salvar e seguir** — `RN-DESC-003`, da descoberta.
- **Avaliações e comentários** — `RN-AVAL-001`.
- **Rede/franquia como entidade** — não existe (`RN-LOCAL-004`); unidades são `venues`
  independentes.
- **Cor por categoria** — a escala categórica ficou de fora da fundação (D22): é de gênero
  musical, e a lista é a BORA-19.

## Auditoria (Princípio VIII)

Via `spatie/laravel-activitylog`, já instalado. Registram-se: criação de local, pedido de
reivindicação, aprovação, recusa e alteração de perfil — com quem, quando, valores antes e
depois, e **o método** no caso da reivindicação.
