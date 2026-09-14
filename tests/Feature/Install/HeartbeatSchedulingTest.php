<?php

use Illuminate\Support\Facades\Cache;
use Tigaphonic\Bazaar\Install\Jobs\RecordQueueHeartbeat;

it('running the scheduler writes the scheduler heartbeat cache key and dispatches the queue job', function () {
    Cache::forget('bazaar:heartbeat:scheduler_last_tick');
    Illuminate\Support\Facades\Bus::fake();

    $this->artisan('schedule:run');

    expect(Cache::get('bazaar:heartbeat:scheduler_last_tick'))->not->toBeNull();
    Illuminate\Support\Facades\Bus::assertDispatched(RecordQueueHeartbeat::class);
});

it('processing the queue heartbeat job writes the cache key', function () {
    Cache::forget('bazaar:heartbeat:queue_last_processed');

    (new RecordQueueHeartbeat)->handle();

    expect(Cache::get('bazaar:heartbeat:queue_last_processed'))->not->toBeNull();
});
