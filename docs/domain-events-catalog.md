# 📡 Catálogo de Eventos de Domínio (Domain Events Catalog)

> **Contexto:** JobFlow Platform  
> **ADR Referenciados:** ADR-002 (RabbitMQ), ADR-003 (Clean Architecture), ADR-004 (Testes)  
> **Padrão de Comunicação:** Assíncrono via RabbitMQ (Customer-Supplier / Partnership)  
> **Regra de Ouro:** Eventos representam **fatos passados** (`PastTense`). Consumidores devem ser idempotentes e tolerantes a falhas.

---

## 📊 Tabela de Referência Rápida

| Evento | Emissor | Consumidor(es) | Payload Resumo | Transacional? | Idempotente? |
|--------|---------|----------------|----------------|---------------|--------------|
| `JobPublished` | JobCatalog | MatchingEngine, Analytics | `jobId`, `title`, `location`, `salaryRange`, `expiresAt` | ✅ Sim (Outbox) | ✅ Sim |
| `JobClosed` | JobCatalog | MatchingEngine, Analytics | `jobId`, `closedAt`, `reason` | ✅ Sim (Outbox) | ✅ Sim |
| `CandidateProfileCompleted` | CandidateProfile | MatchingEngine, Analytics | `candidateId`, `completenessScore`, `primarySkills`, `completedAt` | ✅ Sim (Outbox) | ✅ Sim |
| `CandidateSkillsUpdated` | CandidateProfile | MatchingEngine | `candidateId`, `skillId`, `action`, `proficiency` | ✅ Sim (Outbox) | ✅ Sim |
| `ResumeParsedSuccessfully` | ResumeProcessing | CandidateProfile, MatchingEngine | `candidateId`, `resumeVersion`, `extractedSkills`, `experienceYears` | ❌ Não | ✅ Sim |
| `ResumeParsingFailed` | ResumeProcessing | CandidateProfile | `candidateId`, `resumeVersion`, `error`, `failedAt` | ❌ Não | ✅ Sim |
| `MatchScoreCalculated` | MatchingEngine | Analytics, Notification | `matchId`, `candidateId`, `jobId`, `score`, `calculatedAt` | ❌ Não | ✅ Sim |
| `CandidateAppliedToJob` | CandidateProfile | JobCatalog, Analytics, Notification | `applicationId`, `candidateId`, `jobId`, `appliedAt`, `resumeVersion` | ✅ Sim (Outbox) | ✅ Sim |

---

## 📦 Especificação Detalhada dos Eventos

### 1. `JobPublished`
```yaml
emissor: JobCatalog
consumidores:
    - MatchingEngine: "Indexa vaga no motor de busca e recalcula matches existentes"
    - Analytics: "Registra métrica de tempo-to-publish e volume por categoria"
exchange: jobflow.jobs
routing_key: job.published
payload:
    job_id: UUID (obrigatório)
    title: string (5-100 chars)
    location: { city, country, is_remote }
    salary_range: { min, max, currency }
    required_skills: array<UUID>
    published_at: ISO8601 (obrigatório)
    expires_at: ISO8601 (obrigatório)
transacional: "Sim. Publicado via Transactional Outbox na mesma transação que altera Job.status para PUBLISHED"
idempotente: "Sim. MatchingEngine e Analytics devem ignorar duplicatas via event_id único"
```

### 2. `JobClosed`
```yaml
emissor: JobCatalog
consumidores:
    - MatchingEngine: "Remove vaga do índice ativo e notifica candidatos aplicados"
    - Analytics: "Fecha ciclo de vida da vaga para métricas de fill-rate"
exchange: jobflow.jobs
routing_key: job.closed
payload:
    job_id: UUID
    closed_at: ISO8601
    reason: enum("MANUAL", "EXPIRED", "FILLED")
transacional: "Sim (Outbox)"
idempotente: "Sim. Processamento de fechamento deve ser seguro para reexecução"
```

### 3. `CandidateProfileCompleted`
```yaml
emissor: CandidateProfile
consumidores:
    - MatchingEngine: "Libera candidato para matching ativo"
    - Analytics: "Registra conversão de onboarding"
exchange: jobflow.candidates
routing_key: candidate.profile.completed
payload:
    candidate_id: UUID
    completeness_score: float (0.0-1.0)
    primary_skills: array<UUID>
    preferred_locations: array<{city, country, is_remote}>
    completed_at: ISO8601
transacional: "Sim (Outbox)"
idempotente: "Sim. Índice de matching deve usar `candidate_id` como chave de upsert"
```

### 4. `CandidateSkillsUpdated`
```yaml
emissor: CandidateProfile
consumidores:
    - MatchingEngine: "Recalcula scores parciais sem reindexar perfil completo"
exchange: jobflow.candidates
routing_key: candidate.skills.updated
payload:
    candidate_id: UUID
    skill_id: UUID
    action: enum("ADDED", "UPDATED", "REMOVED")
    proficiency: int (1-5)
    updated_at: ISO8601
transacional: "Sim (Outbox)"
idempotente: "Sim. Aplicação de delta de skills deve ser idempotente por `event_id`"
```

### 5. `ResumeParsedSuccessfully`
```yaml
emissor: ResumeProcessing
consumidores:
    - CandidateProfile: "Atualiza `extractedSkills` e `experienceYears` no perfil"
    - MatchingEngine: "Dispara re-indexação com dados extraídos"
exchange: jobflow.resumes
routing_key: resume.parsed.success
payload:
    candidate_id: UUID
    resume_version: int
    extracted_skills: array<{name, confidence}>
    experience_years: float
    education_levels: array<string>
    parsed_at: ISO8601
transacional: "Não. Processamento é assíncrono e pode falhar/retry"
idempotente: "Sim. CandidateProfile deve usar `resume_version` para evitar sobrescrita regressiva"
```

### 6. `ResumeParsingFailed`
```yaml
emissor: ResumeProcessing
consumidores:
    - CandidateProfile: "Marca resume como inválido e notifica usuário para reupload"
exchange: jobflow.resumes
routing_key: resume.parsed.failed
payload:
    candidate_id: UUID
    resume_version: int
    error_code: string
    error_message: string
    failed_at: ISO8601
transacional: "Não"
idempotente: "Sim. Notificação de falha deve ser deduplicada por `resume_version`"
```

### 7. `MatchScoreCalculated`
```yaml
emissor: MatchingEngine
consumidores:
    - Analytics: "Alimenta dashboards de matching accuracy e distribuição de scores"
    - Notification: "Dispara alerta se score > threshold configurado"
exchange: jobflow.matching
routing_key: match.score.calculated
payload:
    match_id: UUID
    candidate_id: UUID
    job_id: UUID
    score: float (0.0-1.0)
    breakdown: { skills: float, location: float, salary: float, preferences: float }
    calculated_at: ISO8601
transacional: "Não"
idempotente: "Sim. `match_id` é chave natural para upsert de métricas"
```

### 8. `CandidateAppliedToJob`
```yaml
emissor: CandidateProfile
consumidores:
    - JobCatalog: "Registra aplicação e atualiza contagem de applicants"
    - Analytics: "Métrica de conversion-rate e funil de contratação"
    - Notification: "Envia confirmação por email e push"
exchange: jobflow.applications
routing_key: application.created
payload:
    application_id: UUID
    candidate_id: UUID
    job_id: UUID
    applied_at: ISO8601
    resume_version: int
    source: enum("PLATFORM", "EXTERNAL")
transacional: "Sim (Outbox)"
idempotente: "Sim. Proteção contra double-click via `application_id` único e idempotência no consumidor"
```