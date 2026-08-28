---
name: domain-rule
description: Cria ou atualiza uma regra de negócio transversal no catálogo do projeto (docs/domain/<contexto>.md) com ID RN-<CTX>-NNN, e ajusta as specs que a referenciam. Use quando surgir uma regra usada por mais de uma funcionalidade, quando o usuário disser "isso é uma regra de negócio", "adiciona no catálogo", ou quando uma spec precisar referenciar uma regra transversal.
disable-model-invocation: true
---

# domain-rule

Mantém as regras transversais definidas **uma única vez**, com ID, para as specs
referenciarem em vez de reescreverem (evita versões divergentes da mesma regra).

## Quando usar
Invocada manualmente (`/domain-rule`) quando uma regra atravessa mais de uma spec (ex.:
regras de evento, avaliação, personalização).

## Passos
1. Identifique o **contexto** (CTX) da regra. Catálogos existentes: PLAT, LOCAL, ART,
   EVENTO, AVAL, DESC, CONTA.
2. Abra `docs/domain/<contexto>.md` (crie se não existir) e atribua o próximo
   `RN-<CTX>-NNN`.
3. Escreva a regra **uma vez**, de forma clara e verificável.
4. **Política vs parâmetro:** se a regra for configurável (ex.: limite de eventos do plano
   grátis, pesos da personalização), deixe explícito que a **lógica** vive no domínio e o
   **valor** é dado — nunca hardcoded.
5. Encontre as specs que dependem dessa regra e garanta que elas **referenciam o ID**, não
   reescrevem o texto.
6. **Nunca invente a regra.** Se ela ainda não foi definida pelo negócio, registre como
   `PENDENTE` e sinalize que precisa ser confirmada (regras de negócio → com o Ícaro).

## Saída
Mostre a regra criada/atualizada com seu ID e liste as specs que passam a referenciá-la (ou
que precisam ser ajustadas). Lembre o usuário de rodar `/doc-sync` antes do commit.
