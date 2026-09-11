<?php

namespace Tigaphonic\Bazaar\Commands;

use Illuminate\Console\Command;

class BazaarCommand extends Command
{
    public $signature = 'bazaar';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
