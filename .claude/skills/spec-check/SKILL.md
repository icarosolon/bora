---
name: spec-check
description: Valida uma spec do Spec Kit contra o template do projeto (.specify/templates/spec-template.md) e diz se está pronta para /plan. Use sempre que uma spec for escrita ou revisada, ou quando o usuário pedir para avançar de /specify para /plan. Dispare mesmo sem pedido explícito.
---

# spec-check

Portão de completude da spec, adaptado ao Spec Kit.

## Passos
1. Leia `.specify/templates/spec-template.md` (as seções obrigatórias).
2. Leia a spec da feature em `specs/<NNN-feature>/spec.md`.
3. Classifique cada lacuna por severidade: Bloqueante (critério de aceite ausente, regra
   de negócio PENDENTE/indefinida, escopo/limites vazios, cenários de teste ausentes ou
   só de caminho feliz, `RN-<CTX>-NNN` referenciada sem teste que a exercite, princípio
   NON-NEGOTIABLE tocado sem teste que prove o bloqueio), Aviso (seção rasa), OK.
4. Verifique o Princípio IX explicitamente: a spec declara caminhos de erro e limites?
   Se você identificar cenário de falha previsível que a spec não cobre, levante-o como
   Bloqueante — mesmo que o usuário não tenha mencionado.
5. Seja proporcional — não trave por detalhe cosmético.

## Saída
Checklist por severidade + veredito claro: "Pronta para /plan? SIM / NÃO". Se houver
qualquer Bloqueante, o veredito é NÃO; liste como perguntas e peça ao usuário para
resolver antes de avançar. Não prossiga com Bloqueante em aberto. Quem aprova é o usuário.
