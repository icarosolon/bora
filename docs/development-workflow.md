# Bora — Método de Desenvolvimento

Status: ratificado (espelha o método do Nexa — as mesmas pessoas, a mesma maneira de
trabalhar; no Linear, porém, cada produto tem seu próprio time).
**Spec Kit é a espinha dorsal**; as skills próprias cobrem só as lacunas. A spec tem
**uma única fonte da verdade: o Spec Kit**.

---

## 1. Espinha dorsal — Spec Kit

- Fluxo: **constituição → `/speckit-specify` (spec) → `/speckit-plan` → `/speckit-tasks` → implementação**.
- Specs vivem em `specs/NNN-feature/{spec,plan,tasks}.md` (convenção do Spec Kit).
  **Não duplicar spec em `docs/`.**
- Constituição em `.specify/memory/constitution.md` — vinculante; muda **só por emenda**.

## 2. Skills complementares (o que o Spec Kit não cobre)

| Skill | Papel | Invocação |
|---|---|---|
| `spec-check` | Portão de completude, adaptado ao template do Spec Kit | **auto** + manual |
| `domain-rule` | Catálogo de regras transversais `RN-<CTX>-NNN` | manual |
| `adr-new` | ADRs em `docs/adr/` | manual |
| `screen-help` | Ajuda de tela (fonte única app + docs) | manual |
| `doc-sync` | Ritual de fim de sessão (CHANGELOG, backlog, error-log, catálogo, **quadro do Linear**) + commit (**sem push**) | manual |

## 3. Ciclo de trabalho

```
constituição / emenda
      → /speckit-specify (escreve a spec — inclui a tela e seus critérios de UX)
      → /spec-check      (portão: pronta para implementar? sim/não)
      → /speckit-plan    (plano técnico — API e frontend)
      → /speckit-tasks   (quebra em tarefas)
      → implementar      (API-first DENTRO da feature: API + testes → tela + testes)
      → validação visual (Ícaro aprova a tela — Princípio XI)
      → /doc-sync        (atualiza docs + commita; push é manual)
```
Nenhum código antes de spec aprovada pelo portão. O portão (`.claude/skills/spec-check/`)
reprova spec:

- sem cenários de erro e limites; sem teste para cada `RN-<CTX>-NNN` referenciada; sem
  teste que prove o bloqueio de todo princípio NON-NEGOTIABLE tocado (Princípio IX);
- sem a seção **"Tela e Experiência"** preenchida (Princípios XI e XII): tela e ação
  principal declaradas, referência a `docs/product/ux-requirements.md`, **comportamento a
  360px** (o celular é o dispositivo principal), ação principal ao alcance do polegar,
  estados de carregando/vazio/erro/sucesso, itens de acessibilidade, e **testes de tela em
  360 e 1280**.

Esses critérios estão escritos na skill `spec-check` e na seção obrigatória do
`spec-template.md` — não dependem de alguém lembrar deles na hora.

### Definition of Done da feature (Princípio XI — inegociável)

Uma feature só é dada como pronta quando **tudo** abaixo vale:

1. **API-first:** a API da feature está completa, funcional e **documentada**, pronta para
   ser consumida pelo futuro app mobile sem mudança estrutural.
2. **Front desacoplado:** a tela está **100% implementada** no `web/`, consumindo
   exclusivamente a API pública — nada é "pronto só no backend".
3. **Usabilidade:** a tela atende `docs/product/ux-requirements.md` (simples, interativa,
   acessível a todos os perfis, incluindo idosos).
4. **Testes:** testes automatizados de **backend e frontend** executados e aprovados.
5. **Validação visual:** a tela foi apresentada ao Ícaro e **validada por ele**. A próxima
   feature só começa depois dessa validação.

## 4. Rastreio no Linear

O trabalho é rastreado no **Linear**, no **time `Bora` (key `BORA`)**, projeto **Bora**.
O time é próprio do produto — as issues saem como `BORA-nn`, o que identifica o projeto no
próprio ID. Convenções:

- Cada spec do Spec Kit vira um conjunto de issues no Linear a partir do `tasks.md`
  (título `T001: <descrição>`, como no padrão do Nexa; deduplicar por ID antes de criar).
- Decisões pendentes que bloqueiam spec (backlog) viram issues rotuladas `decisão`.
- Estrutura inicial e itens a importar: `docs/logs/linear-import.md`.

## 5. Estrutura de documentação

```
api/                                     # Laravel — API REST pública (ADR-0002)
web/                                     # frontend desacoplado — Next.js + React (ADR-0003)
specs/NNN-feature/{spec,plan,tasks}.md   # Spec Kit — fonte única da spec (API + tela)
.specify/memory/constitution.md          # Spec Kit — constituição
docs/
├─ development-workflow.md               # este documento
├─ product/
│  ├─ vision.md                          # visão de produto (aprovada)
│  ├─ monetization.md                    # modelo de negócio
│  ├─ brand.md                           # nome, cores, logo
│  ├─ ux-requirements.md                 # requisitos vinculantes de UX/acessibilidade
│  ├─ design/figma/                      # telas do Figma antigo (só registro histórico)
│  └─ user-guide/screens/<tela>.md       # screen-help
├─ domain/<contexto>.md                  # domain-rule (RN-<CTX>-NNN)
├─ adr/NNNN-<slug>.md                    # adr-new
├─ architecture/
│  ├─ data-model.md
│  ├─ naming-conventions.md              # inglês no código, português na prosa e na URL
│  └─ api-conventions.md                 # aponta para a constituição
├─ agents/
│  └─ cowork-project.md                  # instruções do Project do Claude Cowork (só leitura)
└─ logs/{backlog.md, error-log.md, linear-import.md}
CHANGELOG.md                             # raiz, Keep a Changelog + SemVer
```

## 5.1 Nomenclatura (vinculante)

**Identificador em inglês, prosa em português. Única exceção: o caminho da URL.**

- **Inglês:** classes, arquivos, métodos, variáveis, constantes, chaves de configuração e
  de ambiente, tabelas, colunas, **campos do JSON da API**, chaves de erro de validação,
  chaves de storage do navegador e nomes de método de teste em PHP.
- **Português:** comentários, mensagens da API, textos de tela, rótulos e as descrições de
  teste que são string (Playwright, Vitest) — porque string é prosa, não identificador.
- **Rotas em português**, tanto na API (`/sessoes/atual`) quanto no `web/` (`/criar-conta`):
  o endereço é visível e compartilhável. O **corpo** da requisição não acompanha:
  `POST /sessoes` recebe `{ "email", "password" }`.
- Vocabulário do produto (`rolezeiro`, `rolê`) não se traduz, nem em banco.

Regra completa, com a justificativa de cada fronteira: `docs/architecture/naming-conventions.md`.

## 6. Onde ficam as regras de negócio

- **No código:** entidades (invariantes) e casos de uso. Bordas (controller/adapter/model/
  framework) não contêm regra.
- **Transversal:** definida uma vez em `docs/domain/<contexto>.md` com ID `RN-<CTX>-NNN`;
  as specs referenciam o ID, não reescrevem.
- **Configurável:** a **política** vive no domínio; o **parâmetro** é dado (nunca
  hardcoded).
- **Atualização obrigatória:** criar/alterar regra atualiza o catálogo e as specs que a
  referenciam no mesmo commit.

## 7. Guardrails e acordo de trabalho (inegociável)

- **Nunca inventar** dados, regras, números ou comportamentos. Regra ausente = `PENDENTE` +
  pergunta. Regras do negócio se confirmam com o Ícaro.
- **Na dúvida, pare e pergunte antes de seguir.**
- **Sinceridade acima de agradar:** apontar risco/erro é obrigação; postura de mentor
  (explicar o porquê e os trade-offs).
- **Local/dev nunca executa contra o banco de produção** — sandbox/local sempre.
- **Toda alteração atualiza a documentação no mesmo commit.**

## 8. Garantia de recriar do zero

O repositório recria o sistema quando existirem, juntos: constituição, specs do Spec Kit,
ADRs, catálogo de regras, `architecture/`, `product/vision.md` e o backlog. Se algum faltar
ou estiver vago, a garantia quebra ali.
