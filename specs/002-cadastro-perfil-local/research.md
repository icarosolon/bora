# Phase 0 — Pesquisa: Cadastro e Perfil de Estabelecimento

**Spec**: [spec.md](./spec.md) · **Plano**: [plan.md](./plan.md) · **Data**: 2026-09-09

Cada item traz **o que ficou decidido**, **por quê** e **o que foi descartado**. Onde algo
foi verificado abrindo arquivo, está dito qual. Onde não foi possível verificar, está dito
que **falta medir** — nunca a versão otimista (Guardrails do `CLAUDE.md`).

---

## R1 — `next/font` e o pulo de layout na troca da fonte — **RESOLVIDO**

**Estava pendente na D21** e foi resolvido **lendo o pacote instalado**, não de memória.

**Verificado** em `web/node_modules/next` (versão **16.3.3**, confirmada pelo
`package.json` do pacote):

| Achado | Onde |
|---|---|
| `display` tem valor padrão **`'swap'`** | `.../@next/font/dist/google/validate-google-font-function-call.js` |
| `adjustFontFallback` tem valor padrão **`true`** | idem |
| Com ele ligado, o loader chama `getFallbackFontOverrideMetrics(fontFamily)` e emite uma família de reserva com métricas ajustadas | `.../google/loader.js` e `.../loaders/next-font-loader/postcss-next-font.js` |
| A tabela de métricas existe embarcada | `next/dist/server/capsize-font-metrics.json` |

**Decisão**: usar `next/font` com os padrões. O texto aparece imediatamente na família de
reserva **com métricas ajustadas**, e a troca não desloca o layout.

**Ressalva honesta**: o ajuste elimina o **deslocamento**, não a **troca visível de
desenho** — a pessoa ainda vê a letra mudar. Isso é aceitável e é o comportamento correto:
o conteúdo fica legível desde o primeiro quadro, que é o que a régua pede em 3G.

**Previsão minha contrariada, registrada de propósito**: eu ia anotar que essa garantia
dependeria de a família vir do Google Fonts, e que uma fonte licenciada pelo redesenho
perderia o benefício. **Está errado.** O `next/font/local` calcula as métricas de reserva a
partir do **próprio arquivo da fonte**
(`getFallbackMetricsFromFontFile`, em `.../local/loader.js`), e só desliga se
`adjustFontFallback: false` for passado explicitamente. **A escolha da família pelo
redesenho (BORA-25) não é limitada por isto.**

---

## R2 — Orçamento de caracteres dos rótulos — **NÃO RESOLVIDO, vira tarefa**

Segue pendente e **não deve ser assumido**. Os números da D12 (~8 caracteres a 100%, ~4 a
200%) são **estimativa** feita com avanço médio de 0,5em por caractere; nenhuma medição foi
feita.

**Por que não dá para resolver agora**: depende de (a) a família escolhida ou um substituto
de métrica equivalente, e (b) renderizar de verdade num navegador. As duas coisas só existem
dentro da fase Foundational.

**Vira tarefa da fase Foundational**, com critério de pronto explícito: medir os cinco
rótulos da barra (`Hoje`, `Buscar`, `Salvos`, `Dividir`, `Conta`) a 360px, sob **zoom do
navegador a 200%** e sob **fonte do sistema ampliada** — que são mecanismos diferentes
(D12). O resultado define onde a barra quebra em duas linhas.

---

## R3 — Identificador da página pública: `slug` — decidido

**Decisão**: `/locais/{slug}`, com `slug` derivado do nome e **desambiguado pelo bairro**
quando colidir (`bar-do-ze`, `bar-do-ze-centro`). O `id` continua existindo internamente.

**Por quê**: o `naming-conventions.md` é explícito — *"o caminho é endereço: a pessoa lê na
barra, copia, compartilha, e ele aparece em busca. É produto, não código."* A SC-002 exige
um endereço compartilhável, e o SEO do catálogo público foi o critério que decidiu o
ADR-0003.

**Descartado**: `id` numérico (`/locais/17`) — cumpriria a função técnica e não se
compartilha. `slug` sem desambiguação — quebra na primeira rede, e a `RN-LOCAL-004` diz que
unidades são perfis separados.

**Consequência**: o `slug` é **imutável** depois de criado. Renomear o local não muda o
endereço, senão todo link já compartilhado morre — e o Princípio X (preservar histórico) vale
também para endereço público.

---

## R4 — Critério de "local parecido" para o aviso de duplicata (FR-020) — decidido

**Decisão**: comparação por **nome normalizado + mesmo bairro**. Normalizar significa
minúsculas, sem acento, sem pontuação e sem termos genéricos de início (`bar`, `bar do`,
`restaurante`). Se houver correspondência, o aviso mostra o que encontrou; **a decisão é de
quem cadastra**, nunca automática.

**Por quê**: a spec já assume que o critério **pode ser grosseiro e ainda pegar a maioria**.
O objetivo é reduzir volume, não eliminar duplicata — a `RN-LOCAL-004` mantém a unificação
manual como rede.

**Descartado**: distância de edição / similaridade fonética como critério principal —
sofisticação sem dado que a justifique, e nada foi medido sobre como os nomes realmente
colidem em Juazeiro e Petrolina (BORA-47 segue por fazer). Fica como caminho de melhoria
**se** o volume de duplicata mostrar que precisa.

**Nada de novo entra no projeto por isto**: normalização e comparação são consulta de banco
com coluna auxiliar, não biblioteca.

---

## R5 — Como o "Como chegar" monta o link — decidido

**Decisão**: link para o app de mapas do aparelho, montado com o **endereço em texto**,
conforme a `RN-DESC-004` já decidiu para a Fase 1. **Sem geocodificação, sem coordenadas,
sem provedor.**

**Por quê**: mantém a feature **livre da BORA-8** (provedor de mapas e seu custo), que segue
PENDENTE. Endereço em texto é suficiente para o app de mapas resolver.

**Consequência para o modelo de dados**: o endereço é guardado **estruturado**
(CEP, logradouro, número, complemento, bairro, cidade, UF) e não como texto livre — porque o
**bairro** é exibido em listagem (`RN-LOCAL-004`) e a cidade alimenta a descoberta depois.
Coordenadas ficam como **campo opcional vazio**, para o dia em que a BORA-8 for decidida sem
exigir migração de tabela.

**Descartado**: guardar só o endereço em uma linha — impediria mostrar o bairro sem análise
de texto, que é frágil.

---

## R6 — Onde vive o estado de reivindicação — decidido

**Decisão**: o estado (`não reivindicado` / `reivindicado`) é **derivado**, não um campo
solto: existe reivindicação aprovada ⇒ o local é reivindicado. O `Venue` guarda a referência
ao vínculo de gestão ativo para leitura barata; a verdade é a tabela de pedidos.

**Por quê**: campo solto e histórico de pedidos podem divergir, e o Princípio VIII depende
justamente do histórico para arbitrar disputa. Um estado que não pode contradizer o próprio
histórico é mais simples de manter correto do que dois que precisam ser sincronizados.

**Descartado**: `enum` no `Venue` como fonte da verdade — barato de ler, fácil de
dessincronizar.

---

## R7 — Categorias como dado (`RN-LOCAL-002`) — decidido

**Decisão**: tabela própria, semeada com **bar, restaurante, casa de shows**, e relação
**N:N** com o local. Sem limite de categorias.

**Por quê**: a `RN-LOCAL-002` diz que a lista é **gerida pela plataforma, nunca hardcoded**;
e a spec exige teste provando que **acrescentar categoria não exige mudar código**.

**Descartado**: `enum` de banco ou constante em PHP — as duas quebrariam o teste acima.

---

## R8 — Nenhuma dependência nova — verificado

**Verificado** no `api/composer.json` e no `web/package.json`: tudo que a feature precisa já
está instalado. Sanctum, permission, activitylog e scramble na API; Tailwind 4, Base UI,
`cva` e `lucide-react` no front.

**Consequência para 3G**: o peso novo do front vem de **duas coisas apenas** — a família
tipográfica (D21, 30–60 KB estimados) e as imagens do perfil rico, que são da P4. E o
**Geist Mono sai**, o que devolve peso.

**Descartado**: biblioteca de máscara de telefone, de upload, ou de seleção múltipla —
"peso de biblioteca é custo real" (premissa do projeto), e os três casos são resolvíveis com
o que já existe.

---

## Resumo do que continua em aberto ao fim da Phase 0

| Item | Estado | Onde é resolvido |
|---|---|---|
| Orçamento de caracteres dos rótulos (R2) | **falta medir** | Tarefa da fase Foundational |
| Nome da família tipográfica | depende do redesenho | BORA-25 |
| Valores dos papéis de cor | depende do redesenho | BORA-25 |
| Critério de desempate entre reivindicações | fora do escopo por decisão | BORA-50 |
| Método automático de verificação | fora do escopo por decisão | BORA-49 |

**Nenhum destes bloqueia a Phase 1.** Os dois primeiros são valores, não estrutura — e a
estrutura é o que o `design-system.md` fixou justamente para eles não bloquearem.
