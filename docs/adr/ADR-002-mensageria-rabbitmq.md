# ADR-002: Mensageria com RabbitMQ (não Laravel Broadcast)

## Status
Aceito

## Contexto
O JobFlow exige processamento assíncrono confiável, retentativas controladas, dead-letter queues e roteamento complexo entre workers distribuídos. O Laravel Broadcast/Redis pub-sub é adequado para eventos em tempo real, mas não oferece garantias de entrega, persistência ou DLQ nativas.

## Decisão
Adotar **RabbitMQ** como broker de mensageria principal, utilizando o driver `rabbitmq` do Laravel Queue. Configurar exchanges topic, filas duráveis, ack manual e políticas de expiração/DLQ.

## Consequências
### Positivas
- Garantia de entrega (persistent messages) e exatamente-uma-processamento (via ack)
- Roteamento flexível (topic, direct, fanout) para microserviços futuros
- Dead Letter Queue nativa para análise de falhas e retry manual

### Negativas
- Infraestrutura adicional a gerenciar (cluster, monitoramento, upgrades)
- Latência ligeiramente maior vs Redis pub-sub
- Necessidade de padronizar serialização (JSON/Avro) e versionamento de payloads

## Alternativas Consideradas
1. Redis Streams / Laravel Broadcast (rejeitado: sem persistência robusta, sem DLQ, risco de perda de dados)
2. AWS SQS (rejeitado: vendor lock-in e custo variável em estágio inicial)

## Referências
- RabbitMQ Documentation: Reliability Guide
- Laravel Queues: Drivers Comparison
- Cloud Native Messaging Patterns (CNCF)