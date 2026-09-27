---
name: screen-help
description: Cria o arquivo de ajuda de tela do projeto (docs/product/user-guide/screens/<tela>.md), que alimenta tanto a "ajuda de tela" dentro do app quanto o site de documentação. Use quando o usuário criar uma tela nova, pedir a ajuda/orientação de uma tela, os pré-requisitos de uma tela, ou o conteúdo do painel de ajuda.
disable-model-invocation: true
---

# screen-help

Cria o conteúdo de ajuda de uma tela como **fonte única**: o mesmo arquivo abastece o painel
de ajuda dentro do app e a documentação visualizável, então a ajuda nunca descola do código.

## Quando usar
Invocada manualmente (`/screen-help <tela>`) ao criar/documentar uma tela.

## Passos
1. Descubra qual é a tela (pergunte se não estiver claro) e o papel que a acessa.
2. Crie `docs/product/user-guide/screens/<tela>.md` com frontmatter:
   ```
   ---
   titulo: <nome da tela>
   papeis_que_acessam: [<ex.: recepção, gerente da clínica>]
   pre_requisitos:
     - <o que precisa existir/estar preenchido antes>
   ---
   ```
3. No corpo, escreva as **orientações da tela** em linguagem de usuário final: o que a tela
   faz, como usar, o que cada ação principal significa, erros comuns e o que fazer.
4. Não invente pré-requisitos ou comportamentos que não existem na spec da tela — se algo
   for incerto, marque `PENDENTE` e pergunte.

## Saída
O arquivo criado. Lembre o usuário de que esse conteúdo é consumido pelo app (ajuda de tela)
e pelo site de docs, então deve ser preciso e escrito para quem vai usar, não para quem
desenvolve.
