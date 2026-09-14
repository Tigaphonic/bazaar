<?php

namespace Tigaphonic\Bazaar\Install\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * AD-17: reports whether the scheduler and queue worker heartbeats
 * (written by BazaarServiceProvider's scheduled task/job) are still fresh.
 */
class BazaarStatusCommand extends Command
{
    protected $signature = 'bazaar:status';

    protected $description = 'Report scheduler and queue worker heartbeat staleness.';

    private const STALE_AFTER_MINUTES = 2;

    public function handle(): int
    {
        $this->reportHeartbeat('scheduler', 'bazaar:heartbeat:scheduler_last_tick');
        $this->reportHeartbeat('queue', 'bazaar:heartbeat:queue_last_processed');

        return self::SUCCESS;
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
