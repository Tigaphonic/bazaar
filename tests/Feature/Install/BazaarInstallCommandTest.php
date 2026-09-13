<?php

it('runs bazaar:install without error', function () {
    $this->artisan('bazaar:install')->assertSuccessful();
})->skip('AC2 — bazaar:install command does not exist yet (Story 1.1)');

it('registers at least one Bazaar Filament resource on the existing panel', function () {
    $this->artisan('bazaar:install');

    $panel = \Filament\Facades\Filament::getDefaultPanel();

    $bazaarResources = collect($panel->getResources())
        ->filter(fn (string $class) => str_starts_with($class, 'Tigaphonic\\Bazaar\\'));

    expect($bazaarResources)->not->toBeEmpty();
})->skip('AC2 — bazaar:install and the Filament resources it registers do not exist yet (Story 1.1)');

it('does not register a new Filament panel', function () {
    $panelIdsBefore = collect(\Filament\Facades\Filament::getPanels())->keys()->sort()->values()->all();

    $this->artisan('bazaar:install');

    $panelIdsAfter = collect(\Filament\Facades\Filament::getPanels())->keys()->sort()->values()->all();

    expect($panelIdsAfter)->toEqual($panelIdsBefore);
})->skip('AC2 — bazaar:install does not exist yet, and no fixture panel is registered in TestCase yet (Story 1.1)');
