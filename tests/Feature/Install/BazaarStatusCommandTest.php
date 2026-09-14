<?php

use Illuminate\Support\Facades\Cache;

it('runs bazaar:status without error', function () {
    $this->artisan('bazaar:status')->assertSuccessful();
});

it('reports scheduler heartbeat staleness', function () {
    $this->artisan('bazaar:status')
        ->expectsOutputToContain('scheduler')
        ->assertSuccessful();
});

it('reports queue worker heartbeat staleness', function () {
    $this->artisan('bazaar:status')
        ->expectsOutputToContain('queue')
        ->assertSuccessful();
});

it('reports a fresh heartbeat as healthy', function () {
    Cache::put('bazaar:heartbeat:scheduler_last_tick', now());
    Cache::put('bazaar:heartbeat:queue_last_processed', now());

    $this->artisan('bazaar:status')
        ->expectsOutputToContain('scheduler: healthy')
        ->expectsOutputToContain('queue: healthy')
        ->assertSuccessful();
});

it('reports an old heartbeat as stale', function () {
    Cache::put('bazaar:heartbeat:scheduler_last_tick', now()->subMinutes(5));
    Cache::put('bazaar:heartbeat:queue_last_processed', now()->subMinutes(5));

    $this->artisan('bazaar:status')
        ->expectsOutputToContain('scheduler: stale')
        ->expectsOutputToContain('queue: stale')
        ->assertSuccessful();
});
