# ADR-004: Test Pyramid Adaptada (50% Unit, 20% Functional, 25% Integration, 5% E2E)

## Status
Aceito

## Contexto
A pirâmide clássica (70/20/10) não se aplica bem a sistemas backend com integrações externas (filas, DB, cache). Testes E2E são lentos e frágeis, enquanto testes unitários puros não validam contratos de API e fluxos reais.

## Decisão
Adotar distribuição adaptada:
- **50% Unitários**: Lógica de domínio, value objects, regras de negócio, pure functions
- **25% Integração**: Repositórios, filas, cache, HTTP clients com containers efêmeros (Testcontainers)
- **20% Funcionais**: Contratos de API, serialização, validação, middlewares
- **5% E2E**: Fluxos críticos ponta-a-ponta (login → job → resultado)

## Consequências
### Positivas
- Feedback rápido no CI (< 3 min) com alta cobertura de lógica crítica
- Testes de integração realistas sem mocks excessivos
- Manutenção previsível: E2E só para happy paths e regressões graves

### Negativas
- Complexidade para configurar Testcontainers e isolar estado entre testes
- Risco de "testing anti-patterns" se unitários mockarem demais o framework
- Cobertura de UI/UX limitada (foco backend justifica)

## Alternativas Consideradas
1. Pirâmide Clássica 70/20/10 (rejeitada: E2E excessivo gera flakiness e lentidão)
2. Testing Trophy (Kent C. Dodds) (rejeitado: mais adequado para frontend/React)

## Referências
- Martin Fowler: Test Pyramid
- Laravel Testing Documentation
- Testcontainers for PHP/Node