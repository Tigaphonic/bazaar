<?php

it('runs php artisan migrate cleanly for the User & Access and Global Settings domains', function () {
    $this->artisan('migrate')->assertSuccessful();
});

it('uses ULID primary keys on every migration Bazaar itself authors, never auto-increment', function () {
    // Scoped to this package's own migrations dir only — per AD-18 (amended 2026-09-14),
    // ULID is mandatory only for tables Bazaar itself creates. Dependency-owned tables
    // (Laravel's core `users`, spatie/laravel-permission's tables, spatie/laravel-settings'
    // own storage) keep their native schema and are never copied in here, so this story
    // legitimately ships zero files in this directory today — the assertion still holds.
    $migrationFiles = collect(glob(__DIR__.'/../../../database/migrations/*.php'));

    $offenders = $migrationFiles->filter(function (string $file) {
        $contents = file_get_contents($file);

        return str_contains($contents, '->id()') || ! str_contains($contents, '->ulid(');
    });

    expect($offenders)->toBeEmpty();
});

it('contains no MySQL- or PostgreSQL-specific SQL in any migration Bazaar itself authors', function () {
    // Same scoping as the ULID test above — dependency-owned migrations are out of scope.
    $migrationFiles = collect(glob(__DIR__.'/../../../database/migrations/*.php'));
    $engineSpecific = ['JSON_EXTRACT(', 'JSONB', '::jsonb', 'AUTO_INCREMENT', 'SERIAL'];

    $offenders = $migrationFiles->filter(function (string $file) use ($engineSpecific) {
        $contents = file_get_contents($file);

        return collect($engineSpecific)->contains(fn (string $needle) => str_contains($contents, $needle));
    });

    expect($offenders)->toBeEmpty();
});

it('seeds the manage-settings permission and the Global Settings default rows when migrate runs', function () {
    $this->artisan('migrate')->assertSuccessful();

    $settingsCount = count(get_class_vars(\Tigaphonic\Bazaar\Settings\Support\BazaarSettings::class));

    expect(\Spatie\Permission\Models\Permission::query()->where('name', 'manage-settings')->exists())->toBeTrue()
        ->and(\Illuminate\Support\Facades\DB::table('settings')->where('group', 'bazaar')->count())->toBe($settingsCount);
});
