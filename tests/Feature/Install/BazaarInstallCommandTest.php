<?php

use Filament\Facades\Filament;

it('runs bazaar:install without error', function () {
    $this->artisan('bazaar:install')->assertSuccessful();
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
