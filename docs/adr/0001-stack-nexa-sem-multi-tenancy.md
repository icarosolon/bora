# ADR-0001 — Reuso da stack do Nexa, sem multi-tenancy

Status: aceito
Deciders: Ícaro · 2026-08-28

## Contexto

O Bora será construído pelo mesmo time que desenvolve o Nexa, que já tem stack
ratificada (PHP 8.4+/Laravel 13/MySQL/Redis, Sanctum, pacotes spatie) e método de trabalho
consolidado (Spec Kit). O Nexa é multi-tenant com banco por tenant; o Bora é um
marketplace único de três lados (usuários, estabelecimentos, artistas) — os
estabelecimentos são registros na plataforma, não inquilinos com dados isolados.

## Decisão

Reaproveitar a stack do Nexa **sem** a camada de multi-tenancy: banco MySQL único, sem
`stancl/tenancy`. Papéis via `spatie/laravel-permission`, auditoria via
`spatie/laravel-activitylog`, API via Sanctum, login social via Socialite (Google).

## Opções consideradas

1. **Stack do Nexa sem tenancy (escolhida)** — Prós: zero curva de aprendizado, convenções
   e skills idênticas nos dois projetos, constituições irmãs. Contras: PHP/Laravel não é a
   escolha "da moda" para produto consumer; realtime e SPA exigirão decisões extras
   (pendentes na constituição).
2. **Stack do Nexa com tenancy (um tenant por estabelecimento)** — Prós: isolamento forte.
   Contras: modelagem errada para marketplace — o feed cruza todos os estabelecimentos o
   tempo todo; banco por estabelecimento tornaria a listagem/busca central um problema
   artificial. Custo alto sem benefício.
3. **Stack nova (ex.: Node/Next full-stack)** — Prós: SSR nativo para SEO do catálogo.
   Contras: time não domina; dois ecossistemas para manter; joga fora o método já pago no
   Nexa. SEO é atendível com frontend adequado sobre a API (decisão de frontend pendente).

## Consequências

- Fica mais fácil: alternância do time entre projetos, reuso de skills/templates,
  velocidade inicial.
- Fica mais difícil: nada estrutural; atenção ao SEO na escolha do frontend (o catálogo
  público precisa ser indexável — critério obrigatório na decisão pendente de frontend).
- Revisitar se: o produto exigir busca geoespacial/feed em escala que estresse MySQL
  (avaliar extensões espaciais ou serviço de busca dedicado via ADR próprio).

## Action items
- [x] Decidir framework de frontend com SEO como critério eliminatório — **Next.js +
      React + TypeScript**, ADR-0003 (2026-08-29).
- [ ] Decidir hospedagem (backlog).
