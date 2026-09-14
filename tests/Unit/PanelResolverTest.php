<?php

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\PanelRegistry;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;

it('throws RuntimeException when config panel id is not found', function () {
    config()->set('bazaar.panel', 'nonexistent');

    $registry = app(PanelRegistry::class);

    expect(fn () => PanelResolver::resolve($registry))
        ->toThrow(RuntimeException::class, "Bazaar could not find a Filament panel with id [nonexistent].");
});

it('throws RuntimeException when no default panel is set and config is missing', function () {
    config()->set('bazaar.panel', null);

    $registry = Mockery::mock(PanelRegistry::class);
    $registry->shouldReceive('getDefault')->andThrow(new NoDefaultPanelSetException());

    expect(fn () => PanelResolver::resolve($registry))
        ->toThrow(RuntimeException::class, "Bazaar could not find a default Filament panel to attach to.");
});
