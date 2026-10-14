---
name: adr-new
description: Cria um Architecture Decision Record (ADR) no formato do projeto (docs/adr/NNNN-nome.md). Use quando uma decisão de arquitetura precisar ser registrada — escolha entre tecnologias/abordagens, mudança estrutural, trade-off relevante — ou quando o usuário disser "registra essa decisão", "cria um ADR". Nota: a skill engineering:architecture também cria ADRs; use uma ou outra.
disable-model-invocation: true
---

# adr-new

Registra uma decisão de arquitetura de forma que o "porquê" fique preservado para quem
chegar depois (ou para reconstruir o sistema do zero).

## Quando usar
Invocada manualmente (`/adr-new`) quando há uma decisão de arquitetura a documentar. Se a
skill `engineering:architecture` estiver disponível e o usuário preferir, ela cobre o mesmo
propósito — não duplique.

## Passos
1. Descubra o próximo número em `docs/adr/` (`NNNN`).
2. Crie `docs/adr/NNNN-<slug>.md` com esta estrutura:
   ```
   # ADR-NNNN — <título>
   Status: proposto | aceito | substituído por ADR-XXXX
   Deciders: <quem> · <data>

   ## Contexto
   O problema e as forças em jogo.

   ## Decisão
   O que foi decidido, em uma frase clara.

   ## Opções consideradas
   Para cada opção: prós, contras e como ela pontua nos critérios relevantes.

   ## Consequências
   O que fica mais fácil, o que fica mais difícil, quando revisitar.

   ## Action items
   - [ ] ...
   ```
3. Seja honesto nas opções: mostre o custo real de cada uma, não só a escolhida. Um ADR que
   só defende a decisão não ajuda quem for revisá-la depois.
4. Não invente consequências ou números — se algo for incerto, marque como a confirmar.

## Saída
O arquivo do ADR. Lembre o usuário de referenciar o ADR nas specs afetadas e rodar
`/doc-sync` antes do commit.
