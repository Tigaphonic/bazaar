<?php

namespace Tigaphonic\Bazaar\Install\Support;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Panel;
use Filament\PanelRegistry;
use RuntimeException;

/**
 * Resolves the Filament panel Bazaar attaches to: `config('bazaar.panel')`
 * when set, otherwise whichever panel the client marked `->default()`.
 * Shared by BazaarServiceProvider and BazaarInstallCommand so both fail
 * the same clear way instead of a bare TypeError on a misconfigured id.
 */
final class PanelResolver
{
    public static function resolve(PanelRegistry $registry): Panel
    {
        $panelId = config('bazaar.panel');

        if ($panelId) {
            return $registry->get($panelId)
                ?? throw new RuntimeException("Bazaar could not find a Filament panel with id [{$panelId}]. Check config('bazaar.panel').");
        }

        try {
            return $registry->getDefault();
        } catch (NoDefaultPanelSetException $exception) {
            throw new RuntimeException(
                "Bazaar could not find a default Filament panel to attach to. Mark one panel ->default() in your PanelProvider, or set config('bazaar.panel').",
                previous: $exception,
            );
        }
    }
}
