# ADR-0002 — Frontend desacoplado no mesmo repositório

Status: aceito
Deciders: Ícaro · 2026-08-28

## Contexto

O projeto entrega não só a API, mas também o site (frontend). O app mobile virá depois,
quando o site tiver boa aceitação, e deve nascer consumindo a API já existente sem
retrabalho no backend. Precisamos decidir onde o frontend vive e como se relaciona com o
backend.

## Decisão

Frontend e backend vivem **no mesmo repositório**, como aplicações separadas e
desacopladas:

```
bora/
├─ api/   # Laravel — API REST pública (constituição, Princípio IV)
└─ web/   # frontend — Next.js + React (ADR-0003); consome exclusivamente /api/v1/...
```

O `web/` consome **apenas a API pública**, com a mesma autenticação (Sanctum) que o app
mobile usará. Nenhum atalho: sem Blade renderizando dado de domínio, sem endpoint privado
"só do site", sem acesso do front ao banco.

## Opções consideradas

1. **Mesmo repositório, apps separados (escolhida)** — Prós: um clone, um fluxo Spec Kit,
   uma spec cobre API+tela (entrega vertical do Princípio XI), versionamento conjunto de
   contrato e consumo. Contras: pipeline de CI precisa distinguir os dois apps; disciplina
   para não vazar acoplamento (guardada pelo Princípio IV).
2. **Repositórios separados** — Prós: isolamento máximo, deploys independentes triviais.
   Contras: specs e PRs de uma mesma feature se espalham em dois repos; para um time
   pequeno é fricção sem ganho — o desacoplamento que importa é o de **contrato**, não o
   de repositório.
3. **Monólito acoplado (Blade/Inertia no mesmo app)** — Prós: velocidade inicial máxima.
   Contras: a API "para o mobile" viraria promessa não exercitada; Inertia acopla front ao
   Laravel e não prova o contrato REST. Incompatível com a prontidão mobile do Princípio IV.

## Consequências

- Fica mais fácil: provar todos os dias que a API sustenta um cliente real (o site é o
  teste de integração permanente do contrato); nascer o app mobile sem tocar no backend.
- Fica mais difícil: CI/CD com dois builds; CORS e autenticação SPA precisam ser
  configurados desde a primeira feature.
- Revisitar se: o time crescer a ponto de front e back terem donos e cadências diferentes
  (aí separar repositórios vira opção real).

## Action items
- [x] Decidir framework do `web/` (critérios eliminatórios: SEO do catálogo público +
      acessibilidade) — **Next.js + React + TypeScript**, ADR-0003 (2026-08-29).
- [ ] Definir na primeira spec o setup de CORS/Sanctum SPA e o padrão de documentação da
      API (OpenAPI) que acompanha cada feature.
