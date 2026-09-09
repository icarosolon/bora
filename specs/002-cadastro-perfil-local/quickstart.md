# Quickstart — validar a spec 002 de ponta a ponta

**Spec**: [spec.md](./spec.md) · **Plano**: [plan.md](./plan.md) ·
**Contrato**: [contracts/locais-api.md](./contracts/locais-api.md) · **Data**: 2026-09-09

Guia de **validação**, não de implementação. Serve para o Ícaro conferir cada história e
para a próxima sessão retomar sem re-explorar.

> **Ordem obrigatória:** a fase Foundational bloqueia todas as histórias
> (`tasks-template.md`: *"No user story work can begin until this phase is complete"*).
> Validar história antes dela é validar tela que vai ser refeita.

---

## Pré-requisitos

- WAMP com PHP 8.4, MySQL 8 e Redis no ar.
- `api/.env` com `FRONTEND_URL` apontando para o **IP da máquina na rede**, não `localhost`
  — senão o link do e-mail chega inutilizável no celular (**E-011**).
- **Reiniciar o `queue:work` depois de qualquer renomeação de classe ou mudança de
  binding** — o worker é daemon e carrega o provider antigo em memória (**E-018**). O aviso
  de reivindicação sai por fila, então isso vale aqui.
- Servidores: API em `:8000`, front em `:3000`. Conferir por **PID na porta** antes de
  afirmar que subiu ou caiu.

```bash
cd api && php artisan migrate:fresh --seed && php artisan queue:work
```

```bash
cd web && npm run dev
```

---

## Fase Foundational — validar **antes** de qualquer história

O que se prova aqui não é funcionalidade; é que a fundação **existe e tem dente**.

1. **O portão reprova algo real.** Rodar o portão contra as telas **já validadas da spec
   001**. Se ele não acusar nada, ele é fraco — e é isso que precisa ser descoberto no dia
   um, não depois (lição do **E-012**: rede de proteção não verificada dá falsa confiança).
2. **O `Button` nasce com 44px.** Renderizar um `Button` **sem** `className` e conferir o
   alvo. Hoje ele é `h-8` (32px) e só passa porque cada chamada corrige na mão com
   `min-h-11`.
3. **O tema segue o aparelho.** Trocar o modo escuro no sistema operacional e recarregar: a
   página acompanha, **sem** nenhum alternador na interface (D3). Hoje o escuro é por classe
   `.dark` e nada aplica essa classe.
4. **O Geist Mono sumiu.** Conferir que nenhuma fonte monoespaçada é baixada.
5. **Medição dos rótulos** (R2, pendente): medir `Hoje`, `Buscar`, `Salvos`, `Dividir`,
   `Conta` a 360px, sob **zoom de 200%** e sob **fonte do sistema ampliada** — mecanismos
   diferentes (D12). O resultado define onde a barra quebra em duas linhas.
6. **As telas da spec 001 continuam passando** depois do retrofit: criar conta, entrar,
   sair, Google, unir contas, esqueci a senha.

---

## P1 — "Coloco um bar no Bora e vejo a página dele no ar"

**Percurso, no celular primeiro** (Princípio XI: tela reprovada no celular não é apresentada
no computador):

1. Entrar com uma conta.
2. Cadastrar um bar com **só os quatro campos**: nome, endereço, categoria, telefone.
3. Terminar e **cair na página pública** do bar.
4. Tocar em **"Convidar"** → o compartilhamento **do próprio aparelho** abre com o link.
5. Tocar em **"Como chegar"** → o app de mapas abre com o endereço.
6. Abrir o link **em janela anônima, sem sessão** → mesma página, com a linha *"Este perfil
   ainda não é gerenciado pelo estabelecimento…"*.

**O que precisa ser verdade:**

- Nenhum campo rico aparece — nem vazio, nem como espaço reservado.
- O **"voltar" é link para destino nomeado**, e funciona **na chegada fria** (sem histórico).
  É o caso do link compartilhado, que é o primeiro contato de um usuário novo.
- Cadastrar um nome parecido no mesmo bairro **avisa antes de criar**, mostrando o que
  encontrou, e deixa a decisão com quem cadastra.
- **Toque duplo em rede lenta não cria dois perfis.**
- **Não existe** a caixinha "sou eu que gerencio este bar" — ela é da P3, junto com a
  aprovação. Se aparecer aqui, o sistema acumula pedido que ninguém pode analisar.

**Medir:** SC-001 (cadastro em menos de 2 minutos, no celular, sem ajuda) e SC-006 (em 3G, o
essencial visível em até 3 segundos, com as imagens sem deslocar o conteúdo).

---

## P2 — "Encontro bares na lista e filtro por categoria"

1. Abrir a lista com locais em categorias diferentes.
2. Conferir **nome + bairro** em cada item, em **uma coluna** a 360px.
3. Aplicar cada filtro e ver o conjunto mudar.
4. Filtrar por categoria vazia → a tela **ensina**, não fica em branco.

**O que precisa ser verdade:** nenhum item exibe estado que dependa de quem olha — sem
coração, sem "seguindo" (`D14`, `D15`). Estando logado ou não, a lista é **a mesma**.

---

## P3 — "Reivindico o perfil do meu estabelecimento"

1. Pedir a reivindicação informando **nome, função, melhor horário e a quem perguntar**.
   Faltando qualquer um, o envio é recusado **no campo**.
2. Na tela de aprovar, a evidência aparece **ao lado do telefone do perfil** — que é o
   número para o qual ligar. Nunca um informado pelo solicitante.
3. **Aprovar** → o selo **"Perfil do estabelecimento"** aparece na página pública, e a conta
   passa a editar.
4. **Recusar** → o motivo é obrigatório, o solicitante é avisado com o motivo e um caminho
   para falar com a plataforma, e pode pedir de novo.
5. **Dois pedidos para o mesmo local** ficam pendentes juntos e aparecem **lado a lado**;
   aprovar um **encerra o outro com aviso**.

**O que precisa ser verdade:** a aprovação **transfere sem recriar** — avaliações e
histórico continuam ligados ao mesmo local. E o registro guarda **por qual método** foi
aprovado, porque o método vai mudar (BORA-49).

**Medir:** SC-005 — mostrar a página a **5 pessoas de fora**, 30 segundos cada; pelo menos
**4** explicam sem ajuda a diferença entre perfil gerenciado e não gerenciado.

---

## P4 — "Enriqueço o perfil"

1. Com perfil reivindicado: acrescentar foto, descrição e Instagram → aparecem na página.
2. Com perfil **não** reivindicado: os campos **não são oferecidos**.
3. Upload de tipo ou tamanho não permitido → a recusa **explica o limite** em linguagem
   humana.

---

## Suítes automatizadas

```bash
cd api && php artisan test
```

```bash
cd web && npm test && npm run test:e2e
```

**O que a suíte precisa cobrir além do caminho feliz** — a spec enumera em "Cenários de
Teste": uma asserção por `RN` referenciada, uma por princípio NON-NEGOTIABLE tocado, telas em
**360 e 1280** com `axe`, e a asserção de **fonte ampliada** exigida pela D12.

> **Cuidado registrado, vindo do E-012:** o teste de componente **não pega** a janela entre o
> HTML chegar e o JavaScript assumir — `jsdom` não tem essa janela. Todos os três defeitos de
> hidratação do projeto foram pegos por **e2e**. Controle novo que dispara ação precisa de
> teste e2e, não só de componente.

> **E do E-013:** validar **no aparelho** é diferente de validar na máquina. O servidor de
> desenvolvimento do Next recusa origem diferente de `localhost` — se as telas carregarem mas
> nada funcionar no celular, conferir `allowedDevOrigins` antes de procurar bug na tela.
