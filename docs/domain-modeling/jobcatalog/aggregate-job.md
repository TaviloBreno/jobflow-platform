# Contexto: JobCatalog

> **Responsabilidade:** Gestão do ciclo de vida de vagas de emprego, da criação ao fechamento.  
> **Linguagem Ubíqua:** `Job`, `JobPosting`, `SalaryRange`, `JobStatus`, `Published`, `Expired`  
> **ADRs Referenciados:** ADR-001 (Polyglot Persistence), ADR-002 (RabbitMQ), ADR-003 (Clean Architecture)

---

## 🏗️ Aggregate Root: `Job`

### Identidade e atributos

```yaml
id: UUID
    description: "Identificador imutável gerado pelo sistema"
    generation: "UUID v7 (time-sortable para performance em índices)"
    visibility: "Público apenas para leitura externa"

title: string
    constraints:
        minLength: 5
        maxLength: 100
    invariant: "Obrigatório e suficientemente descritivo para busca"

description: string
    constraints:
        minLength: 50
        maxLength: 5000
    invariant: "Deve conter contexto mínimo para decisão do candidato"

salaryRange: SalaryRange
    invariant: "Mínimo <= máximo e moeda consistente"

location: JobLocation
    constraints:
        isRemote: boolean
        city: string?
        country: string  # ISO 3166-1 alpha-2
    invariant: "Se isRemote=false, city e country são obrigatórios"

employmentType: EmploymentType
    values: [FULL_TIME, PART_TIME, CONTRACT, INTERNSHIP, TEMPORARY]
    invariant: "Define regras de elegibilidade para matching"

status: JobStatus
    values: [DRAFT, PUBLISHED, CLOSED, EXPIRED]
    initialState: DRAFT
    transitions:
        DRAFT -> PUBLISHED: "publish()"
        PUBLISHED -> CLOSED: "close()"
        PUBLISHED -> EXPIRED: "expire() (automático via scheduler)"
    invariant: "Sem retrocesso de estado"

publishedAt: DateTime?
    invariant: "Definido somente ao publicar"

expiresAt: DateTime?
    calculation: "publishedAt + 60 dias (configurável por tenant)"
    invariant: "Vaga expirada não aceita novas candidaturas"

candidateDataRetention: int  # dias
    default: 730  # 2 anos (conforme política de retenção)
    invariant: "Dados de candidaturas expiradas são anonimizados após período de retenção (LGPD Art. 16)"

createdAt: DateTimeImmutable
updatedAt: DateTime
version: int
```

---

## Value Object: `SalaryRange`

```yaml
properties:
    min: Money
    max: Money
    currency: string  # ISO 4217 (BRL, USD, EUR)

invariants:
    - "min.amount <= max.amount"
    - "min.currency == max.currency"
    - "min.amount >= 0"

methods:
    - contains(amount: Money): boolean
    - midpoint(): Money

justificativa: "Encapsula consistência salarial e evita duplicação de validação"
```

---

## Value Object: `JobLocation`

```yaml
properties:
    isRemote: boolean
    city: string?
    state: string?    # ISO 3166-2
    country: string   # ISO 3166-1 alpha-2
    coordinates: GeoPoint?

invariants:
    - "Se isRemote = false, city e country são obrigatórios"
    - "country deve ser código ISO válido"

methods:
    - isHybrid(): boolean
    - distanceTo(candidateLocation: GeoPoint): float?

justificativa: "Normaliza localização para matching geográfico e filtros"
```

---

## Value Object: `JobRequirements`

```yaml
properties:
    minExperienceYears: int
    requiredSkills: array<SkillId>
    preferredSkills: array<SkillId>
    educationLevel: EducationLevel
    languages: array<LanguageRequirement>

invariants:
    - "minExperienceYears >= 0"
    - "requiredSkills e preferredSkills não podem se sobrepor"

justificativa: "Agrega critérios de elegibilidade para pré-filtragem no MatchingEngine"
```

---

## Comportamentos do Aggregate (`Job`)

```php
class Job
{
        public static function create(
                string $title,
                string $description,
                SalaryRange $salaryRange,
                JobLocation $location,
                EmploymentType $employmentType
        ): self {
                // Valida invariantes de criação
                // Retorna nova instância em estado DRAFT
        }

        public function publish(DateTimeImmutable $publishDate): void
        {
                if ($this->status !== JobStatus::DRAFT) {
                        throw JobCannotBePublished::becauseStatusIs($this->status);
                }

                if (!$this->title || !$this->description) {
                        throw JobCannotBePublished::becauseMissingRequiredFields();
                }

                $this->status = JobStatus::PUBLISHED;
                $this->publishedAt = $publishDate;
                $this->expiresAt = $publishDate->modify('+60 days');

                $this->recordThat(new JobPublished(
                        jobId: $this->id,
                        title: $this->title,
                        publishedAt: $this->publishedAt,
                        expiresAt: $this->expiresAt,
                        location: $this->location
                ));
        }

        public function close(DateTimeImmutable $closedAt): void
        {
                if ($this->status !== JobStatus::PUBLISHED) {
                        throw JobCannotBeClosed::becauseStatusIs($this->status);
                }

                $this->status = JobStatus::CLOSED;

                $this->recordThat(new JobClosed(
                        jobId: $this->id,
                        closedAt: $closedAt,
                        reason: JobClosedReason::MANUALLY_CLOSED
                ));
        }

        public function acceptsApplications(DateTimeImmutable $now): bool
        {
                return $this->status === JobStatus::PUBLISHED
                        && $this->expiresAt?->isAfter($now);
        }

        public function updateSalaryRange(SalaryRange $newRange): void
        {
                $oldRange = $this->salaryRange;
                $this->salaryRange = $newRange;

                $this->recordThat(new JobSalaryUpdated(
                        jobId: $this->id,
                        oldRange: $oldRange,
                        newRange: $newRange,
                        updatedAt: now()
                ));
        }
}
```

---

## Eventos de Domínio

### `JobPublished`

```yaml
triggered: "Quando Job.publish() é executado com sucesso"
payload:
    jobId: UUID
    title: string
    publishedAt: DateTime
    expiresAt: DateTime
    location: JobLocation
consumers:
    - MatchingEngine: "Indexa vaga para matching em tempo real"
    - Analytics: "Registra métrica de vaga publicada"
delivery: "RabbitMQ: exchange 'jobflow.jobs', routing key 'job.published'"
```

### `JobClosed`

```yaml
triggered: "Quando Job.close() é executado"
payload:
    jobId: UUID
    closedAt: DateTime
    reason: string
consumers:
    - MatchingEngine: "Remove vaga do índice de matching ativo"
    - CandidateProfile: "Notifica candidatos com aplicações pendentes"
delivery: "RabbitMQ: exchange 'jobflow.jobs', routing key 'job.closed'"
```

### `JobSalaryUpdated`

```yaml
triggered: "Quando faixa salarial é alterada"
payload:
    jobId: UUID
    oldRange: SalaryRangeDTO
    newRange: SalaryRangeDTO
    updatedAt: DateTime
consumers:
    - MatchingEngine: "Recalcula scoring de candidatos compatíveis"
delivery: "RabbitMQ: exchange 'jobflow.jobs', routing key 'job.salary.updated'"
```
