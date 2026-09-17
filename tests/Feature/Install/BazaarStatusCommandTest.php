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

it('reports unpublished Shell assets as missing', function () {
    // The testbench skeleton's public/ dir is shared and persists across
    // tests (other tests in this suite, e.g. BazaarInstallCommandTest, run
    // `bazaar:install` and never clean up after themselves) -- clear it
    // here so this test doesn't depend on execution order.
    @unlink(public_path('css/bazaar/bazaar-shell.css'));
    @unlink(public_path('js/bazaar/bazaar-shell.js'));

    $this->artisan('bazaar:status')
        ->expectsOutputToContain('asset: css/bazaar/bazaar-shell.css missing')
        ->expectsOutputToContain('asset: js/bazaar/bazaar-shell.js missing')
        ->assertSuccessful();
});

it('reports published Shell assets as present', function () {
    $cssPath = public_path('css/bazaar/bazaar-shell.css');
    $jsPath = public_path('js/bazaar/bazaar-shell.js');

    (new \Illuminate\Filesystem\Filesystem)->ensureDirectoryExists(dirname($cssPath));
    (new \Illuminate\Filesystem\Filesystem)->ensureDirectoryExists(dirname($jsPath));
    file_put_contents($cssPath, '');
    file_put_contents($jsPath, '');

    try {
        $this->artisan('bazaar:status')
            ->expectsOutputToContain('asset: css/bazaar/bazaar-shell.css published')
            ->expectsOutputToContain('asset: js/bazaar/bazaar-shell.js published')
            ->assertSuccessful();
    } finally {
        @unlink($cssPath);
        @unlink($jsPath);
    }
});

it('reports Bazaar-owned tables that exist', function () {
    $this->artisan('bazaar:status')
        ->expectsOutputToContain('table: bazaar_user_preferences exists')
        ->expectsOutputToContain('table: bazaar_user_statuses exists')
        ->assertSuccessful();
});

it('reports a missing Bazaar-owned table', function () {
    \Illuminate\Support\Facades\Schema::drop('bazaar_user_statuses');

    $this->artisan('bazaar:status')
        ->expectsOutputToContain('table: bazaar_user_statuses missing')
        ->assertSuccessful();
});
