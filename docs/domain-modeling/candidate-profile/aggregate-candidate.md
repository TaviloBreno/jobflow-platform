# Contexto: CandidateProfile

> **Responsabilidade:** Gestão do perfil profissional de candidatos, incluindo habilidades, experiências e preferências para matching.  
> **Linguagem ubíqua:** `Candidate`, `Skill`, `Experience`, `Preference`, `Resume`, `MatchingScore`
> **ADRs Referenciados:** ADR-001 (Polyglot Persistence), ADR-002 (RabbitMQ), ADR-003 (Clean Architecture)

---

## 🏗️ Aggregate Root: `Candidate`

### Identidade e estado

```yaml
id: UUID
    description: "Identificador do candidato (vinculado ao Identity Context via sub)"
    generation: "UUID v4 ou herdado do sistema de autenticação"
    visibility: "Privado; exposto apenas via APIs autorizadas"
    invariant: "Imutável após criação"

userId: UUID
    description: "FK lógico para Identity Context"
    invariant: "Vínculo 1:1 com User aggregate"

personalInfo: PersonalInfo
professionalSummary: string?
skills: array<CandidateSkill>
experiences: array<WorkExperience>
education: array<EducationEntry>
preferences: JobPreferences
resume: ResumeAttachment?
matchingOptIn: boolean
createdAt: DateTime
updatedAt: DateTime
lastActiveAt: DateTime?
```

### Regras de negócio (invariantes)

- `personalInfo.email` é obrigatório e único no sistema (validado em Application Service).
- `professionalSummary` é opcional; se presente, deve ter entre **20** e **2000** caracteres.
- Máximo de **50** skills por candidato.
- Skill deve possuir proficiência entre **1 e 5** e data/contexto de validação quando aplicável.
- Experiências não podem ter intervalo inválido (`startDate <= endDate` quando `endDate` existir).
- `resume`: apenas **1 ativo** por vez; versões anteriores devem ser arquivadas.
- Se `matchingOptIn = false`, candidato não aparece no matching (compliance LGPD).

---

## 🧩 Value Objects e Entidades internas

### `PersonalInfo` (Value Object)

**Propriedades**
- `fullName: string` (3-150 chars)
- `email: Email` (RFC 5322)
- `phone: PhoneNumber?` (E.164)
- `linkedinUrl: Url?` (domínio `linkedin.com`)

**Invariantes**
- `fullName` não pode conter caracteres maliciosos.
- Se `phone` presente, deve estar em formato E.164 válido.

**Métodos**
- `redactForAnalytics(): PersonalInfo`
- `isComplete(): boolean`

**Justificativa**  
Agrupa dados pessoais com validações consistentes e facilita anonimização (LGPD).

---

### `JobPreferences` (Value Object)

**Propriedades**
- `desiredSalaryRange: SalaryRange?`
- `preferredLocations: array<JobLocation>`
- `remoteOnly: boolean`
- `employmentTypes: array<EmploymentType>`
- `industries: array<IndustryCode>` (CNAE/NAICS)

**Invariantes**
- Se `remoteOnly = true`, `preferredLocations` pode ser vazio.
- Se candidato ativo no matching, `employmentTypes` não pode ser vazio.
- `desiredSalaryRange.min >= 0`, quando definido.

**Métodos**
- `matchesJob(job: Job): boolean`
- `isFlexible(): boolean`

**Justificativa**  
Encapsula pré-filtragem local e reduz carga no MatchingEngine.

---

### `CandidateSkill` (Entity interna)

**Propriedades**
- `skillId: UUID` (catálogo global)
- `proficiency: ProficiencyLevel` (`1..5`)
- `yearsOfExperience: float`
- `lastUsedAt: DateTime?`
- `verified: boolean`

**Invariantes**
- `proficiency ∈ [1,5]`
- `yearsOfExperience >= 0`
- Se `verified = true`, deve haver `certificationId` ou `assessmentId`.

**Métodos**
- `matchesJobRequirement(requirement: SkillRequirement): boolean`
- `calculateWeight(): float`

**Justificativa**  
Modela habilidade com contexto (proficiência, recência, validação), não apenas presença.

---

## ⚙️ Comportamentos do Aggregate (`Candidate`)

```php
class Candidate
{
        public static function create(
                UUID $userId,
                PersonalInfo $personalInfo,
                JobPreferences $preferences
        ): self {
                // Valida invariantes de criação
                // matchingOptIn = true por padrão (modelo opt-out)
        }

        public function addSkill(
                UUID $skillId,
                ProficiencyLevel $proficiency,
                float $yearsOfExperience
        ): void {
                $existing = $this->skills->first(fn ($s) => $s->skillId === $skillId);

                if ($existing) {
                        $existing->update($proficiency, $yearsOfExperience);
                } else {
                        if ($this->skills->count() >= 50) {
                                throw CandidateSkillLimitExceeded::forCandidate($this->id);
                        }
                        $this->skills->add(new CandidateSkill($skillId, $proficiency, $yearsOfExperience));
                }

                $this->recordThat(new CandidateSkillsUpdated(
                        candidateId: $this->id,
                        skillId: $skillId,
                        action: $existing ? 'updated' : 'added'
                ));
        }

        public function updatePreferences(JobPreferences $newPreferences): void
        {
                $oldPreferences = $this->preferences;
                $this->preferences = $newPreferences;

                $this->recordThat(new CandidatePreferencesUpdated(
                        candidateId: $this->id,
                        oldPreferences: $oldPreferences->toDTO(),
                        newPreferences: $newPreferences->toDTO()
                ));
        }

        public function optOutMatching(string $reason): void
        {
                if (!$this->matchingOptIn) {
                        return; // idempotente
                }

                $this->matchingOptIn = false;
                $this->recordThat(new CandidateMatchingOptedOut(
                        candidateId: $this->id,
                        reason: $reason,
                        optedOutAt: now()
                ));
        }

        public function isEligibleForJob(Job $job): bool
        {
                return $this->matchingOptIn
                        && $this->preferences->matchesJob($job)
                        && $this->hasMinimumRequirements($job);
        }

        private function hasMinimumRequirements(Job $job): bool
        {
                // Ex.: >=70% das skills requeridas (Specification Pattern)
        }
}
```

---

## 📣 Domain Events

```yaml
CandidateProfileCompleted:
    triggered: "Quando candidato preenche campos mínimos para matching"
    payload:
        candidateId: UUID
        completedAt: DateTime
        completenessScore: float  # 0.0-1.0
    consumers:
        - MatchingEngine: "Inclui candidato no índice de matching ativo"
        - Analytics: "Registra conversão de onboarding"
    delivery: "RabbitMQ: exchange 'jobflow.candidates', routing key 'candidate.profile.completed'"

CandidateSkillsUpdated:
    triggered: "Quando skills são adicionadas/atualizadas/removidas"
    payload:
        candidateId: UUID
        skillId: UUID
        action: "added|updated|removed"
        proficiency: int
    consumers:
        - MatchingEngine: "Recalcula scores de compatibilidade com vagas abertas"
    delivery: "RabbitMQ: exchange 'jobflow.candidates', routing key 'candidate.skills.updated'"

CandidateMatchingOptedOut:
    triggered: "Quando candidato revoga consentimento para matching"
    payload:
        candidateId: UUID
        reason: string
        optedOutAt: DateTime
    consumers:
        - MatchingEngine: "Remove candidato do índice de matching (LGPD)"
        - Analytics: "Registra opt-out para métricas de privacidade"
    delivery: "RabbitMQ: exchange 'jobflow.candidates', routing key 'candidate.matching.opted-out'"
```