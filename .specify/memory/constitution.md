<!--
Sync Impact Report
- Version change: 1.1.0 → 1.1.1 (PATCH — clarificação de redação, sem efeito semântico)
- Em 2026-08-28 o codinome de trabalho "iBar" foi aposentado: o repositório passou a se
  chamar `bora` e o projeto no Linear já nasceu como "Bora". A menção ao codinome saiu do
  Escopo. Nenhum princípio foi alterado.

Sync Impact Report — Emenda 1 (histórico)
- Version change: 1.0.0 → 1.1.0
- Amendment: Emenda 1, ratificada por Ícaro em 2026-08-28
- Bump MINOR: adiciona dois princípios novos (XI e XII) e expande o IV; nenhum princípio
  existente é redefinido de forma incompatível.
- Core Principles adicionados:
  XI. Entrega Vertical com Validação Visual (NON-NEGOTIABLE) — NOVO. Feature só está
      pronta com API + tela implementadas, testes automatizados dos dois lados aprovados e
      validação visual do Ícaro antes de iniciar a próxima feature.
  XII. Usabilidade Universal — NOVO. Design interativo, simples e acessível para todos os
       perfis, incluindo quem tem pouca familiaridade com tecnologia (idosos).
- Core Principles alterados:
  IV. API-First — expandido: o frontend vive neste repositório, totalmente desacoplado,
      consumindo exclusivamente a API pública; a API permanece pronta para o app mobile,
      que será lançado quando houver boa aceitação do site. Ver ADR-0002.
  IX. Qualidade Verificável — expandido: cenários e testes cobrem também o frontend.
- Outras mudanças: "Bora" passa de nome de trabalho a **nome oficial da solução** (registro
  INPI segue pendente no backlog); o design de telas do Figma antigo é oficialmente
  aposentado — requisitos de UX em docs/product/ux-requirements.md.
- Source: decisões de Ícaro em 2026-08-28 (goal da sessão).
- Follow-up TODOs: framework de frontend segue PENDENTE (agora bloqueia a primeira tela).

Sync Impact Report — ratificação inicial (histórico)
- Version change: — → 1.0.0 (ratificação inicial)
- Source: extração da ideia original (Figma "App Rolezeiros" + anotações do Ícaro) e
  decisões de 2026-08-28: nome de trabalho "Bora" (registro de marca PENDENTE), stack
  reaproveitada do Nexa sem multi-tenancy, monetização Freemium B2B em fases.
- Core Principles: I–X definidos nesta ratificação.
- Follow-up TODOs: framework de frontend, hospedagem, provedor de mapas/rotas, provedor de
  push, provedor de e-mail transacional e gateway de pagamento (Fase 3) seguem PENDENTE —
  ver docs/logs/backlog.md.
-->

# Bora Constitution

## Escopo

Bora (nome oficial da solução) é uma plataforma web
(marketplace de três lados) que conecta **público**, **estabelecimentos** (bares e
restaurantes) e **artistas/bandas** em torno de eventos de música ao vivo. Lançamento como
site responsivo; **o app mobile será lançado quando o site tiver boa aceitação**,
consumindo a mesma API (Princípio IV). Este repositório abriga **API e frontend**,
desacoplados (ADR-0002). Validação inicial nas cidades de Juazeiro-BA e Petrolina-PE; a
plataforma é aberta — qualquer estabelecimento, artista ou usuário pode se cadastrar
sozinho.

Os Core Principles abaixo valem para todo o produto. Diferente do Nexa, **não há
multi-tenancy**: é uma plataforma única com banco único — ver ADR-0001.

## Core Principles

### I. Conta Única Multi-Papel (NON-NEGOTIABLE)
Uma pessoa tem **uma conta**. Os papéis — rolezeiro (usuário final), gestor de
estabelecimento, artista — são **perfis vinculados à mesma conta**, nunca contas separadas.
Uma conta pode acumular papéis (o dono do bar também pode ser músico e também sai à noite).
Autenticação via Google OAuth ou cadastro próprio (e-mail/senha) resolve para a mesma
identidade; vincular um provedor novo a um e-mail já cadastrado une, nunca duplica.
Rationale: duplicar contas por papel fragmenta identidade, notificações e histórico, e
obriga a pessoa a gerenciar logins paralelos — o custo de modelar papéis desde o início é
muito menor que o de fundir contas depois.

### II. Gratuidade do Usuário Final (NON-NEGOTIABLE)
Nenhuma funcionalidade de **consumo** — buscar, ver locais e eventos, seguir/salvar,
avaliar, comentar, traçar rota, dividir conta — fica atrás de pagamento ou de paywall.
A monetização é B2B (estabelecimentos e, futuramente, bilheteria) — ver
`docs/product/monetization.md`. Qualquer feature paga voltada ao usuário final exige
emenda a esta constituição.
Rationale: a audiência é o ativo que sustenta o modelo de negócio; cobrar do usuário final
mata a adesão de que o lado B2B depende.

### III. Conteúdo de Terceiros e Conformidade Legal (NON-NEGOTIABLE)
Dados de serviços externos (ex.: avaliações do Google) entram **apenas via API oficial**,
dentro dos termos de uso do provedor e com a atribuição exigida — scraping é PROIBIDO.
Conteúdo publicado por estabelecimentos e artistas (fotos, flyers, descrições) é de
responsabilidade do publicador, sujeito a moderação da plataforma. Personalização por
comportamento (aprendizado de locais/categorias preferidas) só ocorre com consentimento
LGPD explícito e com opt-out funcional; dados de localização do usuário nunca são expostos
a terceiros nem usados fora da finalidade consentida.
Rationale: a plataforma vive de conteúdo de terceiros e de dados de comportamento — as duas
maiores superfícies de risco legal do produto. Violação de ToS do Google ou de LGPD não é
bug, é passivo jurídico.

### IV. API-First e Contrato Estável
Toda funcionalidade nova é exposta via REST API antes de existir interface visual.
Versionamento path-based (`/api/v1/...`); mudanças incompatíveis criam nova versão de path.
Respostas seguem envelope padronizado (`data`/`meta`/`links` em listas, `message` em ações,
`errors` em validação 422) e sempre via API Resource — nunca Model serializado direto.
Datas em ISO 8601.

**Frontend desacoplado.** O frontend vive neste repositório como aplicação separada e
consome **exclusivamente a API pública** — os mesmos endpoints, com a mesma autenticação,
que qualquer outro cliente usaria. É PROIBIDO acoplamento por atalho: renderização de dados
pelo backend, endpoint privado "só do site", acesso direto do front ao banco ou a
internals do Laravel. Ver ADR-0002.

**Prontidão mobile.** O app mobile será lançado quando o site tiver boa aceitação e MUST
ser viável **sem nenhuma mudança estrutural no backend**: se uma funcionalidade do site
não puder ser reproduzida no app apenas consumindo a API existente, a API está incompleta
— e isso é bug de contrato, não limitação aceitável. A documentação da API acompanha cada
feature (Princípio XI).
Rationale: o site é o primeiro cliente, o app mobile é o segundo já anunciado; o front
desacoplado consumindo a API pública é o que prova, todos os dias, que a API está pronta
para o app.

### V. Segurança e Autorização por Padrão
Toda ação sensível exige checagem de Policy antes de tocar dados. Toda entrada de usuário
passa por FormRequest dedicado com `authorize()` e `rules()`; sempre `validated()`, nunca
`$request->all()`. Mass assignment com `$fillable` explícito; `$guarded = []` proibido.
Uploads (fotos de perfil, flyers) validados por MIME e tamanho, com processamento seguro
antes de servir. Só o gestor vinculado edita o estabelecimento; só o artista edita o próprio
perfil; conteúdo público é público, dado de conta é privado. Dados pessoais nunca em logs.
Rationale: plataforma aberta com cadastro self-service é alvo desde o dia um; segurança
retrofit custa mais que segurança por padrão.

### VI. Operações Assíncronas para Tarefas Pesadas
Notificações (push, e-mail), processamento de imagem, geocodificação e qualquer integração
externa lenta MUST rodar em Jobs de fila — nunca no ciclo síncrono da request.
Rationale: o tempo de resposta da API não pode depender da latência de terceiros.

### VII. Simplicidade e Camadas Claras
Controllers finos, um recurso por controller. Service só quando há lógica cruzando
múltiplos models, integração externa ou algoritmo reutilizável; CRUD simples vive em
Controller + FormRequest. Side effects via Event/Listener. Integrações externas atrás de
portas & adapters — o domínio nunca importa SDK externo.
Rationale: código previsível para qualquer agente ou dev que entre depois; o padrão é o
mesmo do Nexa para o time trabalhar igual nos dois projetos.

### VIII. Auditoria de Escrita
Toda criação, edição e exclusão de dado de domínio gera registro de auditoria (quem,
quando, o quê, valores antes/depois quando aplicável), via `spatie/laravel-activitylog`.
Ações de moderação da plataforma sobre conteúdo de terceiros registram também o operador.
Rationale: disputa entre estabelecimento e artista, denúncia de avaliação falsa e incidente
de segurança são cenários certos — todos exigem responder "quem mudou isso e quando".

### IX. Qualidade Verificável e Cenários de Falha
Nenhuma spec entra em implementação sem cenários de teste declarados, incluindo caminhos de
erro e limites. Toda `RN-<CTX>-NNN` referenciada tem ao menos um teste que a exercite; todo
princípio NON-NEGOTIABLE tocado tem teste que prove o bloqueio (ex.: consumo nunca exige
pagamento; conta duplicada é rejeitada/unificada; import sem atribuição falha). **Toda
feature tem testes automatizados no backend (unitários/feature da API) e no frontend
(unitários de componente e/ou integração da tela)** — executados e aprovados antes da
entrega (Princípio XI). Cenário de falha previsível não coberto pela spec: o agente MUST
parar e apresentar o gap antes de implementar — nunca decidir sozinho.
Rationale: teste de caminho feliz documenta intenção, não comportamento sob estresse; e
tela sem teste quebra em silêncio na feature seguinte.

### X. Preservação do Histórico (NON-NEGOTIABLE)
Entidade que já produziu dado referenciado (evento realizado, avaliação publicada,
estabelecimento com histórico) **não é excluída — é inativada**. Pedido de eliminação de
dado pessoal do titular atende-se por **anonimização irreversível**, preservando o registro
histórico (avaliações e métricas continuam fechando; a pessoa deixa de ser identificável).
Quando o sistema bloquear uma exclusão, MUST informar qual condição a impede.
Rationale: o histórico de eventos e avaliações é o próprio produto (reputação de locais e
artistas); apagar histórico corrompe a confiança que a plataforma vende.

### XI. Entrega Vertical com Validação Visual (NON-NEGOTIABLE)
Uma funcionalidade só está **pronta** quando, juntos:

1. A API está completa, funcional e documentada (Princípio IV) — pronta para o app mobile;
2. A **tela correspondente está 100% implementada no frontend**, consumindo essa API —
   funcionalidade "pronta só no backend" não existe;
3. Os testes automatizados de backend **e** frontend foram executados e aprovados
   (Princípio IX);
4. A tela foi **apresentada ao Ícaro e validada visualmente por ele**.

É PROIBIDO iniciar a implementação da próxima funcionalidade antes da validação visual da
atual. A ordem interna da feature pode (e deve) ser API-first; a **entrega** é sempre
vertical: API + tela + testes + validação.
Rationale: backend pronto sem tela é estoque invisível — não valida nada com usuário nem
com o dono do produto. O portão de validação visual existe porque o Ícaro dirige o produto
pelo que vê; acumular features sem validação acumula retrabalho.

### XII. Usabilidade Universal
O design é **interativo, simples e muito fácil de usar** para todos os perfis de público —
incluindo quem tem pouca familiaridade com celular/web (idosos são o menor público, mas são
público). Requisitos vinculantes em `docs/product/ux-requirements.md`; mínimos que valem
para toda tela: linguagem simples e direta (sem jargão), ações principais óbvias e com um
toque, alvos de toque confortáveis, tipografia legível com contraste AA
(`docs/product/brand.md`), feedback claro de cada ação (carregando, sucesso, erro em
linguagem humana), navegação rasa e consistente, e funcionamento decente em aparelho
modesto e rede lenta. O design de telas do Figma original está **aposentado** — serve só
como registro histórico da ideia (`docs/product/design/figma/`).
Rationale: o produto é de uso ocasional e social — ninguém "aprende" a usá-lo num
treinamento; ou a tela se explica sozinha, ou a pessoa desiste e volta pro Instagram.

## Stack Tecnológico Obrigatório

Backend: PHP 8.4+, Laravel 13.x, MySQL 8.0+, Redis 7.0+. **Banco único** — sem
multi-tenancy (ADR-0001). RBAC via `spatie/laravel-permission` (papéis do Princípio I).
Audit log via `spatie/laravel-activitylog`. Autenticação de API via `laravel/sanctum`;
login social Google via `laravel/socialite`. Qualquer mudança nessas escolhas fundacionais
exige emenda a esta constituição.

Frontend: aplicação separada no mesmo repositório (`web/`), desacoplada do backend
(`api/`), comunicação exclusivamente via API pública — ver ADR-0002. Framework de frontend
**PENDENTE** (critérios eliminatórios: SEO do catálogo público e acessibilidade — decide
antes da primeira tela).

PENDENTE (não ratificados, exigem decisão antes da spec que depender deles): framework de
frontend (bloqueia a primeira tela — Princípio XI), hospedagem, provedor de mapas/rotas (e
seu custo), provedor de push, provedor de e-mail transacional, gateway de pagamento
(Fase 3 — bilheteria). Ver `docs/logs/backlog.md`.

## Fluxo de Desenvolvimento

Antes de finalizar qualquer tarefa, verificar: papéis resolvidos a partir da conta única
(nunca conta paralela); nenhuma funcionalidade de consumo condicionada a pagamento;
conteúdo externo só via API oficial com atribuição; FormRequest com `authorize()`/`rules()`
em toda ação; Policy checada em todo controller action sensível; resposta sempre via API
Resource; operações lentas em Jobs; auditoria de escrita gerada; cenários de teste da spec
implementados, incluindo os que provam os bloqueios NON-NEGOTIABLE; nenhum dado pessoal em
log; **frontend consumindo só a API pública (nenhum atalho de acoplamento); tela da
feature implementada e conforme `ux-requirements.md`; testes de backend e frontend
executados e aprovados; documentação da API da feature atualizada; validação visual do
Ícaro obtida antes de abrir a próxima feature**. Sessões de agente que alterarem código
MUST deixar rastro do que foi feito, o que ficou pendente e quais comandos aplicam a
mudança (migrations, seeders), para a próxima sessão retomar sem re-explorar do zero.

## Governance

Esta constituição prevalece sobre convenções informais. Emendas exigem descrição,
justificativa e atualização do Sync Impact Report. Versionamento semver: MAJOR remove ou
redefine princípio; MINOR adiciona ou expande; PATCH clarifica redação. Revisões de código
verificam conformidade com os Princípios I–XII antes de merge.

**Version**: 1.1.1 | **Ratified**: 2026-08-28 | **Last Amended**: 2026-08-28
