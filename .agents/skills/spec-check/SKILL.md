---
name: spec-check
description: Valida uma spec do Spec Kit contra o template do projeto (.specify/templates/spec-template.md) e diz se está pronta para /plan. Use sempre que uma spec for escrita ou revisada, ou quando o usuário pedir para avançar de /specify para /plan. Dispare mesmo sem pedido explícito.
---

# spec-check

Portão de completude da spec, adaptado ao Spec Kit.

## Passos
1. Leia `.specify/templates/spec-template.md` (as seções obrigatórias).
2. Leia a spec da feature em `specs/<NNN-feature>/spec.md`.
3. Leia `docs/product/ux-requirements.md` — é vinculante (Princípio XII) e a checagem de
   tela abaixo é feita **contra ele**, não de memória.
4. Classifique cada lacuna por severidade: Bloqueante (critério de aceite ausente, regra
   de negócio PENDENTE/indefinida, escopo/limites vazios, cenários de teste ausentes ou
   só de caminho feliz, `RN-<CTX>-NNN` referenciada sem teste que a exercite, princípio
   NON-NEGOTIABLE tocado sem teste que prove o bloqueio), Aviso (seção rasa), OK.
5. Verifique o Princípio IX explicitamente: a spec declara caminhos de erro e limites?
   Se você identificar cenário de falha previsível que a spec não cobre, levante-o como
   Bloqueante — mesmo que o usuário não tenha mencionado.
6. Verifique a seção **"Tela e Experiência"** (Princípios XI e XII). Cada item abaixo
   ausente ou vazio é **Bloqueante**:
   - **Tela declarada:** a feature especifica a(s) tela(s) entregues e a ação principal de
     cada uma. Feature "só de backend" não existe neste projeto (Princípio XI).
   - **Referência explícita** a `docs/product/ux-requirements.md` nos critérios de aceite.
   - **Comportamento no celular:** a spec diz como a tela se organiza a **360px** — o
     celular é o dispositivo principal. Spec que só descreve o desktop reprova.
   - **Ação principal ao alcance do polegar** declarada.
   - **Estados obrigatórios** presentes: carregando, vazio (que ensina), erro (em
     linguagem humana, dizendo o que fazer) e sucesso.
   - **Acessibilidade:** alvo de toque ≥ 44px, contraste AA, ícone com rótulo, foco
     visível, navegação por teclado e **nada dependente de `hover`**.
   - **Testes de tela nas duas larguras obrigatórias — 360 e 1280** — mais verificação
     automatizada de acessibilidade (Princípio IX).
7. Seja proporcional — não trave por detalhe cosmético. Mas os itens dos passos 5 e 6 não
   são cosméticos: são os princípios NON-NEGOTIABLE do projeto. Não os rebaixe a Aviso
   "para não travar o usuário"; se falta, é Bloqueante e o veredito é NÃO.

## Saída
Checklist por severidade + veredito claro: "Pronta para /plan? SIM / NÃO". Se houver
qualquer Bloqueante, o veredito é NÃO; liste como perguntas e peça ao usuário para
resolver antes de avançar. Não prossiga com Bloqueante em aberto. Quem aprova é o usuário.
