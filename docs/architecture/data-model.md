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

## Detalhamento por spec

O modelo real nasce spec a spec. Quando uma spec detalha parte deste modelo conceitual, o
desenho concreto (colunas, índices, invariantes, transições de estado) vive na própria
spec, e este documento aponta para lá em vez de duplicar.

- **Contas e autenticação** → `specs/001-contas-autenticacao/data-model.md`
  (**implementado e em uso desde 2026-08-31** — as quatro user stories da spec 001 estão
  entregues e validadas; as tabelas existem e são exercitadas por 185 testes).
  Detalha a parte de **User** e **Role/Papel** acima e acrescenta duas entidades que este
  documento conceitual não previa:
  - **ContaSocial** — vínculo com provedor externo (Google). O vínculo é pelo
    identificador do provedor, **não pelo e-mail**, para que troca de e-mail no Google não
    quebre o acesso.
  - **TokenDeEmail** — link de uso único com validade, servindo aos três fluxos
    (verificação de e-mail, união de credenciais, redefinição de senha). Guarda apenas o
    **hash** do token.

  Alterações previstas em `users`: `password` passa a **nullable** (conta que nasce pelo
  Google não tem senha), `email` sempre normalizado, e nova coluna `ultimo_acesso_em`.
