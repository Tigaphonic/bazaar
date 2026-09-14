<?php

namespace Tigaphonic\Bazaar\Tests;

use Filament\FilamentServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
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

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Tigaphonic\\Bazaar\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
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
