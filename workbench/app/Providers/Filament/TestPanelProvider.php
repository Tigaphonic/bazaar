<?php

namespace Workbench\App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

/**
 * Simulates the client project's own, already-installed, otherwise-empty
 * Filament panel that Bazaar connects to (Story 1.1 AC2's Given clause).
 */
class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->default()
            ->path('admin');
    }
}
