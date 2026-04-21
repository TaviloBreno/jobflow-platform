# FASE 0 - Entrega Completa (JobFlow Platform)

> Documento consolidado para fechamento da Fase 0 (Fundação e Arquitetura).
> Linguagem: PT-BR com termos técnicos padrão em inglês.

---

## 1) Template de Pull Request (feature/fase0-documentacao -> develop)

### Título sugerido
`docs(fase0): consolidar arquitetura, eventos de domínio, OpenAPI e estratégia de testes`

### Descrição (copiar e colar no PR)

## Objetivo
Concluir a Fase 0 do JobFlow Platform com artefatos arquiteturais versionados, rastreáveis e prontos para onboarding técnico e apresentação de stakeholders.

## Escopo desta PR
- Consolidação dos ADRs de decisão arquitetural.
- Catálogo de eventos de domínio e alinhamento com context map.
- OpenAPI inicial com exemplos realistas e schemas reutilizáveis.
- Estratégia de testes com pirâmide adaptada ao domínio.
- Material executivo (README de fase + apresentação).

## Impacto no negócio
- Reduz risco de retrabalho arquitetural na Fase 1.
- Aumenta previsibilidade de integração entre times/contextos.
- Melhora velocidade de onboarding e tomada de decisão técnica.

## Checklist de Auto-Review (preenchível)

### ADRs e trade-offs
- [ ] ADR-001 documenta claramente ganhos e custos operacionais da estratégia de Polyglot Persistence.
- [ ] ADR-002 justifica RabbitMQ versus alternativas com foco em confiabilidade (DLQ, ack, retries).
- [ ] ADR-003 explicita impacto de Clean Architecture + DTOs imutáveis em testabilidade e manutenção.
- [ ] Cada ADR possui alternativa rejeitada e motivo explícito.
- [ ] Cada decisão traz seção "Por que isso importa para o negócio".

### Linguagem ubíqua e feature files
- [ ] Termos de domínio (Job, Candidate, Matching, Resume, Application) estão consistentes entre docs, testes e OpenAPI.
- [ ] Cenários Gherkin evitam termos técnicos de infraestrutura quando o foco é regra de negócio.
- [ ] Nomes de eventos estão em Past Tense e semanticamente coerentes (`JobPublished`, `CandidateAppliedToJob`).

### Context map x catálogo de eventos
- [ ] Relações entre os 6 bounded contexts refletem os emissores/consumidores do catálogo de eventos.
- [ ] Eventos de integração críticos do fluxo de vagas e candidatura estão cobertos.
- [ ] Contratos de integração indicam versão/evolução semântica quando aplicável.

### Testes e invariantes
- [ ] Testes unitários cobrem invariantes críticas de domínio (SalaryRange, estado de Job, consistência de perfil).
- [ ] Estratégia de testes respeita distribuição alvo da pirâmide (50/25/20/5).
- [ ] Casos de idempotência e deduplicação em consumidores de eventos possuem cobertura mínima de unidade/integrado.
- [ ] Grupos de teste (`unit`, `integration`, `feature`) estão coerentes com `phpunit.xml`.

### OpenAPI
- [ ] Endpoints principais têm exemplos de request/response realistas.
- [ ] Schemas comuns (erros, localização, salário) são reutilizados via `components/schemas`.
- [ ] Códigos de erro e validação estão padronizados.
- [ ] Referências cruzadas para ADRs/eventos estão presentes na descrição da API.

### Qualidade de documentação
- [ ] Todos os links entre artefatos funcionam.
- [ ] Terminologia está consistente em PT-BR com termos técnicos em inglês.
- [ ] Documento está compreensível para público técnico e não técnico.

## Comentários explicativos em decisões controversas

1. Polyglot Persistence (ADR-001)
   - Comentário: decisão poderosa para escalar busca e analytics, porém aumenta custo operacional. A mitigação proposta é adoção progressiva por contexto e observabilidade desde o início.

2. RabbitMQ em vez de Redis Pub/Sub (ADR-002)
   - Comentário: priorizamos confiabilidade e trilha de falhas (DLQ) sobre simplicidade inicial. Isso reduz risco de perda de eventos de negócio críticos (ex.: `JobPublished`).

3. Clean Architecture + DTOs no Laravel (ADR-003)
   - Comentário: há overhead de mapeamento e curva de aprendizado, mas o ganho em testabilidade, isolamento de domínio e evolução futura compensa no médio prazo.

4. Shared Kernel mínimo entre MatchingEngine e Analytics
   - Comentário: limitamos compartilhamento ao schema de métricas para evitar acoplamento estrutural entre contextos.

## Melhorias técnicas sugeridas (peer review)

1. Introduzir Contract Tests para eventos RabbitMQ
   - Publicador valida schema em CI; consumidor valida backward compatibility por versão.

2. Implementar Outbox Relay com monitoramento de lag
   - Métrica de atraso entre persistência e publicação para detectar gargalos de entrega.

3. Adotar versionamento explícito de payload (ex.: `event_version`)
   - Facilita evolução sem quebrar consumidores legados.

4. Incluir lint de OpenAPI no pipeline
   - Exemplo: Spectral para regras de naming, exemplos obrigatórios e consistência de erros.

5. Criar scorecard de qualidade da documentação
   - Checklist automático para links quebrados, rastreabilidade ADR -> Eventos -> Testes.

6. Endurecer padrão de idempotência
   - Tabela de deduplicação por `event_id` + janelas de retenção + política de retry/DLQ documentada.

---

## 2) Conteúdo completo do README-FASE0.md

# README - Fase 0 (Fundação e Arquitetura)

![Documentation](https://img.shields.io/badge/Documentation-Fase%200-blue)
![ADRs](https://img.shields.io/badge/ADRs-5%20decisions-success)
![Gherkin Scenarios](https://img.shields.io/badge/Gherkin-Scenarios%20Defined-orange)

## Resumo Executivo
A Fase 0 do JobFlow Platform estabeleceu a base arquitetural e documental que orienta as próximas entregas de produto. O foco foi reduzir incerteza técnica antes de acelerar implementação, definindo bounded contexts, padrões de integração assíncrona, decisões arquiteturais registradas em ADRs e estratégia de testes alinhada ao domínio.

Com isso, o projeto passa a ter contratos explícitos entre contextos (API e eventos), linguagem ubíqua compartilhada e rastreabilidade entre decisão, impacto e validação. Essa fundação aumenta previsibilidade para evoluir funcionalidades sem elevar acoplamento acidental ou custo de manutenção.

Para o negócio, o ganho principal é velocidade com segurança: a equipe consegue iniciar a Fase 1 com menor risco de retrabalho, melhor onboarding de novos membros e maior clareza para stakeholders sobre escopo, riscos e critérios de qualidade.

## Artefatos Entregues

| Artefato | Link | Propósito |
|---|---|---|
| Context Mapping | [docs/context-mapping.md](docs/context-mapping.md) | Definir fronteiras de domínio e relações entre os 6 bounded contexts |
| Domain Events Catalog | [docs/domain-events-catalog.md](docs/domain-events-catalog.md) | Especificar contratos de eventos, emissores, consumidores e idempotência |
| Test Pyramid | [docs/testing-pyramid.md](docs/testing-pyramid.md) | Padronizar estratégia de testes e distribuição por nível |
| ADR-001 | [docs/adr/ADR-001-polyglot-persistence.md](docs/adr/ADR-001-polyglot-persistence.md) | Justificar Polyglot Persistence e trade-offs operacionais |
| ADR-002 | [docs/adr/ADR-002-mensageria-rabbitmq.md](docs/adr/ADR-002-mensageria-rabbitmq.md) | Justificar RabbitMQ para integração assíncrona confiável |
| ADR-003 | [docs/adr/ADR-003-clean-architecture-dtos.md](docs/adr/ADR-003-clean-architecture-dtos.md) | Definir Clean Architecture com DTOs imutáveis |
| ADR-004 | [docs/adr/ADR-004-test-pyramid-adaptada.md](docs/adr/ADR-004-test-pyramid-adaptada.md) | Formalizar adaptação da pirâmide de testes |
| ADR-005 | [docs/adr/ADR-005-observabilidade-otel-telescope.md](docs/adr/ADR-005-observabilidade-otel-telescope.md) | Direcionar observabilidade distribuída |
| OpenAPI v1 | [docs/api/openapi.yaml](docs/api/openapi.yaml) | Contrato HTTP versionado com exemplos e schemas reutilizáveis |
| Coleção Postman | [docs/postman/JobFlow.postman_collection.json](docs/postman/JobFlow.postman_collection.json) | Facilitar exploração e validação manual da API |
| Diagrama Context Map | [docs/diagrams/context-map.puml](docs/diagrams/context-map.puml) | Representação visual da arquitetura de contexto |

## Comandos de Validação por Artefato

### Bash (Linux/macOS/Git Bash)
```bash
# 1) Validar sintaxe OpenAPI
npx @redocly/cli lint docs/api/openapi.yaml

# 2) Rodar testes unitários do backend
cd backend && php artisan test --group unit

# 3) Rodar testes de integração
cd backend && php artisan test --group integration

# 4) Rodar suíte completa de testes
cd backend && php artisan test

# 5) Verificar links em markdown (exemplo com lychee)
lychee --no-progress docs/**/*.md README-FASE0.md

# 6) Renderização local do PlantUML (opcional)
plantuml docs/diagrams/context-map.puml
```

### PowerShell (Windows)
```powershell
# 1) Validar sintaxe OpenAPI
npx @redocly/cli lint docs/api/openapi.yaml

# 2) Rodar testes unitários
Set-Location backend; php artisan test --group unit

# 3) Rodar testes de integração
Set-Location backend; php artisan test --group integration

# 4) Rodar suíte completa
Set-Location backend; php artisan test

# 5) Verificar links markdown (se lychee disponível)
lychee --no-progress docs/**/*.md README-FASE0.md

# 6) Gerar diagrama PlantUML (opcional)
plantuml docs/diagrams/context-map.puml
```

## Glossário de Domínio

| Termo | Definição |
|---|---|
| Job | Vaga de emprego publicada no contexto JobCatalog |
| Candidate | Profissional com perfil cadastrado na plataforma |
| Candidate Profile | Conjunto estruturado de dados profissionais e preferências do candidato |
| Matching | Processo de cálculo de aderência entre Candidate e Job |
| Match Score | Score numérico de aderência calculado pelo MatchingEngine |
| Resume | Currículo enviado para parsing e extração de sinais |
| Resume Parsing | Extração automatizada de skills, experiências e educação de um currículo |
| Application | Ato de candidatura do Candidate a um Job |
| Bounded Context | Fronteira semântica e técnica de um subdomínio |
| Domain Event | Fato de negócio ocorrido no passado, usado para integração assíncrona |
| Outbox Pattern | Padrão para publicação confiável de eventos junto da transação de negócio |
| DLQ (Dead Letter Queue) | Fila para mensagens com falha de processamento |
| Ubiquitous Language | Vocabulário comum entre negócio e tecnologia |
| Invariant | Regra de negócio que deve permanecer sempre verdadeira |

## Próximos Passos (Fase 1)

| Item | Descrição | Data Estimada | Responsável |
|---|---|---|---|
| Infra local com Docker Compose | Subir serviços de apoio (RabbitMQ, bancos, observabilidade) | 2026-04-27 | Tech Lead + DevOps |
| Bootstrap de módulos de domínio | Estruturar camadas Domain/Application/Infrastructure/Presentation | 2026-05-01 | Backend Team |
| Pipeline CI inicial | Testes unitários + lint OpenAPI + validação de docs | 2026-05-04 | Platform Engineer |
| Observabilidade base | OpenTelemetry + tracing distribuído + logs estruturados | 2026-05-08 | SRE/Platform |
| Primeiros casos de uso ponta a ponta | Publicar Job, completar Profile, calcular Match | 2026-05-15 | Squad Core |
| Contratos de eventos versionados | Introduzir `event_version` e testes de contrato | 2026-05-19 | Architecture Guild |

---

## 3) Conteúdo dos 7 slides (Markdown compatível com Slidev)

> Sugestão de arquivo: `docs/presentation/fase0-stakeholders-slidev.md`

```md
---
layout: cover
title: JobFlow Platform - Fase 0 Completa
---

# JobFlow Platform
## Fase 0 Completa

Fundação arquitetural para escalar produto com segurança.

- Data: 20/04/2026
- Público: Stakeholders de Produto, Engenharia e Operações
- Status: Pronto para entrada na Fase 1

---
layout: default
---

# O que é JobFlow

## Problema
- Processos de recrutamento fragmentados e lentos.
- Baixa qualidade de matching entre vaga e candidato.
- Pouca rastreabilidade dos fluxos críticos.

## Solução
- Plataforma orientada a domínio para publicação de vagas, perfil de candidatos e matching inteligente.
- Arquitetura event-driven para integrações assíncronas e escaláveis.

## Diferencial
- Decisões arquiteturais formalizadas em ADRs.
- Contratos explícitos de API e eventos.
- Estratégia de testes orientada a risco e velocidade.

---
layout: default
---

# Por que a Fase 0 importa

## Redução de risco
- Evita retrabalho estrutural na implementação da Fase 1.
- Define fronteiras claras de responsabilidade entre contextos.

## Alinhamento entre times
- Linguagem ubíqua comum entre negócio e engenharia.
- Contratos compartilhados (OpenAPI + Domain Events).

## Qualidade desde o início
- Test Pyramid definida com foco em invariantes críticas.
- Base para CI/CD e observabilidade distribuída.

> Resultado esperado: mais velocidade com menor custo de correção futura.

---
layout: default
---

# Mapa de Contexto (6 Bounded Contexts)

![Context Map](../diagrams/context-map.png)

## Contextos
1. JobCatalog
2. CandidateProfile
3. MatchingEngine
4. ResumeProcessing
5. Analytics
6. Identity

## Leitura executiva
- JobCatalog e CandidateProfile são produtores de eventos de negócio.
- MatchingEngine transforma eventos em inteligência de aderência.
- Analytics consolida métricas e telemetria transversal.
- Identity centraliza autenticação e autorização.

---
layout: default
---

# Exemplo de Evento de Domínio: JobPublished

## Payload (resumo)
```yaml
event: JobPublished
event_id: UUID
event_version: v1
occurred_at: ISO8601
payload:
  job_id: UUID
  title: string
  location: { city, country, is_remote }
  salary_range: { min, max, currency }
  required_skills: UUID[]
  expires_at: ISO8601
```

## Consumers
- MatchingEngine: indexação e recálculo de matches.
- Analytics: métricas de publicação e funil.

## Guarantees
- Publicação via Outbox (consistência transacional).
- Idempotência por `event_id`.
- Retry controlado + DLQ para falhas não transitórias.

---
layout: default
---

# Pirâmide de Testes

## Distribuição alvo
- Unit: 50%
- Integration: 25%
- Functional: 20%
- E2E: 5%

## Exemplos por nível
- Unit: invariantes de SalaryRange e transição de estado de Job.
- Integration: persistência + mensageria + outbox relay.
- Functional: fluxos HTTP com contratos de entrada/saída.
- E2E: jornada crítica de publicação e candidatura.

## Métricas de qualidade
- Cobertura de invariantes críticas: >= 90%
- Tempo médio suíte unitária: < 2 min
- Flakiness E2E: < 2%

---
layout: default
---

# Próximos Passos - Fase 1

## Entregas técnicas
- Docker Compose para ambiente local completo.
- Observabilidade base com OpenTelemetry.
- Pipeline CI/CD com quality gates.

## Entregas de produto
- Caso de uso end-to-end: publicar vaga e ranquear candidatos.
- Evolução dos contratos de eventos com versionamento.

## Marcos
- Semana 1: infraestrutura local + bootstrap arquitetural.
- Semana 2: primeiros casos de uso + telemetria.
- Semana 3: estabilização de pipeline e qualidade.

# Decisão de negócio
A Fase 1 inicia com risco controlado e alta previsibilidade de execução.
```

---

## 4) Template de GitHub Release com changelog

### Título da Release
`v0.1.0-fase0`

### Corpo da Release (template)

## JobFlow Platform - v0.1.0-fase0

### Resumo
Release de fechamento da Fase 0, consolidando fundação arquitetural, contratos de integração e estratégia de qualidade para habilitar a Fase 1.

### Highlights
- Definição formal dos 6 bounded contexts e suas relações.
- Catálogo de eventos de domínio com emissores, consumidores e garantias.
- ADRs de decisões críticas (persistência, mensageria, arquitetura, testes e observabilidade).
- OpenAPI v1 com exemplos realistas e schemas reutilizáveis.
- Diretriz de testes baseada em pirâmide adaptada ao domínio.

### Changelog formatado

#### Added
- Documento de Context Mapping com integrações entre JobCatalog, CandidateProfile, MatchingEngine, ResumeProcessing, Analytics e Identity.
- Catálogo de eventos de domínio com especificação detalhada de payloads e idempotência.
- OpenAPI inicial da plataforma com endpoints de Jobs e Candidates.
- Estrutura de ADRs com decisões arquiteturais e alternativas rejeitadas.
- Documento de estratégia de testes (Test Pyramid).

#### Changed
- Padronização da linguagem ubíqua entre documentação, eventos e API.
- Inclusão de links cruzados entre ADRs, eventos e contratos OpenAPI.

#### Security
- Diretriz de autenticação via Bearer JWT em endpoints protegidos.
- Recomendação de rastreabilidade por correlation/request id.

### Artefatos Relacionados
- docs/context-mapping.md
- docs/domain-events-catalog.md
- docs/testing-pyramid.md
- docs/adr/ADR-001-polyglot-persistence.md
- docs/adr/ADR-002-mensageria-rabbitmq.md
- docs/adr/ADR-003-clean-architecture-dtos.md
- docs/adr/ADR-004-test-pyramid-adaptada.md
- docs/adr/ADR-005-observabilidade-otel-telescope.md
- docs/api/openapi.yaml

### Notas de Upgrade
- Não há breaking changes de runtime nesta fase (escopo primário documental e arquitetural).
- A Fase 1 introduzirá bootstrap técnico e automações de pipeline.

---

## 5) Script Bash/PowerShell para merge, tag e fechamento

### Bash (Git Bash/Linux/macOS)
```bash
#!/usr/bin/env bash
set -euo pipefail

# Configuração
FEATURE_BRANCH="feature/fase0-documentacao"
TARGET_BRANCH="develop"
BASE_BRANCH="main"
TAG_NAME="v0.1.0-fase0"

# 1) Garantir base atualizada a partir de main
git checkout "$BASE_BRANCH"
git pull origin "$BASE_BRANCH"

# 2) Criar/atualizar branch de feature
git checkout -B "$FEATURE_BRANCH"

# 3) Commit estruturado (ajuste a mensagem se necessário)
git add docs/ README-FASE0.md FASE0-ENTREGA-COMPLETA.md .git/PULL_REQUEST_TEMPLATE.md || true
git commit -m "docs(fase0): consolidar arquitetura, eventos, openapi e estratégia de testes" || true

# 4) Push da feature
git push -u origin "$FEATURE_BRANCH"

# 5) Merge em develop (fluxo local)
git checkout "$TARGET_BRANCH"
git pull origin "$TARGET_BRANCH"
git merge --no-ff "$FEATURE_BRANCH" -m "merge(fase0): integrar documentação arquitetural e artefatos de fundação"
git push origin "$TARGET_BRANCH"

# 6) Tag de release
git tag -a "$TAG_NAME" -m "Release Fase 0: Fundação e Arquitetura"
git push origin "$TAG_NAME"

echo "Concluído: merge em $TARGET_BRANCH e tag $TAG_NAME criada."
```

### PowerShell (Windows)
```powershell
$ErrorActionPreference = "Stop"

# Configuração
$FeatureBranch = "feature/fase0-documentacao"
$TargetBranch  = "develop"
$BaseBranch    = "main"
$TagName       = "v0.1.0-fase0"

# 1) Garantir base atualizada a partir de main
git checkout $BaseBranch
git pull origin $BaseBranch

# 2) Criar/atualizar branch de feature
git checkout -B $FeatureBranch

# 3) Commit estruturado (ajuste se necessário)
git add docs/ README-FASE0.md FASE0-ENTREGA-COMPLETA.md .git/PULL_REQUEST_TEMPLATE.md
try {
    git commit -m "docs(fase0): consolidar arquitetura, eventos, openapi e estratégia de testes"
} catch {
    Write-Host "Sem mudanças para commit ou commit já realizado."
}

# 4) Push da feature
git push -u origin $FeatureBranch

# 5) Merge em develop
git checkout $TargetBranch
git pull origin $TargetBranch
git merge --no-ff $FeatureBranch -m "merge(fase0): integrar documentação arquitetural e artefatos de fundação"
git push origin $TargetBranch

# 6) Tag de release
git tag -a $TagName -m "Release Fase 0: Fundação e Arquitetura"
git push origin $TagName

Write-Host "Concluído: merge em $TargetBranch e tag $TagName criada."
```

### Comando direto para tag (atalho)

```bash
git tag -a v0.1.0-fase0 -m "Release Fase 0: Fundação e Arquitetura" && git push origin v0.1.0-fase0
```

```powershell
git tag -a v0.1.0-fase0 -m "Release Fase 0: Fundação e Arquitetura"; git push origin v0.1.0-fase0
```

### Commit message estruturada (sugestão final)

`merge(fase0): finalizar fundação arquitetural com ADRs, OpenAPI, eventos e estratégia de testes`

Corpo sugerido:
- consolida context map e domain events catalog
- formaliza decisões arquiteturais via ADR-001..005
- adiciona OpenAPI v1 com exemplos e schemas reutilizáveis
- documenta test pyramid e critérios de qualidade
- prepara material executivo para stakeholders e release v0.1.0-fase0

### Atualização do GitHub Project (cards -> Done)

1. Abrir Project board vinculado ao repositório.
2. Filtrar por itens da Fase 0 (label: `fase-0` ou milestone correspondente).
3. Mover para `Done` os cards:
   - Context Mapping
   - ADRs 001-005
   - Domain Events Catalog
   - OpenAPI v1
   - Testing Pyramid
   - README-FASE0
   - Stakeholders Deck
4. Em cada card, anexar link da PR e tag `v0.1.0-fase0`.
5. Registrar comentário de encerramento com riscos remanescentes e plano da Fase 1.

---

## 6) Checklist final de validação antes do merge

### Qualidade técnica
- [ ] `docs/context-mapping.md` reflete exatamente os 6 bounded contexts e integrações.
- [ ] `docs/domain-events-catalog.md` cobre eventos críticos e idempotência por consumidor.
- [ ] `docs/api/openapi.yaml` passa em lint e contém exemplos coerentes com regras de domínio.
- [ ] ADRs possuem status, contexto, decisão, consequências e alternativas rejeitadas.
- [ ] Estratégia de testes está alinhada ao `phpunit.xml` e aos grupos executáveis.

### Consistência e rastreabilidade
- [ ] Há links cruzados ADR -> Eventos -> OpenAPI -> Testes.
- [ ] Terminologia de domínio está consistente em todos os documentos.
- [ ] Decisões controversas têm justificativa técnica e impacto de negócio explícito.

### Operacional
- [ ] Branch de feature atualizada a partir de `main`.
- [ ] PR aberta de `feature/fase0-documentacao` para `develop`.
- [ ] Review checklist preenchido e sem bloqueios críticos.
- [ ] Merge executado sem conflitos pendentes.
- [ ] Tag `v0.1.0-fase0` criada e enviada ao remoto.
- [ ] Release publicada com changelog formatado.
- [ ] GitHub Project atualizado com cards da Fase 0 em `Done`.

---

## 7) Matriz rápida de trade-off (apoio para discussão executiva)

| Decisão | Opção escolhida | Alternativa | Ganho principal | Custo/Risco | Mitigação |
|---|---|---|---|---|---|
| Broker de mensageria | RabbitMQ | Redis Pub/Sub | Confiabilidade e DLQ nativa | Maior operação | IaC + observabilidade + runbooks |
| Persistência | Polyglot Persistence | Banco único | Performance por workload | Complexidade de stack | Adoção incremental por contexto |
| Arquitetura backend | Clean Architecture + DTOs | MVC acoplado | Testabilidade e evolução | Boilerplate inicial | Templates, codegen e padrões de mapeamento |

### Por que isso importa para o negócio
- Menos incidentes em fluxos críticos de recrutamento.
- Menor custo de mudança ao longo da evolução do produto.
- Maior previsibilidade de prazo na Fase 1 e além.
