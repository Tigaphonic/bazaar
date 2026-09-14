<?php

it('declares filament/filament ^5.8 as a dependency', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    $declared = $composer['require']['filament/filament']
        ?? $composer['require-dev']['filament/filament']
        ?? null;

    expect($declared)->not->toBeNull()
        ->and($declared)->toContain('5.8');
});

it('narrows illuminate/contracts to Laravel ^12/^13 per the Architecture Spine stack', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['require']['illuminate/contracts'])->toBe('^12.0||^13.0');
});
