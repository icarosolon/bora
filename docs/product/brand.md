# Bora (iBar) — Marca, Nome, Cores e Logo

Status: nome de trabalho **Bora** escolhido por Ícaro em 2026-08-28 (entre opções
propostas). Registro de marca e domínio **PENDENTE** — até lá, o codinome interno do
projeto (repositório, Linear) permanece **iBar**.

## Nome

**Bora** — gíria universal no Brasil para "vamos", forte no Nordeste. Critérios pedidos:
popular ✔, simples de falar ✔ (duas sílabas, sem acento, sem ambiguidade de grafia),
significado lógico ✔ (é literalmente o convite para sair: "bora pro Caetano?"). Vira verbo
de uso no dia a dia, o que nomes descritivos não conseguem.

Alternativas consideradas: **Rolê** (exato ao domínio, mas genérico demais para marca e o
acento atrapalha domínio/busca), **Rolezeiros** (nome original do Figma; identidade de
comunidade, porém longo e nomeia o usuário, não o produto).

PENDENTE antes de qualquer material público:
- Busca de anterioridade no INPI (classes 9, 38, 41, 42/43) — "Bora" é palavra comum;
  provável precisar de forma composta ou marca mista (nome + logo).
- Disponibilidade de domínio (`bora.app.br`, `boraapp.com.br`, variações) e de @ nas redes.
- Se o registro simples falhar, candidatas: "Bora Rolê", "BoraLá", "É Bora".

## Paleta de cores — estudo

Paleta original do Ícaro:

| Token | Hex | Papel original |
|---|---|---|
| Primária dark | `#F23E02` | laranja-vermelho vibrante |
| Primary light | `#F4F1DE` | creme |
| Secundária | `#F2CC8F` | areia |
| Black | `#2B2C28` | quase-preto |
| Accent | `#FEC601` | amarelo |

**Avaliação — a base é boa.** Paleta quente, coerente com comida/noite/festa (laranja e
amarelo estimulam apetite e transmitem energia; o creme tira a frieza do fundo branco).
Harmonia análoga consistente. Três ressalvas técnicas:

1. **Contraste (WCAG AA).** Medidos:
   - `#F23E02` sobre branco ≈ 3,9:1 e sobre `#F4F1DE` ≈ 3,4:1 — **reprova para texto
     normal** (mínimo 4,5:1); aprova só para texto grande/negrito e componentes de UI.
     Texto branco sobre botão `#F23E02` ≈ 3,9:1 — usar botões grandes/bold, ou escurecer o
     tom para texto (criar variante `#C93502` p/ links e texto laranja).
   - `#FEC601` sobre claro ≈ 1,6:1 — **nunca usar como texto**; só como fundo de selo/badge
     com texto `#2B2C28` por cima (≈ 8,9:1 ✓) ou como ícone decorativo.
   - `#2B2C28` sobre `#F4F1DE` ≈ 12:1 ✓ — excelente par de texto/fundo.
   - `#F2CC8F` ≈ 1,55:1 sobre claro — apenas decorativo (cards, tags), nunca informação.
2. **Modo escuro é o caso primário, não o secundário.** O app é usado **à noite, em bar**.
   Recomendação: projetar dark-first — fundo `#1E1F1C`/`#2B2C28`, texto `#F4F1DE`, laranja
   e amarelo como acentos (sobre escuro o `#FEC601` rende ≈ 8,9:1 ✓ e o `#F23E02` ≈ 3,7:1,
   ok para elementos grandes). A paleta original funciona *melhor* no escuro do que no claro.
3. **Faltam tokens funcionais.** Definir cores semânticas (sucesso/erro/aviso — erro não
   pode ser o laranja da marca, senão todo botão parece erro) e uma escala de cinzas. A
   lista de gêneros musicais usa cores por categoria (Figma: Forró laranja, Samba amarelo,
   Sertanejo verde) — formalizar essa escala categórica com contraste verificado.

Veredito: **manter a paleta como identidade**, acrescentando variante escura do laranja
para texto, tokens semânticos, escala de cinzas e diretriz dark-first. Nada disso muda a
cara da marca; só a torna utilizável com acessibilidade.

## Logo — avaliação

Logo atual (splash do Figma): **pin de localização com recorte de nota musical** formando
um "d", em `#F23E02` com glow.

- **O conceito é forte e continua válido com o nome novo**: lugar (pin) + música (nota) é
  exatamente o produto. Não depende da letra inicial do nome — lê-se como símbolo, não como
  letra "d" (ainda assim, testar leitura com gente de fora: se todos lerem "d", ajustar o
  recorte para nota mais explícita).
- **Modernizar a execução**: remover o glow (efeito datado e ilegível em fundo claro),
  gerar versão flat, versões monocromáticas (escura p/ fundo claro, creme p/ fundo escuro)
  e testar como favicon/ícone em 16–48 px — o recorte interno some em tamanho pequeno se o
  traço for fino.
- Wordmark "Bora" ao lado do símbolo; tipografia arredondada e geométrica como a usada nas
  telas (Poppins ou similar) conversa bem com o tom do produto.

PENDENTE: redesenho da identidade (layout do Figma foi declarado ultrapassado pelo Ícaro)
— o símbolo pin+nota é o que vale a pena carregar adiante.
