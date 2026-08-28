<!--
Sync Impact Report
- Version change: — → 1.0.0 (ratificação inicial)
- Source: extração da ideia original (Figma "App Rolezeiros" + anotações do Ícaro) e
  decisões de 2026-08-28: nome de trabalho "Bora" (registro de marca PENDENTE), stack
  reaproveitada do Nexa sem multi-tenancy, monetização Freemium B2B em fases.
- Core Principles: I–X definidos nesta ratificação.
- Follow-up TODOs: framework de frontend, hospedagem, provedor de mapas/rotas, provedor de
  push, provedor de e-mail transacional e gateway de pagamento (Fase 3) seguem PENDENTE —
  ver docs/logs/backlog.md.
-->

# Bora (iBar) Constitution

## Escopo

Bora é uma plataforma web (marketplace de três lados) que conecta **público**,
**estabelecimentos** (bares e restaurantes) e **artistas/bandas** em torno de eventos de
música ao vivo. Lançamento como site responsivo; app nativo é fase posterior. Validação
inicial nas cidades de Juazeiro-BA e Petrolina-PE; a plataforma é aberta — qualquer
estabelecimento, artista ou usuário pode se cadastrar sozinho.

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
Rationale: o site é o primeiro cliente, o app nativo é o segundo já anunciado; um contrato
estável evita reescrever o backend quando o app chegar.

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
pagamento; conta duplicada é rejeitada/unificada; import sem atribuição falha). Cenário de
falha previsível não coberto pela spec: o agente MUST parar e apresentar o gap antes de
implementar — nunca decidir sozinho.
Rationale: teste de caminho feliz documenta intenção, não comportamento sob estresse.

### X. Preservação do Histórico (NON-NEGOTIABLE)
Entidade que já produziu dado referenciado (evento realizado, avaliação publicada,
estabelecimento com histórico) **não é excluída — é inativada**. Pedido de eliminação de
dado pessoal do titular atende-se por **anonimização irreversível**, preservando o registro
histórico (avaliações e métricas continuam fechando; a pessoa deixa de ser identificável).
Quando o sistema bloquear uma exclusão, MUST informar qual condição a impede.
Rationale: o histórico de eventos e avaliações é o próprio produto (reputação de locais e
artistas); apagar histórico corrompe a confiança que a plataforma vende.

## Stack Tecnológico Obrigatório

Backend: PHP 8.4+, Laravel 13.x, MySQL 8.0+, Redis 7.0+. **Banco único** — sem
multi-tenancy (ADR-0001). RBAC via `spatie/laravel-permission` (papéis do Princípio I).
Audit log via `spatie/laravel-activitylog`. Autenticação de API via `laravel/sanctum`;
login social Google via `laravel/socialite`. Qualquer mudança nessas escolhas fundacionais
exige emenda a esta constituição.

PENDENTE (não ratificados, exigem decisão antes da spec que depender deles): framework de
frontend, hospedagem, provedor de mapas/rotas (e seu custo), provedor de push, provedor de
e-mail transacional, gateway de pagamento (Fase 3 — bilheteria). Ver
`docs/logs/backlog.md`.

## Fluxo de Desenvolvimento

Antes de finalizar qualquer tarefa, verificar: papéis resolvidos a partir da conta única
(nunca conta paralela); nenhuma funcionalidade de consumo condicionada a pagamento;
conteúdo externo só via API oficial com atribuição; FormRequest com `authorize()`/`rules()`
em toda ação; Policy checada em todo controller action sensível; resposta sempre via API
Resource; operações lentas em Jobs; auditoria de escrita gerada; cenários de teste da spec
implementados, incluindo os que provam os bloqueios NON-NEGOTIABLE; nenhum dado pessoal em
log. Sessões de agente que alterarem código MUST deixar rastro do que foi feito, o que
ficou pendente e quais comandos aplicam a mudança (migrations, seeders), para a próxima
sessão retomar sem re-explorar do zero.

## Governance

Esta constituição prevalece sobre convenções informais. Emendas exigem descrição,
justificativa e atualização do Sync Impact Report. Versionamento semver: MAJOR remove ou
redefine princípio; MINOR adiciona ou expande; PATCH clarifica redação. Revisões de código
verificam conformidade com os Princípios I–X antes de merge.

**Version**: 1.0.0 | **Ratified**: 2026-08-28 | **Last Amended**: 2026-08-28
