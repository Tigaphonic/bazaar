<?php

use Filament\Facades\Filament;

it('runs bazaar:install without error', function () {
    $this->artisan('bazaar:install')->assertSuccessful();
});

it('warns that the User model must manually add the HasRoles trait', function () {
    // Regression guard: this message is the only thing telling the installer
    // that skipping this manual step 500s in production (see README's
    // "Manual install step: HasRoles trait" section) -- without an assertion
    // on it, the message could be edited or dropped entirely undetected.
    $this->artisan('bazaar:install')
        ->expectsOutputToContain("Manual step required: add `use Spatie\Permission\Traits\HasRoles;` to your app's User model so the Users screen's roles checklist works.")
        ->assertSuccessful();
});

it('registers at least one Bazaar Filament resource on the existing panel', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();

    $bazaarResources = collect($panel->getResources())
        ->filter(fn (string $class) => str_starts_with($class, 'Tigaphonic\\Bazaar\\'));

    expect($bazaarResources)->not->toBeEmpty();
});

it('registers at least one Bazaar Filament page on the existing panel', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();

    $bazaarPages = collect($panel->getPages())
        ->filter(fn (string $class) => str_starts_with($class, 'Tigaphonic\\Bazaar\\'));

    expect($bazaarPages)->not->toBeEmpty();
});

it('does not register a new Filament panel', function () {
    $panelIdsBefore = collect(Filament::getPanels())->keys()->sort()->values()->all();

    $this->artisan('bazaar:install');

    $panelIdsAfter = collect(Filament::getPanels())->keys()->sort()->values()->all();

    expect($panelIdsAfter)->toEqual($panelIdsBefore);
});

it('publishes spatie/laravel-permission\'s create_permission_tables migration to database/migrations', function () {
    // Regression guard for the wrong publish tag: laravel-package-tools
    // registers this tag as "{$package->shortName()}-migrations" ("permission-
    // migrations", not "laravel-permission-migrations"). vendor:publish
    // silently no-ops (still exits SUCCESS) on an unknown tag, so asserting
    // only the command's exit code -- as the first test above does -- would
    // still pass even if the tag regressed back to the wrong value. This test
    // asserts the actual published file exists instead.
    $this->artisan('bazaar:install')->assertSuccessful();

    $published = collect(glob(database_path('migrations/*_create_permission_tables.php')) ?: []);

    expect($published)->not->toBeEmpty();
});

it('does not fail or duplicate the published migration file when run twice in a row', function () {
    $this->artisan('bazaar:install')->assertSuccessful();

    $publishedAfterFirstRun = collect(glob(database_path('migrations/*_create_permission_tables.php')) ?: []);

    $this->artisan('bazaar:install')->assertSuccessful();

    $publishedAfterSecondRun = collect(glob(database_path('migrations/*_create_permission_tables.php')) ?: []);

    expect($publishedAfterFirstRun)->not->toBeEmpty();
    expect($publishedAfterSecondRun->all())->toEqual($publishedAfterFirstRun->all());
});
