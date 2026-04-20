# Mapa de Contexto - JobFlow Platform

## Visão Geral
Este documento descreve os **6 Bounded Contexts** que compõem a arquitetura do JobFlow Platform, seguindo os princípios de Domain-Driven Design (DDD). Cada contexto possui responsabilidade clara, modelo de domínio isolado e contratos de integração bem definidos.

## Bounded Contexts Identificados

| Contexto | Responsabilidade Principal | Componentes-Chave |
|----------|---------------------------|-------------------|
| **JobCatalog** | Gestão do ciclo de vida de vagas | JobAggregate (Job, JobPosting, Category) |
| **CandidateProfile** | Perfil profissional e preferências | CandidateAggregate (Profile, Skills, Preferences) |
| **MatchingEngine** | Algoritmo de matching candidato-vaga | MatchingService (Scoring, Ranking) |
| **ResumeProcessing** | Parsing e extração de currículos | ResumeService (PDF/DOCX Parser, NLP Extractor) |
| **Analytics** | Métricas, dashboards e relatórios | ReportingService (Dashboards, Metrics) |
| **Identity** | Autenticação, autorização e perfis | AuthContext (User, Roles, Permissions) |

## Relações e Integrações

### JobCatalog → MatchingEngine
- **Tipo:** Customer-Supplier (Event-Driven)
- **Comunicação:** Eventos de domínio via RabbitMQ
- **Contrato:** `JobPublished`, `JobUpdated`, `JobClosed`
- **Justificativa:** O MatchingEngine consome vagas para indexar no Elasticsearch e calcular scores em tempo real. O JobCatalog é o "fornecedor" da verdade sobre o estado das vagas, enquanto MatchingEngine é o "cliente" que depende desses dados.

### ResumeProcessing → CandidateProfile
- **Tipo:** Conformist
- **Comunicação:** HTTP Síncrono (REST API)
- **Contrato:** `POST /api/candidates/{id}/resumes` com schema definido por CandidateProfile
- **Justificativa:** ResumeProcessing adapta seu output ao schema esperado por CandidateProfile, evitando acoplamento bidirecional. O contexto downstream (ResumeProcessing) segue o modelo do upstream.

### MatchingEngine ↔ Analytics
- **Tipo:** Shared Kernel
- **Comunicação:** OpenTelemetry + Métricas Estruturadas
- **Contrato:** Schema compartilhado `MatchMetric { jobId, candidateId, score, latency, timestamp }`
- **Justificativa:** Ambos os contextos precisam concordar na definição de métricas de matching para garantir consistência nos dashboards e no algoritmo de scoring.

### Identity → Todos os Contextos
- **Tipo:** Upstream-Downstream (Identity Provider)
- **Comunicação:** JWT Claims + OAuth2/OIDC
- **Contrato:** Claims padronizadas `sub`, `roles`, `permissions`, `tenant_id`
- **Justificativa:** Identity é a fonte única de verdade para autenticação e autorização. Todos os outros contextos confiam nos tokens emitidos (AuthZ Token), sem replicar lógica de auth.

### CandidateProfile → MatchingEngine
- **Tipo:** Partnership
- **Comunicação:** Eventos via RabbitMQ (`ProfileUpdated`, `SkillAdded`)
- **Contrato:** Schema evolutivo com versionamento semântico (`v1`, `v2`)
- **Justificativa:** Ambos os contextos co-evoluem: mudanças no perfil do candidato impactam diretamente o algoritmo de matching. Requer comunicação próxima e testes de contrato.

### Todos os Contexts → Analytics (Observabilidade)
- **Tipo:** Observability Sink
- **Comunicação:** OpenTelemetry (traces, metrics, logs)
- **Contrato:** Atributos obrigatórios `service.name`, `jobflow.context`, `correlation_id`
- **Justificativa:** Analytics atua como coletor central de telemetria, permitindo debugging distribuído e monitoramento de SLOs sem acoplamento funcional.

## Decisões Arquiteturais

1. **Event-Driven para fluxos assíncronos**: RabbitMQ foi escolhido para comunicação entre JobCatalog, MatchingEngine e CandidateProfile, garantindo resiliência e escalabilidade horizontal.

2. **HTTP Síncrono apenas para consultas críticas**: ResumeProcessing usa HTTP para enviar currículos parseados porque CandidateProfile precisa validar e persistir imediatamente.

3. **Shared Kernel mínimo**: Apenas o schema de métricas é compartilhado entre MatchingEngine e Analytics, reduzindo superfície de acoplamento.

4. **Identity como Upstream**: Todos os contextos dependem de Identity, mas Identity não depende de nenhum outro, seguindo o princípio de inversão de dependência.

5. **Observabilidade como cross-cutting concern**: OpenTelemetry é injetado via middleware, sem poluir o domínio dos bounded contexts.

## Referências
- Evans, Eric. Domain-Driven Design: Tackling Complexity in the Heart of Software
- Vernon, Vaughn. Context Mapping Patterns
- Fowler, Martin. Bounded Context