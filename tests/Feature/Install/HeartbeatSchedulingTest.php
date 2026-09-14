<?php

use Illuminate\Support\Facades\Cache;
use Tigaphonic\Bazaar\Install\Jobs\RecordQueueHeartbeat;

it('running the scheduler writes the scheduler heartbeat cache key', function () {
    Cache::forget('bazaar:heartbeat:scheduler_last_tick');

    $this->artisan('schedule:run');

    expect(Cache::get('bazaar:heartbeat:scheduler_last_tick'))->not->toBeNull();
});

it('processing the queue heartbeat job writes the cache key', function () {
    Cache::forget('bazaar:heartbeat:queue_last_processed');

    (new RecordQueueHeartbeat)->handle();

    expect(Cache::get('bazaar:heartbeat:queue_last_processed'))->not->toBeNull();
});
