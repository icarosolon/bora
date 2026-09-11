# Domínio — Plataforma

Regras transversais do núcleo (contas, papéis, gratuidade, auditoria, histórico).
ID: `RN-PLAT-NNN`. Specs referenciam o ID, não reescrevem o texto.

---

## RN-PLAT-001 — Conta única multi-papel

Uma pessoa tem uma conta. Papéis (rolezeiro, gestor de estabelecimento, artista) são
perfis vinculados à mesma conta e podem se acumular. Cadastro em um papel novo com e-mail
já existente vincula o papel à conta existente — nunca cria conta paralela.

Esta regra governa os papéis **de produto**, que a pessoa ganha se cadastrando. O papel de
**operação da plataforma** não é um deles e segue a `RN-PLAT-007`: concedido, nunca
autoatribuível.

Base constitucional: Princípio I.

---

## RN-PLAT-002 — Autenticação

Login via Google (OAuth) ou cadastro próprio (e-mail/senha). Vincular Google a uma conta
já criada por e-mail/senha (ou vice-versa) une as credenciais na mesma conta, mediante
confirmação do titular.

Fluxo de confirmação da união (decidido por Ícaro em 2026-08-29 — spec 001, decisão D1):
a união é confirmada **pela senha da conta existente**, com **link de confirmação por
e-mail como plano B** para quem esqueceu a senha. A direção inversa — conta criada via
Google define uma senha — exige **sessão ativa**. União cancelada, negada ou expirada não
altera nada: nenhuma conta criada, nenhuma credencial vinculada.

Base constitucional: Princípio I. Spec: `specs/001-contas-autenticacao/`.

---

## RN-PLAT-003 — Gratuidade do usuário final

Nenhuma funcionalidade de consumo (buscar, ver, seguir, salvar, avaliar, comentar, rota,
divisão de conta) é condicionada a pagamento. Funcionalidade paga é sempre B2B
(estabelecimento; futuramente bilheteria como produto do estabelecimento).

Base constitucional: Princípio II.

---

## RN-PLAT-004 — Auditoria de escrita

Toda criação, edição e exclusão de dado de domínio gera registro de auditoria (quem,
quando, o quê, antes/depois quando aplicável). Ações de moderação registram também o
operador da plataforma — quem pode ser operador está na `RN-PLAT-007`, e a **concessão do
papel** é ela própria escrita auditável.

Base constitucional: Princípio VIII.

---

## RN-PLAT-005 — Preservação de histórico e anonimização

Entidade com dado referenciado (evento realizado, avaliação, estabelecimento com
histórico) é inativada, nunca excluída. Pedido de eliminação de dado pessoal atende-se por
anonimização irreversível, preservando o registro histórico.

Base constitucional: Princípio X.

---

## RN-PLAT-006 — Cidade como dimensão primária

Todo local (e, por consequência, todo evento) pertence a uma cidade. Listagens, busca e
feed são recortados por cidade. O cadastro é aberto a qualquer cidade desde o dia um
(plataforma independente); Juazeiro-BA e Petrolina-PE são as cidades de validação
assistida, não um limite do sistema.

PENDENTE: cidade do usuário — detectada por geolocalização, escolhida manualmente, ou
ambas? Usuário pode acompanhar mais de uma cidade (ex.: mora em Juazeiro, sai em
Petrolina)?

---

## RN-PLAT-007 — Papel de operação da plataforma

Além dos papéis de produto da `RN-PLAT-001`, existe o papel de **operação da plataforma**,
de outra natureza: ele **não se acumula por cadastro** e **não é autoatribuível**. Ninguém
vira operador se inscrevendo — o papel é concedido por **ato explícito da plataforma**, e a
concessão é escrita: gera registro de auditoria com a quem, quando e por qual caminho
(`RN-PLAT-004`, Princípio VIII).

O papel existe como **dado semeado**, não como valor fixo em código: o nome vem de
`bora.account.operation_role` (Princípio VII — política no domínio, parâmetro como dado).

O **método de concessão é parâmetro, não regra.** Na Fase 1 o caminho é o comando
`bora:grant-operator {email}`; trocar por uma tela de gestão de papéis, numa spec de
operação, não pode exigir mudar esta regra.

É esta regra que sustenta a aprovação manual de reivindicação (`RN-LOCAL-005`) e a moderação
citada na `RN-PLAT-004` — ambas pressupõem alguém autorizado a decidir, e até aqui o catálogo
nunca disse quem.

Base constitucional: Princípios I, V, VII e VIII.
Spec: `specs/002-cadastro-perfil-local/` (FR-027).
