<?php

namespace Tigaphonic\Bazaar;

use Filament\Navigation\NavigationGroup;
use Filament\PanelRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tigaphonic\Bazaar\Install\Commands\BazaarInstallCommand;
use Tigaphonic\Bazaar\Install\Commands\BazaarStatusCommand;
use Tigaphonic\Bazaar\Install\Jobs\RecordQueueHeartbeat;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;

class BazaarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('bazaar')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommands([
                BazaarInstallCommand::class,
                BazaarStatusCommand::class,
            ]);
    }

    /**
     * Registered during the register phase (not boot) so it queues before
     * Filament's own boot() eagerly resolves the PanelRegistry singleton --
     * afterResolving() never fires retroactively for an already-resolved one.
     */
    public function packageRegistered(): void
    {
        $this->app->afterResolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            PanelResolver::resolve($registry)
                ->resources((array) config('bazaar.resources'))
                ->pages((array) config('bazaar.pages'))
                ->navigationGroups([
                    NavigationGroup::make('User & Access'),
                    NavigationGroup::make('Global Settings'),
                ]);
        });
    }

    public function packageBooted(): void
    {
        $this->app->booted(function (): void {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);

            $schedule
                ->call(fn () => Cache::put('bazaar:heartbeat:scheduler_last_tick', now()))
                ->everyMinute();

            $schedule
                ->job(new RecordQueueHeartbeat)
                ->everyMinute();
        });
    }
}
