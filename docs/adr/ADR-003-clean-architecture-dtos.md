# ADR-003: Clean Architecture com DTOs Imutáveis

## Status
Aceito

## Contexto
Aplicações Laravel tradicionais frequentemente acoplam lógica de domínio a controllers, models e requests. Isso dificulta testes unitários, gera efeitos colaterais por mutação de estado e torna a migração de framework custosa.

## Decisão
Adotar **Clean Architecture** (Camadas: Domain → Application → Infrastructure → Presentation) com **DTOs imutáveis** (PHP 8.1 readonly classes ou records) para trafegar dados entre camadas. Models do Eloquent permanecem apenas na camada de infraestrutura.

## Consequências
### Positivas
- Separação clara de responsabilidades e testes unitários isolados do framework
- Imutabilidade previne bugs por alteração acidental de estado
- Framework independence: troca de Laravel/Symfony/RoadRunner sem tocar no domínio

### Negativas
- Boilerplate inicial maior (mapeamento Entity ↔ DTO, factories, transformers)
- Curva de aprendizado para devs acostumados com Active Record puro
- Overhead mínimo de serialização/desserialização em requests intensos

## Alternativas Consideradas
1. MVC tradicional com Eloquent direto nos controllers (rejeitado: acoplamento forte, difícil testar)
2. Hexagonal Architecture sem DTOs (rejeitado: arrays/mixed types reduzem type safety e IDE support)

## Referências
- Uncle Bob: Clean Architecture
- PHP 8.2 Readonly Classes RFC
- Domain-Driven Design: DTOs vs Entities (Martin Fowler)