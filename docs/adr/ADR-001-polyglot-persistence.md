# ADR-001: Polyglot Persistence (MySQL, PostgreSQL, MongoDB, Elasticsearch)

## Status
Aceito

## Contexto
A plataforma JobFlow precisará armazenar dados relacionais (usuários, transações), documentos flexíveis (logs, metadados de jobs), buscas full-text rápidas e consultas geoespaciais. Um único SGBD não entrega otimização para todos os casos de uso, gerando gargalos de performance e modelagem artificial.

## Decisão
Adotar abordagem *Polyglot Persistence*, delegando cada domínio ao SGBD mais adequado:
- **MySQL**: Dados transacionais e relacionais core (ACID, FK, ORM Laravel)
- **PostgreSQL**: Consultas complexas, JSONB e geolocalização (PostGIS)
- **MongoDB**: Logs de execução, históricos imutáveis e schemas dinâmicos
- **Elasticsearch**: Busca textual, agregações em tempo real e dashboards

## Consequências
### Positivas
- Performance otimizada por workload específico
- Escalabilidade independente por domínio de dados
- Flexibilidade para evoluir schemas sem impactar o core

### Negativas
- Aumento de complexidade operacional (backup, monitoramento, versionamento)
- Necessidade de padrões claros de sincronização/consistência eventual
- Curva de aprendizado para equipe multidisciplinar

## Alternativas Consideradas
1. MySQL como único SGBD (rejeitado: performance degradada em buscas e documentos)
2. PostgreSQL + JSONB para tudo (rejeitado: Elasticsearch é superior em relevância textual e agregações)

## Referências
- Martin Fowler: Polyglot Persistence
- Laravel Multi-Database Documentation
- Elasticsearch vs PostgreSQL Full-Text Search Benchmarks