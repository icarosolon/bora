# Bora — Plano de Investimento e Orçamento

Status: estimativa de trabalho, montada em 2026-08-28 para o pitch de investimento anjo.
Todos os valores são **premissas a validar** — nenhum é contrato. Complementa
`monetization.md` (de onde vem a receita); aqui está para onde vai o dinheiro.

## Resumo

| | |
|---|---|
| **Captação alvo** | **R$ 350.000** |
| Horizonte de caixa | 18 meses (12 até o lançamento + 6 de operação e monetização) |
| Queima média | ~R$ 19.400/mês |
| Instrumento sugerido | Contrato de investimento anjo (LC 155/2016) ou mútuo conversível |
| Participação de referência | 10–15% (valuation a negociar) |

## Orçamento por categoria (18 meses)

| Categoria | Valor | % | O que cobre |
|---|---:|---:|---|
| Produto e engenharia | R$ 138.000 | 39% | Pró-labore do fundador/dev (~R$ 6 mil × 18), design de identidade e UI (~R$ 15 mil), freelances pontuais de frontend e QA (~R$ 15 mil) |
| Marketing e lançamento | R$ 68.000 | 19% | Tráfego pago (6 × R$ 3,5 mil), conteúdo (6 × R$ 2 mil), micro-influenciadores locais (~R$ 8 mil), ativações presenciais (~R$ 12 mil), material gráfico nas casas (~R$ 7 mil), imprensa/rádio local (~R$ 8 mil) |
| Vendas e operação de campo | R$ 54.000 | 15% | Pessoa de onboarding meio período (12 × R$ 2,5 mil), comissões de ativação (50 × R$ 200), deslocamento e conectividade |
| Reserva de contingência | R$ 47.000 | 13% | Atraso de marco, custo de API acima do previsto, imprevistos |
| Infraestrutura e ferramentas | R$ 22.000 | 6% | Hospedagem/banco/CDN, mapas e geocodificação, e-mail transacional e push, licenças de desenvolvimento |
| Jurídico, marca e LGPD | R$ 21.000 | 6% | Abertura da empresa e contabilidade, registro INPI, adequação LGPD, contratos |
| **Total** | **R$ 350.000** | 100% | |

## Cronograma (deliberadamente folgado)

| Marco | Prazo | Entrega |
|---|---|---|
| M0 — Fundação | meses 1–2 | Empresa aberta, marca registrada, identidade visual, stack e repositório |
| M1 — Contas | meses 3–4 | Login Google e cadastro próprio; conta única multi-papel |
| M2 — Catálogo | meses 5–7 | Perfis de casas e artistas, categorias e gêneros |
| M3 — Eventos | meses 8–9 | Criação pela casa, confirmação do artista, feed do dia |
| M4 — Descoberta | meses 10–11 | Busca, filtros, salvos, avaliações e rotas |
| M5 — Lançamento | mês 12 | Onboarding assistido nas duas cidades, notificações, divisão de conta |
| Operação e monetização | meses 13–18 | Medição dos gatilhos de audiência e ativação da cobrança (Fase 1) |

Os prazos assumem um desenvolvedor principal apoiado por IA, com folga para curva de
aprendizado — não um time completo em velocidade máxima. Cada marco fecha pela Definition
of Done do Princípio XI (API + tela + testes + validação visual).

## Mercado — TAM / SAM / SOM

Definições: **TAM** é o mercado inteiro se dominássemos tudo; **SAM** é a fatia que o
modelo consegue atender; **SOM** é o que realisticamente se captura no horizonte planejado.

| | Recorte | Estimativa | Premissas |
|---|---|---:|---|
| TAM | Brasil | ~R$ 200 mi/ano | ~207 mil casas com música ao vivo (15% de 1,38 mi de estabelecimentos ativos) × R$ 79/mês |
| SAM | Interior do Nordeste | ~R$ 17 mi/ano | ~18 mil casas em cidades de 50 mil+ habitantes × R$ 79/mês |
| SOM | Vale do São Francisco, ano 3 | ~R$ 400 mil/ano | ~300 casas alcançáveis em 6 cidades, 35% pagantes, mais destaque patrocinado e comissão de bilheteria |

### Dados de campo (Petrolina + Juazeiro)

| Dado | Valor | Fonte |
|---|---|---|
| População | 674.566 (Petrolina 418.444 · Juazeiro 256.122) | IBGE, estimativa 2025 |
| Bares e restaurantes | 741 (Petrolina 519 · Juazeiro 222) | Guia comercial Strelo, jun/2026 |
| Com música ao vivo recorrente | ~150 | **Premissa nossa** (20%) — PENDENTE validação em campo |
| Estabelecimentos no Brasil | 1.379.420 ativos | Governo Federal, ago/2024 |

## Premissas que precisam virar fato

Nenhum destes números foi verificado em campo. Trate-os como hipóteses do plano, não como
dados — e diga isso ao investidor, em vez de escondê-los:

- Percentual de casas com música ao vivo (20% das 741) — `RN-LOCAL-002`, contagem própria
  no onboarding.
- Disposição a pagar pelo plano Pro (R$ 49–99/mês) — backlog, validar com as casas.
- Conversão grátis → Pro (35% no SOM) e ticket de bilheteria.
- Custo real de API de mapas em volume — backlog, decisão de provedor.
- Pró-labore e custo de freelances na região.

## Cenário enxuto (se a captação vier menor)

Com ~R$ 180.000 o projeto ainda sai, cortando: a pessoa de campo (o fundador faz o
onboarding), metade do marketing pago, e o freelance de frontend — ao custo de um
lançamento mais lento e de menos casas ativadas na Fase 0. Não corte a reserva de
contingência nem a adequação jurídica/LGPD.
