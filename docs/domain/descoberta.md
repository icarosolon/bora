# Domínio — Descoberta (busca, feed, personalização, notificações, rotas)

ID: `RN-DESC-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-DESC-001 — Busca e filtros

Busca textual sobre locais, artistas e eventos, com filtros por cidade (`RN-PLAT-006`),
categoria de local (`RN-LOCAL-002`), gênero musical (`RN-ART-002`) e data.

---

## RN-DESC-002 — Feed "o que temos para hoje"

Feed por cidade com os eventos do dia/próximos, com destaque visual para o que acontece
hoje. Ordenação padrão para usuário sem histórico: PENDENTE (proximidade? hora? recência de
publicação?). Com histórico e consentimento, aplica-se `RN-DESC-005`.

---

## RN-DESC-003 — Seguir e salvar

**São duas ações distintas** (decidido pelo Ícaro em 2026-09-02), válidas tanto para locais
quanto para artistas:

- **Salvar** — marcador silencioso. Guarda o local ou o artista na lista do usuário (telas
  "Locais salvos"/"Artistas salvos") e **não dispara notificação**.
- **Seguir** — acompanhar. Habilita notificação de evento novo e de alteração dos
  envolvidos (`RN-EVENTO-004`), sujeita a consentimento LGPD explícito com opt-out
  funcional (Constituição, Princípio III).

As duas são independentes: a pessoa pode ter uma, outra ou ambas.

Como são dois verbos próximos, a interface **não pode** distingui-los só por rótulo ou por
ícone — cada controle carrega uma linha curta dizendo o que faz. Exigência derivada de
`docs/product/ux-requirements.md` ("cada tela se explica sozinha ou falhou") e registrada
em `docs/product/design-system.md` (D19), que também define **Seguir** como a ação
principal do perfil de artista.

PENDENTE: o **cancelamento** de evento notifica também quem apenas **salvou**? A
`RN-EVENTO-004` diz hoje "notifica quem salvou/segue os envolvidos" — texto escrito antes
desta separação. Cancelamento é plausivelmente a única notificação que quem só salvou
pode querer sem ter pedido para acompanhar. Decisão do Ícaro.

---

## RN-DESC-004 — Rotas até o local

Todo local e evento oferece "como chegar" a partir da geolocalização do endereço
(`RN-LOCAL-003`). Fase 1: link para o app de mapas do usuário (Google Maps/Waze) resolve
sem custo de API. Mapa embutido/rota na própria plataforma: decisão de provedor e custo
PENDENTE (backlog).

---

## RN-DESC-005 — Personalização por comportamento

A plataforma aprende com o comportamento do usuário (locais mais visitados/vistos,
categorias e gêneros mais consumidos) para **ordenar listagens** e **selecionar
notificações**. Exige consentimento LGPD explícito, com opt-out que devolve a ordenação
neutra (Constituição, Princípio III). A política de ranqueamento vive no domínio; pesos e
janelas são parâmetros (dado), nunca hardcoded.

PENDENTE: sinais concretos da primeira versão (view de perfil? salvamento? clique em
rota?) e critério mínimo antes de personalizar (cold start).

---

## RN-DESC-006 — Notificações

Notificações personalizadas: evento novo de local/artista salvo, gênero favorito na sua
cidade, alteração/cancelamento de evento com interação do usuário. Sempre condicionadas a
consentimento e canal cadastrado; frequência controlada para não virar spam (parâmetro).

PENDENTE: canais (push web? e-mail? WhatsApp?) e provedores — backlog; depende de decisão
de stack pendente na constituição.
