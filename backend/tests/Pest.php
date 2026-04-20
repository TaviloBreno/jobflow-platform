<?php

/*
|--------------------------------------------------------------------------
| Test Suite Bootstrap (ADR-004: Pirâmide Adaptada)
|--------------------------------------------------------------------------
|
| Estrutura:
| - Unit/Domain/           → Lógica pura (Value Objects, Invariantes, DTOs)
| - Feature/Application/   → Casos de uso, Controllers, Contratos de API
| - Integration/Infra/     → Repositórios, Filas, Cache, RabbitMQ, DB
| - Browser/               → E2E com Laravel Dusk
|
*/

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature e Integration precisam do container Laravel + DB
uses(TestCase::class)->in('Feature', 'Integration');
uses(RefreshDatabase::class)->in('Feature', 'Integration');

// Helper: UUIDs determinísticos para testes reprodutíveis
if (!function_exists('fake_uuid')) {
    function fake_uuid(string $seed = 'test'): string {
        return \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_DNS, $seed)->toString();
    }
}

// Helper: Serialização segura de DTOs para validação de contrato
if (!function_exists('serialize_payload')) {
    function serialize_payload(object $dto): string {
        return json_encode($dto, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
