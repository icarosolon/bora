# Domínio — Locais (bares e restaurantes)

ID: `RN-LOCAL-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-LOCAL-001 — Cadastro self-service e gestão do próprio perfil

O estabelecimento se cadastra sozinho e gerencia o próprio perfil (fotos/logo, descrição,
telefone, endereço, categorias, Instagram). Só contas com papel de gestor vinculado ao
local editam o local.

PENDENTE: verificação de propriedade — o que impede alguém de cadastrar o bar dos outros?
(Candidatos: verificação por telefone do local, documento, aprovação manual na fase
assistida.) Decidir antes da spec de cadastro de local.

---

## RN-LOCAL-002 — Categorias de local

Todo local tem uma ou mais categorias (ex.: bar, restaurante, choperia, petiscaria). A
lista de categorias é gerida pela plataforma (dado, não hardcode) e alimenta filtros e
personalização (`RN-DESC-001`, `RN-DESC-002`).

PENDENTE: lista inicial de categorias — confirmar com o Ícaro.

---

## RN-LOCAL-003 — Perfil público com contato e localização

O perfil público exibe: nome, imagem, descrição, categorias, telefone (clique-para-ligar),
endereço completo com geolocalização (alimenta a rota — `RN-DESC-004`), Instagram, likes e
avaliações (`RN-AVAL-001`), e a agenda de eventos do local.

---

## RN-LOCAL-004 — Um local, um perfil

Cada estabelecimento físico tem exatamente um perfil. Duplicata identificada é unificada
(histórico preservado — `RN-PLAT-005`), nunca apagada.

PENDENTE: redes/franquias com mais de uma unidade — um perfil por unidade? Confirmar
quando o caso aparecer.
