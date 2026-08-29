# ADR-0003 — Framework do frontend: Next.js + React + TypeScript

Status: aceito
Deciders: Ícaro · 2026-08-29

## Contexto

O ADR-0002 definiu que o `web/` é uma aplicação separada que consome exclusivamente a API
pública, e deixou o framework em aberto como action item. Quatro forças, todas já
ratificadas, restringem a escolha:

1. **SEO é eliminatório** (backlog, BORA-28). O catálogo público — evento, perfil de casa,
   perfil de artista, feed do dia por cidade — é o canal de aquisição mais barato de um
   produto com R$ 68 mil de marketing para 18 meses (`investment-plan.md`). Exige HTML
   renderizado no servidor.
2. **Acessibilidade e desempenho são vinculantes** (Princípio XII, `ux-requirements.md`):
   WCAG 2.1 AA, navegação por teclado, leitor de tela, zoom de 200%, e funcionamento
   aceitável em aparelho modesto e rede 3G. Empurra para pouco JavaScript e HTML semântico.
3. **O front consome só a API pública** (Princípio IV, ADR-0002). Blade, Livewire e Inertia
   estão fora por decisão constitucional, não por mérito técnico.
4. **Metade do produto é aplicação logada**: painel do estabelecimento, criação de evento,
   confirmação do artista, salvos, divisão de conta. Não é um site de conteúdo — é catálogo
   público **mais** app autenticado.

Contexto de time: **um desenvolvedor**, com experiência de front dentro do Laravel (Blade) e
de Flutter, **sem experiência prévia em React**, apoiado por IA. O orçamento prevê freelance
pontual de frontend (`investment-plan.md`).

Sobre o app mobile: a tecnologia da Fase 3 (Flutter, React Native ou PWA) está **em aberto**
— Ícaro, 2026-08-29 — e **não foi usada como critério aqui**, em nenhuma direção. A escolha
da web não pode depender dela: o Princípio IV garante que qualquer cliente consome a mesma
API, então a decisão de mobile pode e deve ser tomada na Fase 3, com informação melhor. Se
ela cair em React Native, a familiaridade acumulada com React vira um bônus desta decisão —
mas apenas de linguagem, modelo mental, tipos do contrato e cliente de API: **React Native
não reaproveita as telas de web**, porque não usa DOM. Ver o item do backlog.

## Decisão

O `web/` é construído com **Next.js (App Router) + React + TypeScript**, com esta
separação obrigatória:

- **Páginas públicas do catálogo são renderizadas no servidor** (com revalidação) — é o que
  cumpre o requisito de SEO.
- **Áreas autenticadas são renderizadas no cliente**, chamando a API com token — o que evita
  deliberadamente a combinação SSR + sessão do Sanctum.
- **Nenhuma regra de negócio vive no `web/`.** O servidor do Next apenas renderiza e
  repassa; quem decide é a API (Princípio IV — sob pena de o app mobile nascer quebrado).
- Base de UI: **Tailwind + primitivas acessíveis do ecossistema React** (Radix/React Aria),
  com verificação automatizada de acessibilidade (`axe`) nos testes de front (Princípio IX).

## Opções consideradas

Critérios: SEO · acessibilidade e peso em 3G · adequação à área logada · curva de
aprendizado para dev sem React · ecossistema e oferta de freelance · custo de operação.

1. **Next.js + React (escolhida)**
   Prós: resolve catálogo público e área logada com **um modelo mental só** — o que mais
   importa para dev solo; maior ecossistema e maior volume de material de apoio (relevante
   para quem desenvolve com IA); as melhores primitivas acessíveis disponíveis hoje em
   qualquer ecossistema, o que reduz risco no Princípio XII sem exigir especialista em a11y;
   maior oferta de freelance no Brasil, coerente com a rubrica do orçamento.
   Contras: a curva é do **Next**, não do React — Server Components, fronteira
   servidor/cliente e semântica de cache que mudou entre versões maiores; material antigo na
   web induz ao erro; exige um processo Node em produção além do PHP (afeta BORA-27).

2. **Astro + ilhas React**
   Prós: melhor desempenho em 3G de todas as opções (zero JS por padrão), o que serve
   diretamente ao Princípio XII; permite aprender React aos poucos, só nas partes
   interativas; hospedagem mais barata e simples.
   Contras: desconfortável exatamente onde o produto tem massa — painel do estabelecimento e
   criação de evento; levaria a uma ilha muito grande ou a uma segunda aplicação, ou seja,
   **dois modelos mentais para um dev só**; ecossistema e oferta de freelance menores.
   Seria a escolha se o Bora fosse apenas catálogo.

3. **React Router v7 (ex-Remix)**
   Prós: modelo mais limpo e mais próximo do padrão web que o App Router (loaders/actions,
   formulários com aprimoramento progressivo), o que combina bem com acessibilidade; SSR
   nativo; menos "mágica" de cache para um iniciante entender errado.
   Contras: ecossistema, material de apoio e oferta de freelance menores que os do Next;
   também exige processo Node.

4. **Nuxt + Vue**
   Prós: curva mais suave vindo de Blade; SSR nativo; presença forte no mundo Laravel.
   Contras: primitivas de acessibilidade inferiores às de React, o que joga mais trabalho de
   WCAG para o dev; não aproveita a transferência de Flutter → React (UI declarativa,
   componentes, estado que dispara rebuild), que era um ativo real do Ícaro.

5. **SPA pura (Vite + React, sem SSR)** — descartada de imediato: reprova no critério
   eliminatório de SEO.

6. **Flutter Web** — descartada: renderização em canvas torna o catálogo praticamente
   invisível para busca orgânica. O conhecimento de Flutter do Ícaro pode valer para o
   **app** da Fase 3 — decisão em aberto —, não para a web.

7. **Blade / Livewire / Inertia** — indisponíveis: violam o Princípio IV e o ADR-0002
   (acoplariam o front ao Laravel e deixariam o contrato REST sem cliente real).

## Confiança das afirmações deste ADR

Separado de propósito, para quem reler daqui a meses saber o que checar antes de confiar:

**Fato verificável / restrição do próprio projeto** (não depende de julgamento)
- SEO e acessibilidade como critérios eliminatórios — está escrito no backlog e na
  constituição.
- Blade/Livewire/Inertia proibidos — Princípio IV e ADR-0002.
- SPA sem SSR e Flutter Web reprovam em SEO — limitação técnica conhecida e amplamente
  documentada dessas abordagens.
- Metade do produto é área logada — decorre do escopo em `vision.md`.

**Julgamento do assistente, NÃO medido** (podem estar errados; conteste se destoarem da sua
experiência)
- "Melhores primitivas de acessibilidade do mercado" para o ecossistema React — é opinião
  técnica corrente, não benchmark. Vale checar na prática durante o spike.
- "Maior oferta de freelance no Brasil" para React — plausível pelo mercado geral, **não
  medido**, e menos ainda para Juazeiro/Petrolina especificamente.
- "Maior volume de material de apoio, inclusive para IA" — plausível, não medido.
- "Astro fica desconfortável no painel do estabelecimento" e "Nuxt/Vue tem a11y inferior" —
  avaliações minhas. A segunda é a mais frágil do documento: Vue tem bibliotecas de
  acessibilidade decentes, e a diferença para React é menor do que o texto sugere.

**A confirmar por medição, ainda não feito**
- Custo real de hospedar o processo Node (entra em BORA-27).
- Desempenho do catálogo em 3G — medir na primeira página real do spike (BORA-32).
- Se a curva do App Router é suportável para quem está começando em React — o spike existe
  justamente para responder isso, e a resposta pode derrubar esta decisão em favor de
  React Router v7.

## Consequências

**Fica mais fácil**
- Cumprir o SEO do catálogo sem trabalho extra de renderização.
- Cumprir o Princípio XII com menos risco: teclado, foco e `aria` vêm resolvidos das
  primitivas, em vez de dependerem de acerto artesanal.
- Contratar ajuda pontual de frontend e encontrar respostas — inclusive de IA.
- Uma única stack cobre catálogo e área logada, sem segunda aplicação.

**Fica mais difícil**
- Há uma curva de aprendizado real antes da primeira feature, e ela cai bem em cima da
  spec 001 (contas), que é a pior tela para aprender o framework. Mitigação decidida junto
  com este ADR: **spike descartável no M0** — página pública consumindo um `GET` simples da
  API, sem login e fora da Definition of Done, só para absorver setup, CORS e modelo de
  componentes onde errar não custa marco.
- A operação ganha um processo Node ao lado do PHP: mais uma peça para hospedar, monitorar e
  atualizar. Entra como insumo obrigatório de **BORA-27 (hospedagem)**. Custo estimado cabe
  na rubrica de infraestrutura do `investment-plan.md` — **a confirmar** quando o provedor
  for escolhido.
- Disciplina permanente contra vazamento de regra para o `web/`: é o erro que só aparece
  na Fase 3, quando o app mobile precisar da regra que ficou no site.
- Risco conhecido: autenticação com SSR (cookie de sessão do Sanctum + renderização no
  servidor) é armadilha clássica de CORS/CSRF. A separação decidida acima existe para que
  esse cenário simplesmente não ocorra; misturar as duas coisas reabre o risco.

**Quando revisitar**
- Se o catálogo público não performar em 3G mesmo depois de otimizado — aí Astro para as
  páginas públicas volta à mesa.
- Se a área logada crescer a ponto de justificar aplicação própria.
- Se a curva do App Router se mostrar cara demais no spike: React Router v7 é a alternativa
  de troca mais barata, por ser o mesmo React.

## Action items
- [ ] Spike descartável no M0 (página pública consumindo `GET` da API; sem login, sem DoD)
- [ ] Decidir hospedagem considerando o processo Node — BORA-27
- [ ] Definir na spec 001 o setup de CORS/Sanctum e o padrão de token da área logada
      (herdado do action item do ADR-0002)
- [ ] Definir a base de UI concreta (Tailwind + biblioteca de primitivas acessíveis) e o
      conjunto de testes de front (runner, Testing Library, `axe`, e2e) — na spec 001
