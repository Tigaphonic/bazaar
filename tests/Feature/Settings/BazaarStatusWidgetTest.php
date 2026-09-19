<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tigaphonic\Bazaar\Install\Filament\Widgets\BazaarStatusWidget;

it('reports missing when no heartbeat was recorded', function () {
    $heartbeats = (new BazaarStatusWidget)->heartbeats();

    expect($heartbeats['scheduler']['state'])->toBe('missing')
        ->and($heartbeats['queue']['state'])->toBe('missing');
});

it('reports healthy for a fresh tick and stale for an old one', function () {
    Cache::put('bazaar:heartbeat:scheduler_last_tick', now());
    Cache::put('bazaar:heartbeat:queue_last_processed', now()->subMinutes(5));

    $heartbeats = (new BazaarStatusWidget)->heartbeats();

    expect($heartbeats['scheduler']['state'])->toBe('healthy')
        ->and($heartbeats['queue']['state'])->toBe('stale');
});

it('renders the translated health state', function () {
    Cache::put('bazaar:heartbeat:scheduler_last_tick', now());

    Livewire::test(BazaarStatusWidget::class)
        ->assertSee(__('bazaar::settings.status_state_healthy'))
        ->assertSee(__('bazaar::settings.status_state_missing'));
});
