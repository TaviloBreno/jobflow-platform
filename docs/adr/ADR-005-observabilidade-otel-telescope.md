# ADR-005: Observabilidade com OpenTelemetry + Laravel Telescope

## Status
Aceito

## Contexto
Sistemas distribuídos (filas, múltiplos DBs, workers) geram ruído operacional. Logs isolados não mostram causalidade. Métricas sem traces não apontam gargalos. Precisamos de correlação ponta-a-ponta em dev e prod.

## Decisão
- **OpenTelemetry (OTel)**: Instrumentação automática para traces, métricas e logs. Export para Jaeger/Tempo + Prometheus/Grafana.
- **Laravel Telescope**: Debugging local/dev com timeline de queries, jobs, exceptions e requests. Desativado em produção.

## Consequências
### Positivas
- Visibilidade unificada: trace ID conecta request → queue → DB → external API
- Telescope acelera desenvolvimento e debugging local sem overhead de prod
- Padrão CNCF: vendor-neutral, fácil migração entre backends (Datadog, New Relic, OSS)

### Negativas
- Overhead de performance (~2-5% CPU/mem) com sampling ativo
- Custo de armazenamento de traces/métricas em escala
- Configuração inicial complexa (auto-instrumentation, context propagation, baggagem)

## Alternativas Consideradas
1. Apenas logs + Sentry (rejeitado: falta correlação temporal e métricas de infra)
2. APM proprietário (Datadog/New Relic) desde o dia 1 (rejeitado: custo e lock-in prematuro)

## Referências
- OpenTelemetry Specification
- Laravel Telescope Documentation
- CNCF Observability Whitepaper