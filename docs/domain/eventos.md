# Domínio — Eventos

ID: `RN-EVENTO-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-EVENTO-001 — Quem cria o evento é o estabelecimento

O evento é criado pelo estabelecimento (gestor do local), no local dele. Artista não cria
evento; artista é convidado (`RN-EVENTO-002`).

Origem: anotação do Ícaro — "Quem cria evento? Estabelecimento e convida o grupo musical,
que precisa confirmar a participação!"

---

## RN-EVENTO-002 — Confirmação do artista

O estabelecimento convida o artista para o evento; o artista **precisa confirmar** a
participação. A atração só aparece vinculada ao evento (e o evento na agenda do artista)
após a confirmação.

PENDENTE (decidir antes da spec de eventos):
- O evento pode ser publicado antes da confirmação (sem atração exibida) ou fica em
  rascunho até confirmar?
- Prazo para o artista responder e o que acontece se recusar/ignorar.
- Estabelecimento pode publicar evento com atração **não cadastrada** na plataforma (nome
  em texto livre + convite para o artista reivindicar)? Importante para não travar a
  agenda na fase de adoção.

---

## RN-EVENTO-003 — Dados do evento

Evento tem: local (`RN-LOCAL-003`), data e hora, atração(ões), flyer/imagem, valor de
entrada/couvert (**informativo** na Fase 1 — sem venda; bilheteria é Fase 3), links de
Instagram, comentários e compartilhamento.

---

## RN-EVENTO-004 — Ciclo de vida

Evento realizado permanece no histórico do local e do artista (`RN-PLAT-005`).
Cancelamento não apaga o evento: marca como cancelado e notifica quem salvou/segue os
envolvidos (`RN-DESC-003`).

PENDENTE: janela de edição após publicação (mudar data/atração notifica quem interagiu?).

PENDENTE: **"quem salvou/segue" acima está desatualizado.** A frase foi escrita quando
salvar e seguir eram a mesma coisa; desde 2026-09-02 a `RN-DESC-003` os separou — **salvar
é silencioso e não notifica**. Falta decidir se o **cancelamento** é a exceção que também
avisa quem apenas salvou. A pergunta está detalhada em `descoberta.md` (`RN-DESC-003`).
Enquanto não for decidida, esta regra **não** deve ser lida como promessa de notificação a
quem salvou.
