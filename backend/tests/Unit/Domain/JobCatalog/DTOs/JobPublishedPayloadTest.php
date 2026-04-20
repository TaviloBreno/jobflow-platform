<?php

use App\Domain\JobCatalog\DTOs\JobPublishedPayload;

// ============================================================================
// FASE VERMELHA: JobPublishedPayload NÃO existe ainda
// ============================================================================

test('JobPublishedPayload extends EventPayload base contract', function () {
    $payload = new JobPublishedPayload(
        eventId: 'evt_123',
        occurredAt: new \DateTimeImmutable(),
        aggregateId: 'job_abc',
        title: 'Engenheiro de Software',
        location: ['city' => 'São Paulo', 'country' => 'BR', 'is_remote' => false],
        expiresAt: '2026-06-20T00:00:00Z'
    );

    expect($payload)->toBeInstanceOf(\App\Domain\Shared\DTOs\EventPayload::class);
})->group('unit');

test('JobPublishedPayload validates title length constraints', function () {
    new JobPublishedPayload(
        eventId: 'evt_124',
        occurredAt: new \DateTimeImmutable(),
        aggregateId: 'job_def',
        title: 'Dev', // < 5 caracteres
        location: ['city' => 'Remote', 'country' => 'BR', 'is_remote' => true],
        expiresAt: null
    );
})->throws(\InvalidArgumentException::class, 'Job title must be between 5 and 100 characters')->group('unit');

test('JobPublishedPayload serializes to JSON with expected contract', function () {
    $now = new \DateTimeImmutable('2026-04-21T10:00:00Z');

    $payload = new JobPublishedPayload(
        eventId: 'evt_125',
        occurredAt: $now,
        aggregateId: 'job_ghi',
        title: 'Arquiteto de Software',
        location: ['city' => 'Lisboa', 'country' => 'PT', 'is_remote' => true],
        expiresAt: '2026-08-20T00:00:00Z'
    );

    $json = json_encode($payload);
    $data = json_decode($json, true);

    expect($data)->toHaveKeys([
        'event_id', 'occurred_at', 'aggregate_id', 'event_type',
        'schema_version', 'title', 'location', 'expires_at'
    ])
    ->and($data['event_type'])->toBe('job.published')
    ->and($data['title'])->toBe('Arquiteto de Software');
})->group('unit');

test('JobPublishedPayload is immutable (readonly class)', function () {
    $payload = new JobPublishedPayload(
        eventId: 'evt_126',
        occurredAt: new \DateTimeImmutable(),
        aggregateId: 'job_jkl',
        title: 'Tech Lead',
        location: ['city' => 'Remote', 'country' => 'BR', 'is_remote' => true],
        expiresAt: null
    );

    $payload->title = 'Hacked'; // CompileError em readonly class
})->throws(\Error::class)->group('unit');
