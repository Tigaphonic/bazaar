<?php

namespace Tigaphonic\Bazaar\Tests;

use Filament\FilamentServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Tigaphonic\Bazaar\BazaarServiceProvider;
use Workbench\App\Providers\Filament\TestPanelProvider;

use function Orchestra\Testbench\default_migration_path;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        // Simulates the client project's own pre-existing Laravel core tables
        // (users, sessions, ...) that already exist before Bazaar is installed.
        $this->loadMigrationsFrom(default_migration_path());

        // Bazaar's own migrations (bazaar_user_preferences, Story 1.2) --
        // loaded the same way as the host's own tables above, so every Shell
        // test that touches UserPreferences has the table available without
        // each test file needing its own `$this->artisan('migrate')` call.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Several Shell tests instantiate the framework's own base
        // Illuminate\Foundation\Auth\User directly (rather than the
        // workbench's own fillable-configured model) to represent "whatever
        // host app user model this attaches to" generically -- that base
        // class declares no $fillable, so mass assignment needs unguarding
        // here rather than in each test file.
        Model::unguard();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Tigaphonic\\Bazaar\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function tearDown(): void
    {
        // Pairs with Model::unguard() in setUp() -- Pest runs the whole suite
        // in one process, so leaving every Model globally unguarded after a
        // Shell test would mask fillable-related bugs in every test that
        // runs afterward.
        Model::reguard();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            TestPanelProvider::class,
            BazaarServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
