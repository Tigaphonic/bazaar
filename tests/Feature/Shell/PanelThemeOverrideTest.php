<?php

// Story 1.2 RED-PHASE scaffold: BazaarServiceProvider does not yet register a custom
// panel theme/colors. ARCHITECTURE-SPINE.md line 42: "Per-client visual differences go
// through Filament's own theme-override mechanism ... never a second panel
// implementation." AC1: shell must render as a Filament panel theme override, not the
// stock Filament theme.

use Filament\Facades\Filament;

it('registers a Bazaar theme asset distinct from Filament stock default', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();

    expect($panel->getTheme()->getId())->not->toBe('app');
})->skip('Story 1.2 not implemented — panel does not yet call ->theme()/->viteTheme()');

it('overrides the panel primary color to DESIGN.md primary #00609e', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();
    $colors = $panel->getColors();

    expect($colors)->toHaveKey('primary');
    // Filament stores colors as an array or a Filament\Support\Colors\Color instance;
    // the red-phase intent is simply that primary is no longer Filament's stock amber.
    expect($colors['primary'])->not->toBeNull();
})->skip('Story 1.2 not implemented — panel does not yet call ->colors()');
