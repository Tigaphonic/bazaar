<?php

it('runs php artisan migrate cleanly for the User & Access and Global Settings domains', function () {
    $this->artisan('migrate')->assertSuccessful();
})->skip('AC3 — no domain migrations exist yet beyond the generic skeleton placeholder (Story 1.1)');

it('uses ULID primary keys on every migration Bazaar itself authors, never auto-increment', function () {
    // Scoped to this package's own migrations dir only — per AD-18 (amended 2026-09-14),
    // ULID is mandatory only for tables Bazaar itself creates. Dependency-owned tables
    // (Laravel's core `users`, spatie/laravel-permission's tables, spatie/laravel-settings'
    // own storage) keep their native schema and are never copied in here, so this story may
    // legitimately ship zero files in this directory — the loop is then vacuously true.
    $migrationFiles = glob(__DIR__.'/../../../database/migrations/*.php');

    foreach ($migrationFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('->id()')
            ->and($contents)->toContain('->ulid(');
    }
})->skip('AC3 / AD-18 — re-check once Story 1.1 settles whether it ships any migration of its own (Story 1.1)');

it('contains no MySQL- or PostgreSQL-specific SQL in any migration Bazaar itself authors', function () {
    // Same scoping as the ULID test above — dependency-owned migrations are out of scope.
    $migrationFiles = glob(__DIR__.'/../../../database/migrations/*.php');
    $engineSpecific = ['JSON_EXTRACT(', 'JSONB', '::jsonb', 'AUTO_INCREMENT', 'SERIAL'];

    foreach ($migrationFiles as $file) {
        $contents = file_get_contents($file);

        foreach ($engineSpecific as $needle) {
            expect($contents)->not->toContain($needle);
        }
    }
})->skip('AC3 / AD-23 — re-check once Story 1.1 settles whether it ships any migration of its own (Story 1.1)');
