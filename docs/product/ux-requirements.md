# Bora — Requisitos de UX e Acessibilidade

Status: vinculante (Constituição, Princípio XII). Ratificado por Ícaro em 2026-08-28.
O design de telas do Figma original está **aposentado** — não usar como referência de
layout; fica em `docs/product/design/figma/` só como registro histórico da ideia.

## Premissa

O Bora atende **todos os perfis de público** — do jovem que resolve tudo pelo celular ao
idoso que usa com dificuldade (menor público, mas público). O produto é de uso ocasional:
ninguém aprende a usá-lo; **cada tela se explica sozinha ou falhou**. Toda spec de feature
referencia este documento nos critérios de aceite da tela.

## Requisitos vinculantes (valem para toda tela)

### Simplicidade
- A ação principal de cada tela é **uma**, óbvia, e resolve-se com um toque.
- Linguagem simples e direta, em português do dia a dia — sem jargão técnico, sem
  estrangeirismo evitável ("Salvar", não "Bookmark").
- Navegação rasa: qualquer conteúdo importante a no máximo ~3 toques da home; o caminho de
  volta é sempre visível.
- Nada de gesto obscuro como único caminho (long-press, swipe escondido): todo gesto tem
  alternativa visível de botão.
- Formulários mínimos: pedir só o necessário, um assunto por etapa, erros apontados no
  campo e em linguagem humana ("Digite o telefone com DDD", não "campo inválido").

### Legibilidade e toque (foco idosos — beneficia todo mundo)
- Fonte base ≥ 16px; a interface respeita o ajuste de tamanho de fonte do aparelho/navegador.
- Contraste mínimo WCAG AA em todo texto e ícone informativo (pares aprovados em
  `brand.md`; o laranja da marca não é cor de texto).
- Alvos de toque ≥ 44×44px com espaçamento que evite toque errado.
- Ícone nunca sozinho para ação importante: ícone + rótulo de texto.
- Informação nunca transmitida só por cor.

### Feedback e confiança
- Toda ação responde na hora: estado de carregando, confirmação de sucesso, erro dizendo
  **o que fazer** — nunca silêncio, nunca código de erro cru.
- Ação destrutiva ou de efeito público (publicar evento, cancelar) pede confirmação clara
  e diz a consequência.
- Estado vazio ensina ("Você ainda não salvou nenhum local. Toque no ♥ de um local para
  salvá-lo aqui"), nunca tela em branco.

### Acessibilidade técnica
- HTML semântico; navegável por teclado; rótulos/`aria` corretos para leitores de tela;
  foco visível.
- Compatível com zoom de 200% sem quebra de layout.
- Funciona de forma aceitável em aparelho modesto e rede lenta (3G): páginas leves,
  imagens otimizadas, conteúdo essencial primeiro.
- Referência de conformidade: WCAG 2.1 nível AA como alvo mínimo.

### Interatividade
- A interface é **interativa e viva**: resposta visual imediata ao toque, transições
  curtas que orientam (nunca enfeite que atrase), atualização sem recarregar a página.
- Animação nunca é obrigatória para entender o conteúdo e respeita `prefers-reduced-motion`.

## Como isso entra no fluxo

- Toda spec de feature inclui, nos critérios de aceite da tela, os itens deste documento
  que se aplicam — e o `spec-check` reprova spec de feature com tela que não os referencie.
- A validação visual do Ícaro (Princípio XI) é também o teste de "essa tela se explica
  sozinha?". Reprovou, volta.
- PENDENTE: teste informal com usuário real de baixo letramento digital (ex.: pedir a um
  idoso para achar um evento e traçar a rota) antes do lançamento da Fase 1 — registrar no
  backlog.
