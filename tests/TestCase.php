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

    // Testbench ignores every vendor package's own auto-discovered
    // ServiceProvider by default (Orchestra\Testbench\TestCase's own
    // $enablesPackageDiscoveries = false) -- spatie/laravel-permission's
    // PermissionServiceProvider (hasConfigFile('permission')) needs to
    // actually boot for config('permission.*') to be populated before the
    // migration below can run. Laravel's own Application::register()
    // dedupes by class name, so this has no effect on the providers this
    // class already registers explicitly in getPackageProviders().
    protected $enablesPackageDiscoveries = true;

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

        // Some tests (e.g. BazaarInstallCommandTest) call `bazaar:install`,
        // which publishes spatie/laravel-permission's migration into the
        // app's own database/migrations/ directory. Pest runs this whole
        // package's suite in a single process against the same on-disk
        // Testbench skeleton, so a file published by an earlier test would
        // otherwise leak into every later test's `php artisan migrate`
        // (DomainMigrationTest calls it plain) and collide with the
        // roles/permissions tables this setUp() creates directly below.
        // Purge any such leaked file before every test so each test starts
        // from the same clean slate regardless of run order.
        foreach ([
            ...(glob(database_path('migrations/*_create_permission_tables.php')) ?: []),
            ...(glob(database_path('migrations/*_create_settings_table.php')) ?: []),
            ...(glob(database_path('migrations/*_create_media_table.php')) ?: []),
        ] as $leakedMigration) {
            if (!unlink($leakedMigration)) {
                throw new \RuntimeException("Failed to remove leaked migration file before test: {$leakedMigration}");
            }
        }

        // spatie/laravel-permission's own migration (roles, permissions,
        // model_has_roles, model_has_permissions, role_has_permissions) --
        // loaded exactly as the package ships it (AD-18: dependency-owned
        // tables keep their native, unmodified shape, never copied into
        // Bazaar's own database/migrations/). The package's own
        // PermissionServiceProvider declares hasMigrations() but never
        // ->runsMigrations(), so nothing loads this automatically; this
        // mirrors spatie/laravel-permission's own test suite pattern of
        // `include`-ing the stub and running ->up() on the instance it
        // returns.
        (function (): void {
            if (!\Illuminate\Support\Facades\Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
                $migration = include __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
                $migration->up();
            }
        })();

        // spatie/laravel-settings' own `settings` table (published to the host
        // by `bazaar:install`, so likewise never copied into this package's
        // database/migrations/), then Bazaar's data-only default-rows migration
        // that needs it. The permission-seed migration is deliberately not run
        // here: tests create `manage-settings` themselves.
        (function (): void {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                (include __DIR__.'/../vendor/spatie/laravel-settings/database/migrations/create_settings_table.php.stub')->up();
            }

            (include __DIR__.'/../database/data-migrations/seed_bazaar_settings_defaults.php')->up();
        })();

        // spatie/laravel-medialibrary's own `media` table (published to the
        // host by `bazaar:install`, so likewise never copied into this
        // package's database/migrations/), plus the workbench fixture table
        // the Media pipeline tests attach images to.
        (function (): void {
            if (!\Illuminate\Support\Facades\Schema::hasTable('media')) {
                (include __DIR__.'/../vendor/spatie/laravel-medialibrary/database/migrations/create_media_table.php.stub')->up();
            }

            if (!\Illuminate\Support\Facades\Schema::hasTable('media_test_models')) {
                \Illuminate\Support\Facades\Schema::create('media_test_models', function (\Illuminate\Database\Schema\Blueprint $table): void {
                    $table->id();
                    $table->timestamps();
                });
            }
        })();

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
