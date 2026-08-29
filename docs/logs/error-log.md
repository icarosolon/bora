# Error Log

Registro de erros no formato `E-NNN` (sintoma, causa, resolução, status), mantido pela
skill `doc-sync`.

## E-001 — Issues do Bora nasceram com prefixo `NEX` (2026-08-29)

- **Sintoma:** todas as 31 issues do projeto Bora no Linear vinham identificadas como
  `NEX-9`..`NEX-39`. Olhando a tarefa não dava para saber de qual produto ela era.
- **Causa:** a importação de 2026-08-28 seguiu a decisão de colocar o projeto Bora dentro
  do time `Nexa`. No Linear o prefixo do identificador é atributo do **time**, não do
  projeto — projeto não tem prefixo. Logo, qualquer issue criada naquele time sairia `NEX`,
  independentemente do projeto.
- **Resolução:** criado o time próprio `Bora` (key `BORA`) e trocado o time do projeto.
  O Linear migrou as 31 issues automaticamente, preservando projeto, marcos, prioridades,
  status e a label `decisao-pendente`; renumerou em ordem inversa (`BORA-n` = `NEX-(40−n)`)
  e mantém redirect dos IDs antigos. Docs sincronizadas no mesmo commit.
- **Status:** resolvido. Efeito colateral aceito pelo Ícaro: a numeração não segue a ordem
  de prioridade (BORA-1 é a decisão mais distante, BORA-31 é o setup) — não há como
  renumerar no Linear sem recriar as issues e perder o histórico.
- **Lição:** no Linear, um produto que precisa de identificador próprio precisa de **time**
  próprio. Decidir isso antes de importar issues, não depois.
