<?php

it('declares filament/filament ^5.8 as a dependency', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    $declared = $composer['require']['filament/filament']
        ?? $composer['require-dev']['filament/filament']
        ?? null;

    expect($declared)->not->toBeNull()
        ->and($declared)->toContain('5.8');
})->skip('AC1 — filament/filament is not declared in composer.json yet (Story 1.1)');

it('narrows illuminate/contracts to Laravel ^12/^13 per the Architecture Spine stack', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['require']['illuminate/contracts'])->toBe('^12.0||^13.0');
})->skip('AC1 — current constraint still allows ^11.0 (Story 1.1)');
