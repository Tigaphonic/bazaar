<?php

namespace Tigaphonic\Bazaar\Install\Commands;

use Filament\PanelRegistry;
use Illuminate\Console\Command;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;

class BazaarInstallCommand extends Command
{
    public $signature = 'bazaar:install';

    public $description = 'Connect Bazaar to the client project\'s existing Filament panel.';

    public function handle(PanelRegistry $registry): int
    {
        $panel = PanelResolver::resolve($registry);

        $this->components->info("Bazaar connected to the '{$panel->getId()}' Filament panel.");

        return self::SUCCESS;
    }
}
