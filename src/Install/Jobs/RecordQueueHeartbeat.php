<?php

namespace Tigaphonic\Bazaar\Install\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * AD-17: a trivial job dispatched on the same schedule as the scheduler
 * heartbeat. Its own execution proves the queue worker is actually running.
 */
class RecordQueueHeartbeat implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        Cache::put('bazaar:heartbeat:queue_last_processed', now());
    }
}
