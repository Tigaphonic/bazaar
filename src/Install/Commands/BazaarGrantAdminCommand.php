<?php

namespace Tigaphonic\Bazaar\Install\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Tigaphonic\Bazaar\User\Services\UserService;

/**
 * Bootstrap for a fresh install: the first Staff member has no Role, so no
 * screen gated by a permission opens for them. This gives them the Admin Role.
 */
class BazaarGrantAdminCommand extends Command
{
    protected $signature = 'bazaar:grant-admin {email : Email of the existing user}';

    protected $description = 'Give a user the Admin Role holding every Bazaar permission.';

    public function handle(UserService $users): int
    {
        try {
            $users->grantAdmin((string) $this->argument('email'));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("{$this->argument('email')} now has the Admin Role.");

        return self::SUCCESS;
    }
}
