# 🧪 Pirâmide de Testes - JobFlow Platform

> **ADR Referenciado:** [ADR-004: Test Pyramid Adaptada](adr/ADR-004-test-pyramid-adaptada.md)  
> **Última Atualização:** 2026-04-21  
> **Responsável:** Tech Lead / QA Chapter

---

## 📊 Visão Geral da Pirâmide
                ┌─────────────────┐
                │     E2E (5%)    │
                │  Dusk + Chrome  │
                └────────┬────────┘
       ┌─────────────────┴─────────────────┐
       │     Functional (20%)              │
       │  Pest + Use Cases + HTTP Mocks    │
       └────────────────┬──────────────────┘
      ┌─────────────────┴─────────────────┐
      │    Integration (25%)              │
      │ Pest + Testcontainers + DB real   │
      └────────────────┬──────────────────┘
      ┌─────────────────────────┴─────────────────────────┐
      │ Unit (50%) │
      │ PHPUnit + Domain + Zero Dependencies + Fast │
      └───────────────────────────────────────────────────┘

| Nível | % Ideal | Velocidade | Confiabilidade | Custo de Manutenção |
|-------|---------|------------|----------------|-------------------|
| **Unit** | 50% | ⚡ <100ms/test | 🔴 Baixa (mocks) | 🟢 Baixo |
| **Integration** | 25% | 🐢 1-5s/test | 🟡 Média (DB real) | 🟡 Médio |
| **Functional** | 20% | 🐇 200-800ms/test | 🟡 Média (HTTP mocks) | 🟡 Médio |
| **E2E** | 5% | 🐌 10-30s/test | 🟢 Alta (navegador real) | 🔴 Alto |

> 💡 **Regra de Ouro:** Teste o máximo possível no nível mais baixo que faça sentido. Suba na pirâmide apenas quando necessário para validar integrações ou fluxos de usuário.

---

## 🔹 Nível 1: Testes Unitários (Unit Tests)

### 🎯 Objetivo
Validar **lógica de domínio pura**, sem dependências externas. São os testes mais rápidos e isolados.

### 📦 O Que Testar
| Tipo | Exemplos no JobFlow | Por Que |
|------|---------------------|---------|
| **Value Objects** | `SalaryRange`, `JobLocation`, `PersonalInfo` | Validar invariantes, imutabilidade, métodos de transformação |
| **Entities** | `Job`, `Candidate` (métodos de domínio) | Validar transições de estado, regras de negócio encapsuladas |
| **Domain Services** | `MatchingScoreCalculator`, `ResumeParser` | Validar algoritmos puros, sem I/O |
| **DTOs Imutáveis** | `JobPublishedPayload`, `CandidateProfileCompletedDTO` | Validar construção, serialização JSON, contratos de evento |

### 🚫 O Que Mockar (TUDO)
```php
// ✅ Mockar:
- Repositórios (Eloquent, Redis, RabbitMQ)
- HTTP Clients (Guzzle, APIs externas)
- Filesystem (Storage, uploads)
- Cache (Redis, Memcached)
- Time (now(), Carbon) para testes determinísticos
- Random (uuid, rand) para testes reprodutíveis

// ❌ NÃO mockar:
- Lógica de domínio pura (é isso que estamos testando!)
- Value Objects e Entities (são o objeto de teste)
- DTOs e contratos de evento
```