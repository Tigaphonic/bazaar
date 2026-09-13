<?php

it('runs bazaar:status without error', function () {
    $this->artisan('bazaar:status')->assertSuccessful();
})->skip('AC4 / AD-17 — bazaar:status command does not exist yet (Story 1.1)');

it('reports scheduler heartbeat staleness', function () {
    $this->artisan('bazaar:status')
        ->expectsOutputToContain('scheduler')
        ->assertSuccessful();
})->skip('AC4 / AD-17 — scheduler_last_tick heartbeat is not implemented yet (Story 1.1)');

it('reports queue worker heartbeat staleness', function () {
    $this->artisan('bazaar:status')
        ->expectsOutputToContain('queue')
        ->assertSuccessful();
})->skip('AC4 / AD-17 — queue_last_processed heartbeat is not implemented yet (Story 1.1)');
