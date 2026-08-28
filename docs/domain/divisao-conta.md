# Domínio — Divisão de conta

ID: `RN-CONTA-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-CONTA-001 — Calculadora de divisão de conta

Utilitário gratuito (`RN-PLAT-003`): divide o valor da conta entre os participantes.
**Não processa pagamento** na Fase 1 — só calcula (valor total, nº de pessoas, e possíveis
refinamentos). Origem: anotação do Ícaro ("Calculadora de divisão de conta") e tela
"Divida a sua conta aqui" do Figma (tela existe, conteúdo não foi desenhado).

PENDENTE (decidir antes da spec):
- Divisão simples (total ÷ pessoas) ou por item/consumo individual?
- Inclui taxa de serviço (10%) e couvert como campos próprios?
- Precisa de login ou funciona anônimo? (Recomendação: anônimo — é porta de entrada para
  o produto; login só para salvar histórico.)
- Fase futura: gerar link de cobrança Pix por pessoa? (Se sim, vira integração de
  pagamento — mesma decisão de gateway da Fase 3.)
