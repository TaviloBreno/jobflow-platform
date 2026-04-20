<?php

use App\Domain\CandidateProfile\ValueObjects\PersonalInfo;

// ============================================================================
// FASE VERMELHA: PersonalInfo NÃO existe ainda
// ============================================================================

test('PersonalInfo validates full name length constraints', function () {
    new PersonalInfo(fullName: 'Jo', email: 'jo@test.com');
})->throws(\InvalidArgumentException::class, 'Full name must be between 3 and 150 characters')->group('unit');

test('PersonalInfo validates email format', function () {
    new PersonalInfo(fullName: 'João Silva', email: 'invalid-email');
})->throws(\InvalidArgumentException::class, 'Invalid email format')->group('unit');

test('PersonalInfo accepts valid E.164 phone number when provided', function () {
    $info = new PersonalInfo(
        fullName: 'João Silva',
        email: 'joao@test.com',
        phone: '+5511999999999'
    );

    expect($info->phone)->toBe('+5511999999999');
})->group('unit');

test('PersonalInfo rejects invalid E.164 phone format', function () {
    new PersonalInfo(
        fullName: 'João Silva',
        email: 'joao@test.com',
        phone: '11999999999' // Falta o + e código do país
    );
})->throws(\InvalidArgumentException::class, 'Phone must be in E.164 format')->group('unit');

test('PersonalInfo validates LinkedIn URL domain when provided', function () {
    new PersonalInfo(
        fullName: 'João Silva',
        email: 'joao@test.com',
        linkedinUrl: 'https://facebook.com/joaosilva'
    );
})->throws(\InvalidArgumentException::class, 'LinkedIn URL must be from linkedin.com domain')->group('unit');

test('PersonalInfo isComplete returns false when only required fields are present', function () {
    $info = new PersonalInfo(fullName: 'João Silva', email: 'joao@test.com');

    expect($info->isComplete())->toBeFalse();
})->group('unit');

test('PersonalInfo isComplete returns true when optional fields are also provided', function () {
    $info = new PersonalInfo(
        fullName: 'João Silva',
        email: 'joao@test.com',
        phone: '+5511999999999',
        linkedinUrl: 'https://linkedin.com/in/joaosilva'
    );

    expect($info->isComplete())->toBeTrue();
})->group('unit');
