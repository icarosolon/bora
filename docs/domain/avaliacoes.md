# Domínio — Avaliações e interações

ID: `RN-AVAL-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-AVAL-001 — Avaliação de locais

Usuário autenticado avalia e comenta locais. O perfil exibe agregado (likes/contagem) e a
lista de comentários.

PENDENTE (decidir antes da spec de avaliações):
- Escala: o Figma mostra likes + comentários; nota em estrelas entra ou não?
- Uma avaliação por usuário por local, editável? Ou comentários livres múltiplos?

---

## RN-AVAL-002 — Avaliação de artistas

Mesmo mecanismo de `RN-AVAL-001`, aplicado ao perfil do artista.

PENDENTE: avaliação é do artista em geral ou de uma apresentação (evento) específica? A
segunda dá mais contexto ("como foi o show de sábado?") e alimenta melhor a reputação.

---

## RN-AVAL-003 — Avaliações do Google (import)

Avaliações do Google Maps só entram via **API oficial (Google Places)**, dentro dos termos
de uso e com a atribuição exigida. Scraping é proibido (Constituição, Princípio III).
Limitação conhecida da API: retorna no máximo ~5 avaliações por local, escolhidas pelo
Google, sem paginação — serve como **complemento exibido com atribuição**, não como base
de dados própria (armazenar/copiar o conteúdo viola o ToS).

PENDENTE: viabilidade/custo do Places API e decisão de produto — vale exibir 5 avaliações
do Google ao lado das nativas, ou focar 100% em avaliação própria? Registrar em ADR quando
decidido. Origem: anotação do Ícaro ("verificar se é possível buscar os comentários de
usuários do Google Maps").

---

## RN-AVAL-004 — Moderação

Conteúdo publicado (avaliações, comentários, fotos) é passível de denúncia e de moderação
pela plataforma. Remoção por moderação preserva o registro para auditoria (`RN-PLAT-004`,
`RN-PLAT-005`) — o conteúdo sai do ar, não da trilha.

PENDENTE: política de moderação (o que é removível, prazo de resposta a denúncia, quem
modera na fase inicial).
