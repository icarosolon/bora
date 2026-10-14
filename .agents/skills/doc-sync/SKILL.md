---
name: doc-sync
description: Executa o ritual de fim de sessão do projeto — garante que spec, CHANGELOG, backlog, error-log, catálogo de regras e o quadro do Linear estão atualizados com o que mudou, e cria o commit em Conventional Commits (sem push). Use ao terminar uma tarefa, antes de commitar, quando o usuário disser "vamos fechar/encerrar", "atualiza a doc" ou "gera o commit".
disable-model-invocation: true
---

# doc-sync

Fecha a sessão garantindo que código e documentação saem juntos. Regra do projeto: toda
alteração ou criação atualiza a documentação no mesmo commit.

## Quando usar
Invocada manualmente (`/doc-sync`) ao fim de uma tarefa/sessão, antes do commit.

## Passos
Com base no que mudou nesta sessão, verifique e atualize:
1. **Spec** — status atualizado (rascunho → aprovada → implementada) na spec trabalhada.
2. **CHANGELOG.md** — entrada no formato Keep a Changelog (`Added`/`Changed`/`Fixed`…),
   referenciando o ID da spec.
3. **backlog** (`docs/logs/backlog.md`) — status das tarefas (o que avançou, o que ficou
   pendente e por quê).
4. **error-log** (`docs/logs/error-log.md`) — registre erro novo como `E-NNN` com sintoma,
   causa, resolução e status.
5. **catálogo de regras** (`docs/domain/`) — se alguma regra foi criada/alterada, atualize
   o catálogo e as specs que a referenciam.
6. **data-model / api** — se o modelo ou o contrato mudou, atualize os arquivos.
   **Compare CAMPOS, não só rotas.** Contrato com as 14 rotas certas e um campo de resposta
   faltando passa em qualquer conferência por rota — foi assim que `signs_in_with` ficou
   fora do contrato enquanto a tela decidia com ele (E-019, 2026-09-02).
7. **Linear — o quadro conta a mesma história que o repositório?** Não basta escrever a
   decisão no catálogo: enquanto a issue não fecha, o quadro segue dizendo que a decisão
   está pendente. Para **cada regra decidida ou alterada nesta sessão**:
   - feche a issue correspondente e **remova o rótulo `decisao-pendente`**;
   - **tire a linha** da tabela "Decisões pendentes" do `backlog.md`;
   - **crie issue para a pendência que a decisão gerou** — decidir costuma abrir uma
     pergunta nova, e ela nasce sem dono;
   - registre a sincronização em `docs/logs/linear-import.md`.

   **Isto já falhou duas vezes**, e nas duas a decisão tinha sido tomada em sessão paralela:
   `RN-PLAT-002` (sincronização de 2026-08-29→09-01) e `RN-DESC-003` (2026-09-02, em que a
   BORA-9 seguia em Backlog com o rótulo `decisao-pendente` enquanto
   `docs/domain/descoberta.md` já registrava a decisão). **Sessão paralela é o caso comum,
   não a exceção:** confira o quadro contra o catálogo mesmo quando a decisão não foi sua.

Se algo não puder ser determinado com certeza, **pergunte** em vez de assumir.

## Commit
Depois de aplicar as atualizações, **faça o commit**. O push é sempre manual do Ícaro —
nunca execute `git push`.

8. **Revise antes de commitar** — rode `git status --short` e `git diff`. Se houver
   alteração que **não** pertence a esta sessão, não a inclua: faça stage explícito dos
   arquivos da tarefa (`git add <caminhos>`), nunca `git add -A` às cegas. Na dúvida sobre
   um arquivo, **pergunte**.
9. **Mostre ao Ícaro** a lista de arquivos e a mensagem antes de executar o commit.
10. **Commite** seguindo a convenção do repositório:
   - Conventional Commits (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`).
   - Subject em **ASCII, sem acentos**, no imperativo, até ~72 caracteres.
   - Corpo explicando **o quê e por quê**, com o ID da spec e as `RN-<CTX>-NNN` tocadas.
   - Trailer `Co-Authored-By: Codex Opus 5 <noreply@anthropic.com>`.
   - Nunca `--no-verify`; se um hook falhar, investigue a causa em vez de contornar.

Ex.:
```
feat: cadastro de local com categorias

Implementa spec 0002. Atualiza CHANGELOG, backlog. Sem regra nova.

Co-Authored-By: Codex Opus 5 <noreply@anthropic.com>
```

## Saída
Confirme o hash do commit criado, lembre que o **push fica pendente** para o Ícaro fazer
manualmente, diga o que mudou no Linear (issues fechadas e abertas), e liste em uma linha o
que ficou pendente para a próxima sessão, se houver.
