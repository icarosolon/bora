# Domínio — Artistas e bandas

ID: `RN-ART-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-ART-001 — Cadastro self-service do artista

Artista/banda se cadastra sozinho e gerencia o próprio perfil (foto, bio, gêneros
musicais, Instagram). O perfil pertence a uma conta (`RN-PLAT-001`); banda com vários
integrantes é um perfil gerido por uma ou mais contas.

PENDENTE: co-gestão de perfil de banda (mais de um admin?) — decidir na spec de artistas.

---

## RN-ART-002 — Gêneros musicais

Todo artista declara um ou mais gêneros (Forró, Samba, Pagode, Sertanejo, …). A lista de
gêneros é gerida pela plataforma (dado, não hardcode) e alimenta filtros, feed e
personalização.

PENDENTE: lista inicial de gêneros — confirmar com o Ícaro (o Figma mostra Forró, Samba,
Pagode, Sertanejo e um card "Tiktok" — decidir se "hits do momento" é gênero ou coleção).

---

## RN-ART-003 — Perfil público do artista

Exibe: foto, bio, gêneros, likes, avaliações/comentários (`RN-AVAL-002`) e a **agenda de
eventos confirmados** (`RN-EVENTO-002`). Evento não confirmado não aparece na agenda
pública do artista.
