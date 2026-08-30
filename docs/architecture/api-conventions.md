# Convenções de API

As convenções de API são as da constituição: `.specify/memory/constitution.md`
(Princípio IV — API-First e Contrato Estável).

## Como isso se aplica na prática

As decisões abaixo saíram da spec 001 e valem para **todas** as features, não só para ela
(ver `specs/001-contas-autenticacao/contracts/auth-api.md`):

- **Versionamento**: tudo sob `/api/v1/...`. A rota `/api/user`, herdada do
  `install:api` e hoje fora do prefixo, **é movida** para `/api/v1/eu` na implementação da
  spec 001 — não existe endpoint fora do versionamento.
- **Autenticação**: token **Bearer** do Sanctum, o mesmo mecanismo que o futuro app mobile
  usará (Princípio IV). Sessão com prazo **deslizante** — o Sanctum não faz isso sozinho,
  é middleware nosso.
- **CORS**: `config/cors.php` publicado, com **origens explícitas**;
  `supports_credentials` permanece `false` (não há cookie atravessando).
- **Documentação**: **OpenAPI gerado pelo Scramble** a partir de FormRequests e Resources
  (decisão D3 da spec 001). O contrato revisado à mão vive na spec; o Scramble é o que
  publica. Divergência entre os dois é defeito, não questão de preferência.

**Status**: decidido e documentado em 2026-08-30; **ainda não implementado** — nenhum
pacote instalado, nenhuma rota criada.
