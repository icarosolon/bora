# Domínio — Locais (bares e restaurantes)

ID: `RN-LOCAL-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-LOCAL-001 — Cadastro self-service e gestão do próprio perfil

O estabelecimento se cadastra sozinho e gerencia o próprio perfil (fotos/logo, descrição,
telefone, endereço, categorias, Instagram). Só contas com papel de gestor vinculado ao
local editam o local.

**Qualquer conta pode CRIAR o perfil de um local** (decidido pelo Ícaro em 2026-09-03).
Criar e controlar são coisas separadas: o perfil nasce **não reivindicado** e só passa a
ser controlado por alguém pela reivindicação (`RN-LOCAL-005`). O motivo do corte é o dano
real: perfil errado é constrangimento corrigível, **evento falso faz a pessoa atravessar a
cidade para nada** — então a porta da verificação fica na **publicação**, não no cadastro.
Assim o catálogo enche na velocidade das pessoas, e não na velocidade da verificação.

PROPOSTA (assistente, aguardando o Ícaro): o perfil **não reivindicado é propositalmente
magro** — nome, endereço, categoria e telefone, e nada além. Sem fotos, descrição ou
Instagram. Encolhe a superfície de vandalismo, evita que um estranho tenha controle
editorial sobre o bar de outro, e faz da riqueza do perfil (`RN-LOCAL-003`) um **prêmio da
reivindicação**, não o estado inicial.

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

---

## RN-LOCAL-005 — Reivindicação de perfil de local

Decidida pelo Ícaro em 2026-09-03 (fecha a BORA-22). Separa **criar** de **controlar**
(`RN-LOCAL-001`).

**Estados do perfil.** Todo local está em um de dois estados:

- **Não reivindicado** — criado por qualquer conta. Aparece no catálogo e na busca, mas
  **não publica evento**.
- **Reivindicado** — tem gestor vinculado e verificado. Só neste estado o local publica
  evento (`RN-EVENTO-001`).

**Método de verificação — Fase 1: aprovação manual da plataforma.** A reivindicação é
aprovada à mão, coerente com a implantação assistida já assumida em
`docs/product/vision.md`. Escolhido também por sequência: os métodos automáticos dependem
de provedor de SMS/voz que segue PENDENTE (BORA-6), e adotá-los agora reabriria aquela
decisão e voltaria a travar a spec de cadastro de local.

Descartados para a Fase 1, com o motivo registrado:

- **Código no telefone do local** — sinal forte e escalável, mas depende da BORA-6; e boa
  parte dos bares usa celular pessoal ou só WhatsApp. É o sucessor natural.
- **Documento (CNPJ, contrato social, alvará)** — fricção que mata o self-service, guarda
  de documento é dado sensível e passivo de LGPD (Princípio III), e **boa parte do bar
  pequeno em Juazeiro e Petrolina opera como MEI ou informal**: exigir documento excluiria
  parte do mercado-alvo.

**A política é do domínio; o método é parâmetro.** Trocar aprovação manual por método
automático não pode exigir mudança de regra — só de configuração e de adaptador.

**Transferência preserva histórico** (Princípio X). Quando o gestor legítimo reivindica um
perfil criado por terceiro, o perfil é **transferido, nunca recriado**: avaliações,
comentários e histórico de eventos continuam ligados ao mesmo local. Quem criou perde o
controle editorial; o registro de que criou não é apagado.

**Auditoria** (Princípio VIII): ficam registrados quem criou o perfil, quem reivindicou,
quem aprovou, quando, e **por qual método a reivindicação foi aprovada** — este último
porque o método vai mudar, e disputa entre um dono e quem cadastrou antes dele é cenário
certo, não hipotético.

PENDENTE: quando a aprovação manual deixar de escalar, qual método automático a substitui e
qual o gatilho da troca? Depende da BORA-6 (provedor). Ver BORA-49.

PENDENTE: duas pessoas reivindicam o mesmo local — qual o critério de desempate, e o que
acontece com quem perde?
