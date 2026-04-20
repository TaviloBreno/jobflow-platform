<?php

use App\Domain\JobCatalog\ValueObjects\SalaryRange;

// ============================================================================
// FASE VERMELHA DO TDD: Estas classes NÃO existem ainda.
// Todos os testes abaixo DEVEM falhar com "Class not found".
// ============================================================================

test('SalaryRange enforces min amount is less than or equal to max', function () {
    $range = new SalaryRange(5000, 8000, 'BRL');

    expect($range->min)->toBe(5000)
        ->and($range->max)->toBe(8000)
        ->and($range->currency)->toBe('BRL');
})->group('unit');

test('SalaryRange throws exception when min is greater than max', function () {
    new SalaryRange(10000, 5000, 'BRL');
})->throws(\InvalidArgumentException::class, 'Minimum salary cannot be greater than maximum')->group('unit');

test('SalaryRange rejects negative amounts', function () {
    new SalaryRange(-100, 5000, 'BRL');
})->throws(\InvalidArgumentException::class, 'Salary amounts must be non-negative')->group('unit');

test('SalaryRange requires valid ISO 4217 currency code', function () {
    new SalaryRange(5000, 8000, 'INVALID');
})->throws(\InvalidArgumentException::class, 'Invalid currency code')->group('unit');

test('SalaryRange calculates midpoint correctly', function () {
    $range = new SalaryRange(5000, 9000, 'USD');

    expect($range->midpoint())->toBe(7000);
})->group('unit');

test('SalaryRange contains method works for boundary values', function () {
    $range = new SalaryRange(5000, 10000, 'BRL');

    expect($range->contains(5000))->toBeTrue()
        ->and($range->contains(10000))->toBeTrue()
        ->and($range->contains(4999))->toBeFalse()
        ->and($range->contains(10001))->toBeFalse();
})->group('unit');

test('SalaryRange is immutable (readonly class)', function () {
    $range = new SalaryRange(5000, 8000, 'BRL');
    $range->min = 9999; // Isso deve gerar CompileError em PHP 8.2+
})->throws(\Error::class)->group('unit');
