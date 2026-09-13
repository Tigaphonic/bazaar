<?php

it('runs php artisan migrate cleanly for the User & Access and Global Settings domains', function () {
    $this->artisan('migrate')->assertSuccessful();
})->skip('AC3 — no domain migrations exist yet beyond the generic skeleton placeholder (Story 1.1)');

it('uses ULID primary keys on every Bazaar migration, never auto-increment', function () {
    $migrationFiles = glob(__DIR__.'/../../../database/migrations/*.php');

    expect($migrationFiles)->not->toBeEmpty();

    foreach ($migrationFiles as $file) {
        $contents = file_get_contents($file);

        expect($contents)->not->toContain('->id()')
            ->and($contents)->toContain('->ulid(');
    }
})->skip('AC3 / AD-18 — migrations directory still only has the skeleton .stub placeholder (Story 1.1)');

it('contains no MySQL- or PostgreSQL-specific SQL in migration files', function () {
    $migrationFiles = glob(__DIR__.'/../../../database/migrations/*.php');
    $engineSpecific = ['JSON_EXTRACT(', 'JSONB', '::jsonb', 'AUTO_INCREMENT', 'SERIAL'];

    expect($migrationFiles)->not->toBeEmpty();

    foreach ($migrationFiles as $file) {
        $contents = file_get_contents($file);

        foreach ($engineSpecific as $needle) {
            expect($contents)->not->toContain($needle);
        }
    }
})->skip('AC3 / AD-23 — verified once real domain migrations exist (Story 1.1)');
