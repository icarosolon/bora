# Modelo de Dados — rascunho conceitual

Status: rascunho para orientar as primeiras specs. O modelo definitivo nasce spec a spec;
este arquivo é atualizado pelo `/doc-sync` quando o modelo real mudar. Banco único
(ADR-0001).

## Entidades centrais

- **User** — conta única (`RN-PLAT-001`); credenciais próprias e/ou Google
  (`RN-PLAT-002`). Consentimentos LGPD (personalização, notificações) com timestamp.
- **Role/Papel** — rolezeiro (implícito), gestor de local, artista — via
  `spatie/laravel-permission`, sempre sobre a mesma conta.
- **City** — cidade (`RN-PLAT-006`); dimensão de recorte de todo o catálogo.
- **Venue (Local)** — estabelecimento: nome, slug, descrição, telefone, endereço +
  lat/long, Instagram, cidade, categorias (N:N), gestores (N:N com User), status
  (ativo/inativo — `RN-PLAT-005`).
- **VenueCategory** — categoria de local (`RN-LOCAL-002`), dado gerido pela plataforma.
- **Artist** — perfil de artista/banda: nome, bio, foto, Instagram, gêneros (N:N),
  contas gestoras (N:N com User), status.
- **Genre** — gênero musical (`RN-ART-002`), dado gerido pela plataforma.
- **Event** — evento: venue, data/hora, flyer, preço informativo (`RN-EVENTO-003`),
  status (rascunho/publicado/cancelado/realizado — `RN-EVENTO-004`).
- **EventArtist** — convite do evento ao artista com status
  (convidado/confirmado/recusado) — materializa `RN-EVENTO-002`.
- **Review/Comment** — avaliação/comentário de usuário sobre Venue ou Artist
  (polimórfico), com estado de moderação (`RN-AVAL-004`).
- **Like** — like de usuário sobre Venue/Artist/Event (polimórfico).
- **Bookmark (Salvo)** — usuário salva Venue/Artist (`RN-DESC-003`).
- **UserInteraction** — sinais de comportamento para personalização (`RN-DESC-005`),
  gravados apenas com consentimento.
- **Notification** — notificação enviada/pendente por canal (`RN-DESC-006`).
- **Subscription/Plan (Fase 1 de monetização)** — plano B2B do estabelecimento
  (grátis/Pro) — detalhar na spec da fase.

## Invariantes que o modelo precisa sustentar

- Uma conta, N papéis — nunca contas paralelas por papel (Princípio I).
- Evento sem confirmação não exibe a atração nem entra na agenda do artista
  (`RN-EVENTO-002`).
- Exclusões viram inativação; dado pessoal sai por anonimização (Princípio X).
- Toda escrita audita (Princípio VIII).
