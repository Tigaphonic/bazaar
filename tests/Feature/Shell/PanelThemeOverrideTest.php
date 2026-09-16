<?php

// Story 1.2 RED-PHASE scaffold: BazaarServiceProvider does not yet register Shell's
// theme. ARCHITECTURE-SPINE.md AD-33 fixes the exact shape (updated 2026-09-14,
// after Winston's spine amendment):
//   FilamentAsset::register(
//       [Css::make('bazaar-shell', ...), Js::make('bazaar-shell', ...)],
//       package: 'bazaar',
//   );
//   $panel->theme('bazaar-shell');
// — never ->viteTheme(), which is Filament's Vite-paired API and would silently
// require a host-side Vite build, defeating AD-33's entire "zero Node for host
// apps" reason for existing. `package: 'bazaar'` scopes the asset path away from
// the host app's own 'app'-scoped assets (Filament\Support\Assets\Css::
// getRelativePublicPath() keys the URL by package).

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;

it('registers Shell\'s theme under the exact "bazaar-shell" asset id, distinct from Filament stock', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();

    expect($panel->getTheme()->getId())->toBe('bazaar-shell');
});

it('never uses ->viteTheme(), which would silently require a host-side Vite build', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();

    expect($panel->getViteTheme())->toBeNull();
});

it('registers Shell\'s CSS and JS under the "bazaar" package, not the default "app" scope', function () {
    $this->artisan('bazaar:install');

    $styleIds = collect(FilamentAsset::getStyles(['bazaar']))->map(fn ($asset) => $asset->getId());
    $scriptIds = collect(FilamentAsset::getScripts(['bazaar']))->map(fn ($asset) => $asset->getId());

    expect($styleIds)->toContain('bazaar-shell')
        ->and($scriptIds)->toContain('bazaar-shell');
});

it('overrides the panel primary color to DESIGN.md primary #00609e', function () {
    $this->artisan('bazaar:install');

    $panel = Filament::getDefaultPanel();
    $colors = $panel->getColors();

    expect($colors)->toHaveKey('primary');
    // Filament stores colors as an array or a Filament\Support\Colors\Color instance;
    // the red-phase intent is simply that primary is no longer Filament's stock amber.
    expect($colors['primary'])->toBe(\Filament\Support\Colors\Color::hex(\Tigaphonic\Bazaar\Shell\Support\DesignTokens::colors()['primary']));
});
