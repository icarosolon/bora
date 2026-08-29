# Error Log

Registro de erros no formato `E-NNN` (sintoma, causa, resolução, status), mantido pela
skill `doc-sync`.

## E-002 — O portão `/spec-check` não cobrava tela, UX nem acessibilidade (2026-08-29)

- **Sintoma:** o `development-workflow.md` afirmava que o portão reprova "tela que não
  referencie `ux-requirements.md`". Ao conferir a skill, essa checagem não existia — e nem
  qualquer outra sobre tela, mobile ou acessibilidade. Descoberto porque o Ícaro
  desconfiou da afirmação de que a nova regra de mobile-first "já estaria valendo".
- **Causa:** a documentação do método descrevia um comportamento pretendido como se fosse
  implementado. Além disso, o `spec-template.md` era o padrão de fábrica do Spec Kit, sem
  nenhuma seção de tela — e com o exemplo de premissa "Mobile support is out of scope for
  v1", diretamente contrário aos Princípios XI e XII. A cadeia inteira que deveria fazer
  valer esses princípios estava quebrada nos três elos.
- **Resolução:** (1) `spec-template.md` ganhou a seção obrigatória "Tela e Experiência"
  (telas e ação principal, comportamento a 360px, polegar, estados obrigatórios,
  acessibilidade, testes em 360 e 1280) e o exemplo de premissa venenoso foi substituído;
  (2) a skill `spec-check` passou a ler `ux-requirements.md` e a tratar cada um desses itens
  como **Bloqueante**, com instrução explícita de não rebaixá-los a Aviso; (3) o
  `development-workflow.md` passou a descrever o portão que existe de fato.
- **Status:** resolvido. A verificar na primeira spec real (001 — contas): o portão precisa
  reprovar de verdade uma spec sem tela.
- **Lição:** princípio que não está escrito na skill que o cobra **não está valendo** — está
  só valendo enquanto alguém lembrar. Ao afirmar que uma regra é obrigatória, abrir o
  arquivo que a executa e conferir, em vez de confiar na doc que a descreve.

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
