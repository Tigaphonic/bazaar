<?php

namespace Tigaphonic\Bazaar\Install\Commands;

use Filament\PanelRegistry;
use Illuminate\Console\Command;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;

class BazaarInstallCommand extends Command
{
    protected $signature = 'bazaar:install';

    protected $description = 'Connect Bazaar to the client project\'s existing Filament panel.';

    public function handle(PanelRegistry $registry): int
    {
        $panel = PanelResolver::resolve($registry);

        // Publishes Shell's registered theme/CSS/JS (AD-33) from the package's
        // resources/dist into the host app's own public/ directory -- pure PHP
        // file copy (Filament's own `filament:assets` command), never npm/node,
        // consistent with "composer require + one Artisan command" (Story 1.1).
        // Guarded: some minimal test harnesses (this package's own Pest suite)
        // boot Filament's manager/facade without registering every Filament
        // sub-package's own console commands, so the command may not exist
        // there -- a real client install always has it (ships with
        // filament/support, which filament/filament always requires).
        if ($this->getApplication()?->has('filament:assets')) {
            if ($this->callSilently('filament:assets') !== static::SUCCESS) {
                $this->components->error('Failed to publish Filament assets.');
                return static::FAILURE;
            }
        }

        $this->components->info("Bazaar connected to the '{$panel->getId()}' Filament panel.");

        return self::SUCCESS;
    }
}
