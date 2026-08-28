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

Usuário salva locais e artistas (telas "Locais salvos"/"Artistas salvos"). Salvar habilita
notificação de evento novo, alteração e cancelamento dos salvos (`RN-EVENTO-004`).

PENDENTE: "salvar" e "seguir" são a mesma ação ou duas (salvar = bookmark silencioso,
seguir = com notificação)?

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
