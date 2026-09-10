# Implementation Plan: Cadastro e Perfil de Estabelecimento

**Branch**: `002-cadastro-perfil-local` | **Date**: 2026-09-09 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/002-cadastro-perfil-local/spec.md` (aprovada
pelo Ícaro em 2026-09-09)

## Summary

Destrava o catálogo do Bora. Qualquer conta autenticada cria o perfil de um local; ele nasce
**não reivindicado** e **magro** (nome, endereço, categorias, telefone), aparece no catálogo
público renderizado no servidor, e **não publica evento** até ser reivindicado. A
reivindicação carrega evidência para julgamento manual, e a aprovação transfere o perfil sem
recriá-lo.

Tecnicamente, a feature tem duas metades desiguais:

1. **Uma fase Foundational que não é desta feature, mas passa por ela** — o portão de
   conformidade de tela, o kit de UI com a régua embutida, tipografia, papéis de cor, shell
   de consumo e o retrofit das telas da spec 001. Decisão D6 do `design-system.md`.
2. **Quatro histórias verticais** (API + tela + testes), na ordem P1 cadastro/página
   pública, P2 lista e busca, P3 reivindicação, P4 perfil rico.

A abordagem técnica não tem novidade de arquitetura: reusa o que a spec 001 estabeleceu
(portas e adaptadores, casos de uso, FormRequest/Policy/Resource, Sanctum, activitylog) e
acrescenta um contexto de domínio novo — `Venue` — mais o primeiro **catálogo público
renderizado no servidor** do projeto, que é o que ainda não existe no `web/`.

## Technical Context

**Language/Version**: PHP 8.4 (composer exige `^8.3`) com Laravel 13.17 na `api/`;
TypeScript 5 com React 19.2 e Next.js 16.3.3 (App Router) no `web/`.

**Primary Dependencies**: `laravel/sanctum` (token de API), `spatie/laravel-permission`
(papéis da conta única), `spatie/laravel-activitylog` (Princípio VIII),
`dedoc/scramble` (documentação da API). No front: Tailwind 4, `@base-ui/react`,
`class-variance-authority`, `lucide-react`. **Nenhuma dependência nova é necessária** —
ver `research.md`.

**Storage**: MySQL 8, banco único, sem multi-tenancy (ADR-0001). Migrations existentes
cobrem só contas; esta feature acrescenta as tabelas de local, categoria, vínculo de gestão
e pedido de reivindicação.

**Testing**: PHPUnit 12 na `api/`; Vitest + Testing Library + `jest-axe` para componente e
Playwright para e2e no `web/`. As três suítes já existem e passam.

**Target Platform**: navegador de celular como alvo principal (piso 360px), navegador de
computador como secundário; API servida por PHP-FPM e front por processo Node.

**Project Type**: aplicação web de duas partes desacopladas no mesmo repositório
(ADR-0002) — `api/` e `web/`.

**Performance Goals**: SC-006 da spec — em **3G**, o conteúdo essencial da página pública
(nome, endereço, telefone, "Como chegar") **visível e utilizável em até 3 segundos**, com
as imagens carregando depois **sem deslocar** o que já está na tela.

**Constraints**: piso de **360px** sem rolagem horizontal; fonte base ≥ 16px; alvo de toque
≥ 44px; contraste **WCAG 2.1 AA**; zoom de 200% **e** fonte do sistema ampliada sem quebra;
nada dependente de `hover`; token nunca cruza para componente de servidor (ADR-0003);
nenhuma regra de negócio no `web/`.

**Scale/Scope**: escala de validação — duas cidades (Juazeiro-BA e Petrolina-PE), dezenas a
poucas centenas de locais, aprovação de reivindicação **manual** e feita por uma pessoa.
Escopo desta feature: **7 telas** (cadastrar, perfil público, lista, pedir reivindicação,
aprovar reivindicações, editar perfil, mais o aviso por e-mail que não é tela) e
**4 entidades** novas.

## Constitution Check

*GATE: verificado antes da Phase 0 e reavaliado depois da Phase 1.*

| Princípio | Como esta feature cumpre | Porta |
|---|---|---|
| **I — Conta única multi-papel** | O papel de gestor é vínculo `account ↔ venue` **N:N** sobre a conta existente; nenhuma conta paralela. Papel via `spatie/laravel-permission`, como já é feito | ✅ |
| **II — Gratuidade do usuário final** | Todo o catálogo público (perfil, lista, busca, "Como chegar") responde **sem sessão e sem cobrança**. Teste prova o bloqueio | ✅ |
| **III — Conteúdo de terceiros e LGPD** | Nenhum dado externo entra nesta feature: sem Google Places, sem scraping. O "Como chegar" é **link** para o app do aparelho, não integração (`RN-DESC-004`) | ✅ |
| **IV — API-first e contrato estável** | Toda a feature existe primeiro como API sob `/api/v1`, com envelope padrão e API Resource. O `web/` consome só a API pública. Contrato em `contracts/locais-api.md` | ✅ |
| **V — Segurança e autorização** | `FormRequest` com `authorize()`/`rules()` em toda escrita; `VenuePolicy` em toda ação sensível; `$fillable` explícito; upload validado por MIME e tamanho (P4) | ✅ |
| **VI — Assíncrono** | O aviso de resultado da reivindicação (FR-021, FR-023) sai por **Job de fila**, reusando a porta de e-mail da spec 001 | ✅ |
| **VII — Simplicidade e camadas** | Um controller por recurso; casos de uso só onde a lógica cruza models (reivindicação, transferência); CRUD simples fica em Controller + FormRequest | ✅ |
| **VIII — Auditoria de escrita** | Criação, pedido, aprovação e recusa geram registro com quem, quando e **por qual método** | ✅ |
| **IX — Qualidade verificável** | Uma asserção por `RN` referenciada e uma por princípio NON-NEGOTIABLE tocado — a spec já as enumera na seção "Cenários de Teste" | ✅ |
| **X — Preservação do histórico** | Aprovação **transfere**, não recria; local com histórico é **inativado**, nunca excluído, e o sistema informa a condição que impede | ✅ |
| **XI — Entrega vertical com validação visual** | Cada história entrega API + tela + testes, e é **validada visualmente por história**, não no fim da spec | ✅ |
| **XII — Usabilidade universal** | `ux-requirements.md` aplicado tela a tela; o **portão automatizado** passa a verificar isso por construção | ✅ |

**Stack obrigatória**: nenhuma escolha fundacional muda. Nenhuma emenda constitucional é
necessária para esta feature.

### Desvio declarado, já justificado

| Desvio | Por que | Onde foi decidido |
|---|---|---|
| Esta feature carrega uma **fase Foundational** que não pertence a ela (kit, tema, shell, portão, retrofit da spec 001) | O template do projeto não tem lugar para trabalho fundacional sem tela, e um documento normativo não passa no próprio `spec-check`. A alternativa — uma rodada de fundação antes — foi avaliada e recusada pelo Ícaro | **D6** do `design-system.md` |

Isso **não é violação de princípio**; é escopo aumentado, com o risco registrado: escopo
grande é onde a validação visual vira carimbo. A mitigação está na estrutura — a fase
Foundational do `tasks-template.md` bloqueia todas as histórias, e a validação do Ícaro é
**por história**.

## Project Structure

### Documentation (this feature)

```text
specs/002-cadastro-perfil-local/
├── spec.md                    # aprovada em 2026-09-09
├── plan.md                    # este arquivo
├── research.md                # Phase 0
├── data-model.md              # Phase 1
├── quickstart.md              # Phase 1
├── contracts/
│   └── locais-api.md          # Phase 1
├── checklists/
│   └── requirements.md        # do /speckit-specify
└── tasks.md                   # do /speckit-tasks — NÃO criado aqui
```

### Source Code (repository root)

```text
api/
├── app/
│   ├── Domain/
│   │   └── Venue/                  # NOVO — invariantes de local e reivindicação
│   ├── UseCases/
│   │   └── Venue/                  # NOVO — CreateVenue, ClaimVenue, ApproveClaim, RejectClaim
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── VenueController.php         # NOVO
│   │   │   ├── VenueClaimController.php    # NOVO
│   │   │   └── VenueCategoryController.php # NOVO
│   │   ├── Requests/Venue/         # NOVO — FormRequests
│   │   └── Resources/              # NOVO — VenueResource, VenueClaimResource
│   ├── Models/                     # NOVO — Venue, VenueCategory, VenueClaim
│   ├── Policies/                   # NOVO — VenuePolicy, VenueClaimPolicy
│   ├── Jobs/                       # aviso de resultado da reivindicação
│   ├── Ports/ + Adapters/          # reusados — porta de e-mail da spec 001
│   └── Rules/
├── database/migrations/            # 5 migrations novas
└── tests/{Feature,Unit}/Venue/     # NOVO

web/
├── src/
│   ├── app/
│   │   ├── locais/
│   │   │   ├── page.tsx                    # lista (P2) — cliente
│   │   │   ├── [slug]/page.tsx             # perfil público (P1) — SERVIDOR
│   │   │   └── novo/page.tsx               # cadastrar (P1) — cliente
│   │   ├── meus-locais/                    # editar perfil (P4)
│   │   └── admin/reivindicacoes/           # aprovar (P3)
│   ├── components/
│   │   ├── ui/                     # kit — Button corrigido, Field, Alert, novo CategorySelect
│   │   ├── shell/                  # NOVO — shell de consumo (barra/trilho, D9/D11/D20)
│   │   └── venue/                  # NOVO — cartão de lista, selo de estado, fileira de ações
│   ├── lib/                        # api.ts, session.ts, hydration.ts — reusados
│   └── styles/                     # papéis de cor (D22), tipografia (D21)
└── tests/                          # componente (Vitest + axe) e e2e (Playwright)
```

**Structure Decision**: mantém a estrutura de duas aplicações desacopladas do ADR-0002 e a
organização por camadas já usada na spec 001 (`Domain` / `UseCases` / `Http` / `Ports` /
`Adapters`). O único acréscimo estrutural é a pasta `web/src/components/shell/`, que passa a
existir porque o shell deixa de ser implícito — foi o que causou o **E-016**, com o
`AccountHeader` solto no layout raiz.

### Reavaliação depois da Phase 1

Refeita em 2026-09-09, contra os artefatos gerados. **Nenhum princípio passou a ser violado
pelo desenho**, e três pontos ficaram *mais* garantidos do que estavam antes:

- **Princípio II** — as **três** rotas públicas do contrato **não aceitam nem leem**
  `Authorization`. Isso passou de intenção a propriedade verificável, e de quebra fecha o
  ADR-0003: o componente de servidor do Next as consome sem jamais tocar em token.
  `GET /locais/semelhantes` **exige sessão** e não entra nessa conta (Ícaro, 2026-09-09) —
  ela é chamada de dentro do formulário de cadastro, que é área autenticada.
- **Princípio VIII e X** — o estado de reivindicação virou **derivado do histórico**
  (`research.md` R6), não um campo que possa divergir dele. Um estado que não consegue
  contradizer o próprio histórico é mais fácil de manter correto do que dois sincronizados.
- **Princípio V** — o `POST /locais` **ignora** campo rico enviado, em vez de aceitá-lo em
  silêncio. É `validated()`, não `all()`.

**Uma dependência a menos que o esperado:** a `research.md` (R8) confirmou, abrindo
`composer.json` e `package.json`, que **nenhuma biblioteca nova é necessária**. O peso novo
no front vem só da família tipográfica e das imagens da P4 — e o Geist Mono saindo devolve
parte.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| Fase Foundational dentro de uma feature | Ver "Desvio declarado" acima | Rodada de fundação separada foi avaliada e recusada pelo Ícaro (D6); um documento normativo não passa no `spec-check` do projeto por não ter tela |
| `slug` além de `id` no local | O endereço da página é produto: compartilhável, legível e indexável (SC-002, e o SEO que decidiu o ADR-0003) | `id` numérico na URL cumpriria a função técnica, mas o `naming-conventions.md` diz que o caminho é endereço, não código — e "bora.app/locais/17" não se compartilha |

Nada mais desta feature acrescenta camada, padrão ou dependência.
