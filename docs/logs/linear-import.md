# Linear — estrutura a criar (aguardando autenticação do conector)

Decisão do Ícaro (2026-08-28): projeto **"iBar"** no **mesmo time do Nexa** (mesmo time de
pessoas, produto separado). Este arquivo é o roteiro de importação: quando o conector do
Linear estiver autorizado, criar exatamente o que está abaixo e marcar aqui o que foi
criado.

## Projeto

- **Nome:** iBar (renomear para "Bora" quando o registro de marca confirmar — backlog)
- **Descrição:** Plataforma web que conecta público, bares/restaurantes e artistas em
  torno de eventos de música ao vivo. Gratuito para o usuário final; Freemium B2B.
  Validação em Juazeiro-BA e Petrolina-PE. Método Spec Kit — repositório é a fonte da
  verdade (`C:\wamp64\www\ibar`).
- **Marcos (milestones):**
  1. **M0 — Fundação** (constituição ratificada ✔, decisões de base do backlog, setup do
     repositório Laravel)
  2. **M1 — Contas** (spec 001: conta única multi-papel, login Google/e-mail)
  3. **M2 — Catálogo** (locais + artistas: cadastro, perfis públicos, categorias/gêneros)
  4. **M3 — Eventos** (criação pelo local, convite e confirmação do artista, feed do dia)
  5. **M4 — Descoberta** (busca, filtros, salvos, rotas, avaliações)
  6. **M5 — Lançamento assistido** (onboarding Juazeiro/Petrolina, divisão de conta,
     notificações)

## Issues iniciais

### Rotuladas `decisão` (uma issue por linha da tabela do backlog)
Criar uma issue por item de `docs/logs/backlog.md` → "Decisões pendentes que bloqueiam
spec", com título `Decisão: <item>`, descrição apontando a RN/doc de origem, e o marco
correspondente ao que ela bloqueia.

### De método/setup (marco M0)
- `Setup: repositório Laravel 13 conforme constituição (ADR-0001)`
- `Setup: autenticar conector Linear e validar fluxo tasks→issues`
- `Decisão: registro de marca Bora (INPI) + domínio` (prioridade alta)
- `Spec 001: contas e autenticação — rodar /specify` (primeira spec — ver backlog)

### Convenção contínua (igual ao Nexa)
- Cada spec aprovada gera issues a partir do `tasks.md` com título `T001: <descrição>`
  (deduplicar por ID `T\d{3}` antes de criar).
- Issues de erro referenciam `E-NNN` do error-log.

## Estado

- [ ] Projeto criado no Linear
- [ ] Marcos criados
- [ ] Issues de decisão criadas
- [ ] Issues de setup criadas
