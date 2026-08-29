# Bora — Requisitos de UX e Acessibilidade

Status: vinculante (Constituição, Princípio XII). Ratificado por Ícaro em 2026-08-28.
O design de telas do Figma original está **aposentado** — não usar como referência de
layout; fica em `docs/product/design/figma/` só como registro histórico da ideia.

## Premissa

O Bora atende **todos os perfis de público** — do jovem que resolve tudo pelo celular ao
idoso que usa com dificuldade (menor público, mas público). **O uso é predominantemente
pelo celular**; o computador é minoria. O produto é de uso ocasional:
ninguém aprende a usá-lo; **cada tela se explica sozinha ou falhou**. Toda spec de feature
referencia este documento nos critérios de aceite da tela.

## Requisitos vinculantes (valem para toda tela)

### Dispositivo principal: o celular (mobile-first, não "responsivo")

Decisão do Ícaro (2026-08-29): **a maioria esmagadora do uso será no celular**; o
computador é minoria. Isso não é uma preferência de layout — é a ordem em que cada tela é
pensada, construída e testada.

- **Mobile-first literal no código:** o estilo base é o do celular; `media query` só existe
  para **ampliar** para telas maiores. Nunca o contrário. Tela que nasce em desktop e
  "encolhe" reprova.
- **Piso de largura: 360px.** Nenhuma tela pode ter rolagem **horizontal** nem conteúdo
  cortado a 360px de largura — é o piso de aparelho modesto que adotamos.
- **Larguras de referência para conferir toda tela:** 360 (piso), 390–430 (celular comum),
  768 (tablet), 1280 (computador).
- **Ação principal ao alcance do polegar:** a ação principal da tela fica na metade
  inferior, alcançável com uma mão. Barra de ação no topo não serve como único caminho para
  a ação principal.
- **Nada depende de `hover`.** Passar o mouse não existe no celular: toda informação ou
  ação revelada por `hover` tem caminho equivalente por toque, visível.
- **Uma coluna no celular.** Tabela larga, grade densa e layout de várias colunas se
  reorganizam em lista vertical — não viram rolagem lateral.
- **No computador a tela não é celular esticado:** o conteúdo respeita largura máxima
  legível e usa o espaço extra para mostrar mais, nunca para espalhar controles.
- **Isso vale para todas as telas, inclusive o painel do estabelecimento.** Premissa: o dono
  do bar publica evento pelo celular, no balcão, tanto quanto pelo computador. O painel
  precisa funcionar bem nos dois — mas nasce pelo celular.

**Como se verifica (Princípio IX):** todo teste de tela roda em **pelo menos duas larguras
— 360 e 1280** — e falha se houver rolagem horizontal ou elemento inacessível. A validação
visual do Ícaro (Princípio XI) é feita **primeiro no celular**; a tela reprovada no celular
não é apresentada em desktop.

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
- Em especial, o `spec-check` reprova spec cuja tela não declare o comportamento no
  **celular** e os testes nas duas larguras de referência.
- A validação visual do Ícaro (Princípio XI) é também o teste de "essa tela se explica
  sozinha?". Reprovou, volta.
- PENDENTE: teste informal com usuário real de baixo letramento digital (ex.: pedir a um
  idoso para achar um evento e traçar a rota) antes do lançamento da Fase 1 — registrar no
  backlog.
