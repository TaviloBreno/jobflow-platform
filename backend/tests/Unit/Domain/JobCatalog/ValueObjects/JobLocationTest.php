<?php

use App\Domain\JobCatalog\ValueObjects\JobLocation;

// ============================================================================
// FASE VERMELHA: JobLocation NÃO existe ainda
// ============================================================================

test('JobLocation accepts remote-only configuration', function () {
    $location = new JobLocation(isRemote: true);

    expect($location->isRemote)->toBeTrue()
        ->and($location->city)->toBeNull()
        ->and($location->country)->toBeNull();
})->group('unit');

test('JobLocation requires city and country when not remote', function () {
    new JobLocation(isRemote: false, city: null, country: null);
})->throws(\InvalidArgumentException::class, 'City and country are required for non-remote positions')->group('unit');

test('JobLocation validates country as ISO 3166-1 alpha-2 code', function () {
    new JobLocation(isRemote: false, city: 'São Paulo', country: 'BRAZIL');
})->throws(\InvalidArgumentException::class, 'Country must be a valid ISO 3166-1 alpha-2 code')->group('unit');

test('JobLocation accepts valid coordinates when provided', function () {
    $location = new JobLocation(
        isRemote: false,
        city: 'São Paulo',
        country: 'BR',
        latitude: -23.5505,
        longitude: -46.6333
    );

    expect($location->latitude)->toBe(-23.5505)
        ->and($location->longitude)->toBe(-46.6333);
})->group('unit');

test('JobLocation rejects invalid coordinate ranges', function () {
    new JobLocation(
        isRemote: false,
        city: 'Test',
        country: 'BR',
        latitude: 100, // Inválido: latitude deve estar entre -90 e 90
        longitude: -46.6333
    );
})->throws(\InvalidArgumentException::class, 'Latitude must be between -90 and 90')->group('unit');
