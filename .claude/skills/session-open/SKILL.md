---
name: session-open
description: Executa o ritual de início de sessão do projeto — levanta o estado do repositório (branch, árvore limpa, feature corrente) e a continuidade da sessão anterior (próximo passo do backlog, [Unreleased], erro mais recente, sincronização do Linear). Use ao abrir uma sessão, quando o usuário disser "vamos começar", "abrir sessão", "me põe em dia", ou antes de receber a primeira demanda.
disable-model-invocation: true
---

# session-open

Responde uma pergunta só: **onde paramos?**

Contexto e método já chegam pelo `CLAUDE.md`, que carrega sozinho. O que não chega é o
estado — e ele está espalhado em arquivos grandes demais para ler inteiros (`CHANGELOG.md`
62 KB, `error-log.md` 39 KB, `backlog.md` 38 KB). Esta skill vai buscar só os trechos que
dizem em que ponto o projeto parou.

É o par do `/doc-sync`: aquele fecha, este abre.

**Read-only.** Nenhum comando abaixo escreve em disco, banco, rede ou estado do Git.

## Quando usar
Invocada manualmente (`/session-open`), **antes** de receber a demanda.

## Etapa 1 — Estado

```
git status --short --branch
git log --oneline -5
```

- A branch vem daí; **nunca se presume**.
- Ler `.specify/feature.json` (feature corrente) e listar o diretório
  `specs/NNN-*/` correspondente: **quais artefatos existem e quais faltam**
  (`spec.md`, `plan.md`, `tasks.md`). Fluxo com `plan.md` e sem `tasks.md` significa que
  `/speckit-tasks` não rodou.

Árvore suja: **pare e pergunte** o que fazer com o pendente. Não commitar, não descartar,
não dar `stash`.

## Etapa 2 — Continuidade

Ler **o trecho**, não o arquivo (`sed -n`, `grep`, `head`):

- `docs/logs/backlog.md` — seção **"Próximo passo"** (ao final) e a tabela de **decisões
  pendentes**.
- `CHANGELOG.md` — bloco **`[Unreleased]`**.
- `docs/logs/error-log.md` — o **`E-NNN` mais recente**, no topo.
- `docs/logs/linear-import.md` — a última **"Sincronização de ..."**.

## Etapa 3 — Leitura sob demanda

Não ler adiantado. Quando a tarefa chegar: regra em `docs/domain/<contexto>.md`;
arquitetura em `docs/adr/` e `docs/architecture/api-conventions.md`; a feature em
`specs/NNN-*/`; e só então o arquivo de código. O modelo real está em
`specs/NNN-*/data-model.md` — `docs/architecture/data-model.md` é rascunho conceitual.

## Saída

Cinco linhas, não um relatório:

1. **Branch e árvore** — limpa ou os arquivos pendentes.
2. **Feature corrente** — qual é e qual artefato do Spec Kit falta.
3. **Próximo passo** — o que o backlog diz que era para acontecer agora.
4. **Aberto** — `E-NNN` não resolvido, decisões pendentes, divergência entre Linear e
   catálogo.
5. **Aguardando a demanda.**

Se algo não puder ser determinado com certeza, **pergunte** em vez de assumir.
