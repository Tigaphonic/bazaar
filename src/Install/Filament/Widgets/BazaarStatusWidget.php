<?php

namespace Tigaphonic\Bazaar\Install\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Pull-based health check (AD-17): staleness is evaluated when the page
 * renders, from the same heartbeat cache keys `bazaar:status` reads.
 */
class BazaarStatusWidget extends Widget
{
    private const STALE_AFTER_MINUTES = 2;

    // Larastan cannot see the package's own view namespace.
    // @phpstan-ignore property.defaultValue
    protected string $view = 'bazaar::install.status-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, array{state: string, last_tick: ?Carbon}>
     */
    public function heartbeats(): array
    {
        return [
            'scheduler' => $this->evaluate('bazaar:heartbeat:scheduler_last_tick'),
            'queue' => $this->evaluate('bazaar:heartbeat:queue_last_processed'),
        ];
    }

    /**
     * @return array{state: string, last_tick: ?Carbon}
     */
    private function evaluate(string $cacheKey): array
    {
        /** @var mixed $lastTick */
        $lastTick = Cache::get($cacheKey);

        if ($lastTick !== null && ! $lastTick instanceof Carbon) {
            $lastTick = Carbon::parse($lastTick);
        }

        $state = match (true) {
            $lastTick === null => 'missing',
            $lastTick->diffInMinutes(now()) > self::STALE_AFTER_MINUTES => 'stale',
            default => 'healthy',
        };

        return ['state' => $state, 'last_tick' => $lastTick];
    }
}
