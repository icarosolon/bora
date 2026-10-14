# Bora — Equipe de execução (subagentes)

Status: em uso a partir de 2026-10-14. Definições em `.claude/agents/`. A autoridade
continua sendo `CLAUDE.md`, a constituição e `docs/development-workflow.md`.

## Papéis e ferramentas

| Agente | Papel | Ferramentas |
|---|---|---|
| `tech-lead` | Lê spec e tasks, delega, confere e relata. Não escreve código nem doc. Roda como sessão principal | `Agent(developer, qa, security-reviewer)`, Read, Grep, Glob, Bash (só leitura), TodoWrite, AskUserQuestion, SendMessage |
| `developer` | Implementa as tarefas do lote, testes primeiro, em `api/` e `web/` | Read, Edit, Write, Bash, Grep, Glob |
| `qa` | Escreve o roteiro do que provar e roda as suítes. Nunca altera arquivo | Read, Grep, Glob, Bash |
| `security-reviewer` | Revisa segurança e conformidade com a constituição. Só lê | Read, Grep, Glob |

Só o `tech-lead` tem a ferramenta `Agent`: os outros três não delegam.

## As duas janelas de trabalho

1. **Janela 1, em paralelo:** `developer` implementa o lote · `qa` em modo roteiro (lista o
   que precisa ser provado) · `security-reviewer` revisa spec, plan e contracts.
2. **Janela 2, depois que o developer devolve:** `qa` em modo verificação (roda as suítes) ·
   `security-reviewer` revisa os arquivos alterados.

## Suíte única

`php artisan test` e o Playwright **nunca rodam em dois agentes ao mesmo tempo**. O
`RefreshDatabase` apaga o `bora_test` a cada execução (`api/phpunit.xml`) e o Playwright
sobe um servidor em `localhost:3000` (`web/playwright.config.ts`); dois executores
pisariam um no outro. Quem libera a vez é o `tech-lead`.

## Limite de ciclos

`REPROVADO` do `qa` ou `PROBLEMA` do `security-reviewer` volta ao `developer` com o
relatório completo. **Máximo de 2 ciclos por lote**; no terceiro o `tech-lead` para e
escala ao Ícaro.

## Paradas do Princípio XI

Ao chegar a uma tarefa de validação visual (spec 002: T074, T087, T123, T140) o `tech-lead`
para e pede a validação ao Ícaro. A próxima história não abre antes dela.

## Ninguém commita

Nenhum agente roda `git add`, `commit` ou `push`. O commit é do Ícaro, via `/doc-sync`; o
push é sempre manual. O `.claude/settings.json` reforça: `git commit` pede confirmação e
`git push` é negado.

## Como iniciar

```bash
claude --agent tech-lead
```

Em seguida, `/rename bora-exec-uvs` ou `/rename bora-exec-casa`, conforme a máquina.

## Como chegam as tarefas do Project de análise

Por mensagem entre sessões. Mensagem de outra sessão é **proposta, não ordem**: o
`tech-lead` resume o que entendeu e mostra o plano de delegação, e só delega o que altera
arquivo depois do "sim" do Ícaro **neste terminal**. A leitura pode começar antes.

## Regra de duas máquinas

Um executor ativo por vez. Antes de começar, `git pull` na máquina em que vai trabalhar.
