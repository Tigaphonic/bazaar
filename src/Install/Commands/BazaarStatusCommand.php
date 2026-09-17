<?php

namespace Tigaphonic\Bazaar\Install\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * AD-17: reports whether the scheduler and queue worker heartbeats
 * (written by BazaarServiceProvider's scheduled task/job) are still fresh.
 */
class BazaarStatusCommand extends Command
{
    protected $signature = 'bazaar:status';

    protected $description = 'Report scheduler/queue heartbeats, published assets, and migration status.';

    private const STALE_AFTER_MINUTES = 2;

    /**
     * Public paths Filament's `filament:assets` command copies Shell's
     * compiled CSS/JS to (Filament\Support\Assets\Css/Js::getRelativePublicPath(),
     * built from the 'bazaar' package and 'bazaar-shell' asset id registered
     * in BazaarServiceProvider::registerShellTheme()). Never built from the
     * live FilamentAsset registry here -- that registry is only populated
     * after BazaarServiceProvider::packageBooted() runs on a fully booted
     * app, which this command already is, but hardcoding the same values
     * Filament itself would derive keeps this check dependency-free of
     * Filament's own Asset classes.
     */
    private const PUBLISHED_ASSETS = [
        'css/bazaar/bazaar-shell.css',
        'js/bazaar/bazaar-shell.js',
    ];

    /**
     * Tables Bazaar's own migrations create (BazaarServiceProvider's
     * hasMigrations()). Checked directly against the schema rather than
     * Laravel's migrations table, since a host app may have run `migrate`
     * before a migration existed here and never re-run it (the bug that
     * originally motivated this check).
     */
    private const OWNED_TABLES = [
        'bazaar_user_preferences',
        'bazaar_user_statuses',
    ];

    public function handle(): int
    {
        $this->reportHeartbeat('scheduler', 'bazaar:heartbeat:scheduler_last_tick');
        $this->reportHeartbeat('queue', 'bazaar:heartbeat:queue_last_processed');
        $this->reportPublishedAssets();
        $this->reportMigrations();

        return self::SUCCESS;
    }

    private function reportPublishedAssets(): void
    {
        foreach (self::PUBLISHED_ASSETS as $relativePath) {
            if (file_exists(public_path($relativePath))) {
                $this->components->info("asset: {$relativePath} published.");

                continue;
            }

            $this->components->warn("asset: {$relativePath} missing -- run `php artisan bazaar:install` (or `php artisan filament:assets`).");
        }
    }

    private function reportMigrations(): void
    {
        foreach (self::OWNED_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $this->components->info("table: {$table} exists.");

                continue;
            }

            $this->components->warn("table: {$table} missing -- run `php artisan migrate`.");
        }
    }

    private function reportHeartbeat(string $label, string $cacheKey): void
    {
        /** @var Carbon|null $lastTick */
        $lastTick = Cache::get($cacheKey);

        if ($lastTick === null) {
            $this->components->warn("{$label}: no heartbeat recorded yet.");

            return;
        }

        $isStale = $lastTick->diffInMinutes(now()) > self::STALE_AFTER_MINUTES;

        if ($isStale) {
            $this->components->warn("{$label}: stale, last tick {$lastTick->diffForHumans()}.");

            return;
        }

        $this->components->info("{$label}: healthy, last tick {$lastTick->diffForHumans()}.");
    }
}
