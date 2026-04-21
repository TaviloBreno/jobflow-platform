# 🧪 Pirâmide de Testes — JobFlow Platform

> **ADR referenciado:** [ADR-004: Pirâmide de Testes Adaptada](adr/ADR-004-test-pyramid-adaptada.md)  
> **Última atualização:** 2026-04-21  
> **Responsável:** Tech Lead / QA Chapter

---

## 📊 Visão geral da pirâmide

```text
                ┌─────────────────┐
                │     E2E (5%)    │
                │  Dusk + Chrome  │
                └────────┬────────┘
       ┌─────────────────┴─────────────────┐
       │     Functional (20%)              │
       │  Pest + Use Cases + HTTP Mocks    │
       └────────────────┬──────────────────┘
      ┌─────────────────┴──────────────────┐
      │    Integration (25%)               │
      │ Pest + Testcontainers + DB real    │
      └────────────────┬───────────────────┘
      ┌────────────────┴──────────────────────────────────────┐
      │ Unit (50%)                                            │
      │ PHPUnit + Domain + Zero Dependencies + Fast           │
      └────────────────────────────────────────────────────────┘
```

| Nível           | % ideal | Velocidade      | Confiabilidade      | Custo de manutenção |
|-----------------|---------|-----------------|---------------------|---------------------|
| **Unit**        | 50%     | ⚡ \<100ms/test | 🔴 Baixa (mocks)    | 🟢 Baixo            |
| **Integration** | 25%     | 🐢 1–5s/test    | 🟡 Média (DB real)  | 🟡 Médio            |
| **Functional**  | 20%     | 🐇 200–800ms/test | 🟡 Média (HTTP mocks) | 🟡 Médio         |
| **E2E**         | 5%      | 🐌 10–30s/test  | 🟢 Alta (navegador real) | 🔴 Alto       |

> 💡 **Regra de ouro:** teste o máximo possível no nível mais baixo que faça sentido. Suba na pirâmide apenas quando necessário para validar integrações ou fluxos de usuário.

---

## 🔹 Nível 1: Testes unitários (Unit Tests)

### 🎯 Objetivo

Validar **lógica de domínio pura**, sem dependências externas. São os testes mais rápidos e isolados.

### 📦 O que testar

| Tipo                | Exemplos no JobFlow                                   | Por quê |
|---------------------|--------------------------------------------------------|--------|
| **Value Objects**   | `SalaryRange`, `JobLocation`, `PersonalInfo`          | Validar invariantes, imutabilidade e métodos de transformação |
| **Entities**        | `Job`, `Candidate` (métodos de domínio)               | Validar transições de estado e regras de negócio encapsuladas |
| **Domain Services** | `MatchingScoreCalculator`, `ResumeParser`             | Validar algoritmos puros, sem I/O |
| **DTOs imutáveis**  | `JobPublishedPayload`, `CandidateProfileCompletedDTO` | Validar construção, serialização JSON e contratos de evento |

### 🚫 O que mockar (tudo)

**✅ Mockar:**
- Repositórios (Eloquent, Redis, RabbitMQ)
- HTTP clients (Guzzle, APIs externas)
- Filesystem (Storage, uploads)
- Cache (Redis, Memcached)
- Tempo (`now()`, Carbon) para testes determinísticos
- Aleatoriedade (`uuid`, `rand`) para testes reprodutíveis

**❌ Não mockar:**
- Lógica de domínio pura (é isso que está sendo testado)
- Value Objects e Entities (são o objeto de teste)
- DTOs e contratos de evento