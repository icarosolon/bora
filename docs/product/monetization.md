# Bora — Modelo de Negócio e Monetização

Status: modelo **Freemium B2B em fases** aprovado por Ícaro em 2026-08-28. Premissa
inegociável: **gratuito para o usuário final** (constituição, Princípio II). Preços abaixo
são referências a validar com o mercado local — PENDENTE pesquisa de disposição a pagar.

## Por que este modelo

Marketplace de três lados morre pelo problema do ovo e da galinha: estabelecimento só paga
se houver audiência; audiência só vem se houver conteúdo (eventos). A sequência que
funciona em plataformas locais (iFood, Sympla, Get In e afins seguiram variações disso):

1. **Popular a oferta primeiro, de graça** — catálogo cheio atrai público mesmo sem nenhum
   cliente pagante.
2. **Provar valor com número** — mostrar ao estabelecimento visualizações do perfil,
   alcance dos eventos, seguidores. Sem métrica visível, nenhum bar de Juazeiro paga
   mensalidade.
3. **Só então cobrar** — e cobrar pelo *destaque e pela conveniência*, nunca pelo básico,
   para o catálogo nunca encolher.

**Regra de ouro: não cobrar de ninguém enquanto a plataforma não provar audiência.**
Cobrar cedo demais esvazia o catálogo e mata os dois lados.

## As fases

### Fase 0 — Validação (sem receita, por design)
- Tudo gratuito. Onboarding assistido de 30–50 estabelecimentos em Juazeiro e Petrolina
  (meta inicial — ajustar conforme campo).
- Curadoria ativa: garantir que **toda semana** haja eventos publicados (agenda vazia é a
  morte da retenção).
- Métricas de saída da fase: usuários ativos semanais, eventos ativos/semana, taxa de
  retorno semanal do usuário, estabelecimentos que publicam sem ajuda.

### Fase 1 — Freemium B2B
| | Plano Grátis | Plano Pro (estabelecimento) |
|---|---|---|
| Perfil completo | ✔ | ✔ |
| Eventos/mês | limitado (ex.: 4) | ilimitado |
| Posição nas listagens | normal | destaque |
| Analytics (visitas, alcance, seguidores) | básico | completo |
| Selo verificado | — | ✔ |
| Preço | R$ 0 | referência R$ 49–99/mês (validar) |

- Artista permanece **gratuito** na Fase 1: artista é conteúdo, não cliente. Um plano
  Pro-Artista (destaque, analytics) é candidato a upsell futuro — PENDENTE.
- O limite do plano grátis nunca pode esvaziar a agenda pública: se o limite reduzir o
  volume de eventos publicados, ele está errado — afrouxar.

### Fase 2 — Destaque patrocinado
- Posições pagas no feed/busca ("patrocinado", sempre rotulado), venda por semana ou por
  evento. Complementa a assinatura sem excluí-la.

### Fase 3 — Bilheteria
- Venda de ingresso/couvert dentro da plataforma com comissão (referência de mercado:
  8–12% + taxa de serviço ao comprador — validar com gateway escolhido, PENDENTE).
- Maior potencial de receita no longo prazo e tração natural: o evento já está na
  plataforma; comprar ali é o caminho mais curto.
- Nota: a taxa de serviço ao comprador é prática padrão de bilheteria (Sympla etc.) e **não
  viola o Princípio II** — consumo do catálogo segue gratuito; ingresso é produto do
  estabelecimento. Registrar essa fronteira em emenda/spec quando a fase chegar.

## Riscos e como mitigá-los

- **Concorrência do grátis (Instagram/WhatsApp):** o bar já divulga de graça no Instagram.
  O produto precisa dar o que o Instagram não dá — público *procurando ativamente* o que
  fazer hoje, agregado por cidade, com rota e avaliação. A venda B2B é audiência
  qualificada, não "mais uma rede para postar".
- **Agenda vazia = churn de usuário:** curadoria ativa na Fase 0/1; notificação
  personalizada só quando houver conteúdo relevante (notificação vazia treina o usuário a
  ignorar).
- **Cobrar cedo demais:** ver regra de ouro.
- **Custo de mapas/geocodificação:** APIs de mapa cobram por uso; escolher provedor com
  camada gratuita generosa e cachear geocodificação — PENDENTE decisão (backlog).
- **Dependência de poucas contas pagantes no início:** preço baixo e volume, não preço alto
  e meia dúzia de âncoras.

## KPIs que dizem a verdade

- Usuários ativos semanais (WAU) e retenção semana-a-semana.
- Eventos ativos por semana, por cidade.
- Estabelecimentos que publicam sozinhos (sem onboarding assistido).
- Conversão grátis → Pro; churn mensal Pro; MRR.
- Fase 3: GMV de bilheteria e take rate efetivo.

## Decisões pendentes (ver backlog)

Preço final dos planos, limite exato do plano grátis, momento de ativar a Fase 1 (critério
numérico, não data), gateway de pagamento, plano Pro-Artista.
