<?php

namespace Tigaphonic\Bazaar\Tests;

// Routes are loaded at boot, so the opt-in flag must be set before the
// service provider boots; flipping config() inside a test body is too late.
class ApiEnabledTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum's own personal_access_tokens table, unmodified (AD-18).
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/sanctum/database/migrations');
    }

    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('bazaar.api.enabled', true);
    }
}
