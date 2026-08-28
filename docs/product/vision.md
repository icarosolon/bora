# Bora — Visão de Produto

Status: aprovado. Ideia extraída do Figma "App Rolezeiros" e das anotações do Ícaro;
decisões de nome, stack e monetização confirmadas por Ícaro em 2026-08-28. **Bora é o nome
oficial da solução** (registro INPI pendente; codinome de repositório: iBar).

## O que é

Bora é uma plataforma que responde **"onde tem rolê hoje?"** — conecta o público a bares e
restaurantes com música ao vivo e aos artistas que tocam neles. Três lados:

- **Rolezeiro (usuário final)** — descobre eventos, locais e artistas; segue, salva, avalia
  e recebe recomendações personalizadas. **Sempre gratuito** (Princípio II da constituição).
- **Estabelecimento (bar/restaurante)** — cria e gerencia seu perfil, publica eventos e
  convida artistas. É de quem vem a receita (Freemium B2B — `monetization.md`).
- **Artista/banda** — mantém perfil com gêneros, agenda e avaliações; confirma participação
  em eventos.

Lançamento como **site responsivo** com login via Google ou cadastro na plataforma —
frontend e API construídos **juntos neste projeto, porém desacoplados** (ADR-0002): o site
consome exclusivamente a API pública. O **app mobile será lançado quando o site tiver boa
aceitação**, consumindo a mesma API sem mudança estrutural no backend (Constituição,
Princípio IV).

**Design:** o layout do Figma original foi aposentado. O requisito vigente é um design
**interativo, simples e muito fácil de usar para todos os públicos** — inclusive quem tem
pouca familiaridade com celular, como idosos. Requisitos vinculantes em
`ux-requirements.md` (Constituição, Princípio XII).

**Entrega:** nenhuma funcionalidade é considerada pronta "só no backend" — cada feature
sai com API + tela + testes automatizados (back e front) e é validada visualmente pelo
Ícaro antes da próxima começar (Constituição, Princípio XI).

## Onde começa

Juazeiro-BA e Petrolina-PE, com implantação assistida junto aos primeiros clientes. A
plataforma é **independente e aberta**: estabelecimentos, artistas e usuários de qualquer
cidade podem se cadastrar sozinhos — a expansão não depende de operação nova por cidade.

## Funcionalidades núcleo

### Catálogo e perfis
- Perfil de **local**: fotos/logo, descrição, categorias, telefone (clique-para-ligar),
  endereço com geolocalização, likes, comentários/avaliações, Instagram.
- Perfil de **artista**: foto, bio, gêneros musicais (Forró, Samba, Pagode, Sertanejo…),
  avaliações, agenda de eventos confirmados.
- **Salvos**: usuário salva locais e artistas favoritos (telas "Locais salvos" e
  "Artistas salvos" do Figma).

### Eventos
- **Quem cria o evento é o estabelecimento**, que convida o grupo musical; o artista
  **precisa confirmar a participação** (`RN-EVENTO-002`).
- Evento tem data, hora, local, atração, valor de entrada/couvert (informativo na Fase 1),
  links de Instagram do local e do artista, comentários.
- Compartilhamento de evento (ícone de share na tela de detalhe).

### Descoberta
- Feed "O que temos para hoje?" com eventos do dia por cidade.
- Busca e **filtros por categoria de local e gênero musical**.
- **Personalização**: a plataforma aprende quais locais o usuário mais frequenta e quais
  categorias/gêneros prefere, para ordenar listagens e disparar notificações relevantes
  (com consentimento LGPD — Princípio III).
- **Rotas até o local** (integração com provedor de mapas — PENDENTE escolha).
- Avaliações do Google Maps: **PENDENTE viabilidade** — só entra via API oficial (Places),
  que retorna no máximo 5 avaliações por local e exige atribuição; scraping é proibido pela
  constituição. Ver backlog.

### Utilidades
- **Calculadora de divisão de conta** (tela "Divida a sua conta aqui" do Figma) —
  utilitário gratuito, sem processamento de pagamento na Fase 1.

## Fases

1. **Fase 1 — MVP site (validação):** cadastro/login, perfis de local e artista, eventos
   com confirmação de artista, feed + busca + filtros, salvos, avaliações próprias da
   plataforma, rotas, divisão de conta. Tudo gratuito; onboarding assistido em
   Juazeiro/Petrolina.
2. **Fase 2 — Monetização B2B:** plano pago para estabelecimentos (destaque, eventos
   ilimitados, analytics) + destaque patrocinado no feed. Ver `monetization.md`.
3. **Fase 3 — Bilheteria e app:** venda de ingresso/couvert com comissão; **app mobile**
   (gatilho: boa aceitação do site) consumindo a API existente sem retrabalho de backend.

## Fora de escopo agora

App nativo, venda de ingressos, reserva de mesa, delivery/cardápio, pagamento da conta pelo
app (a calculadora só divide o valor). São posteriores à validação da Fase 1.
