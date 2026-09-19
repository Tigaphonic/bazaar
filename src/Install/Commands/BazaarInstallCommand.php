<?php

namespace Tigaphonic\Bazaar\Install\Commands;

use Filament\PanelRegistry;
use Illuminate\Console\Command;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Tigaphonic\Bazaar\Install\Support\PanelResolver;

class BazaarInstallCommand extends Command
{
    protected $signature = 'bazaar:install';

    protected $description = 'Connect Bazaar to the client project\'s existing Filament panel.';

    public function handle(PanelRegistry $registry): int
    {
        $panel = PanelResolver::resolve($registry);

        // Publishes Shell's registered theme/CSS/JS (AD-33) from the package's
        // resources/dist into the host app's own public/ directory -- pure PHP
        // file copy (Filament's own `filament:assets` command), never npm/node,
        // consistent with "composer require + one Artisan command" (Story 1.1).
        // Guarded: some minimal test harnesses (this package's own Pest suite)
        // boot Filament's manager/facade without registering every Filament
        // sub-package's own console commands, so the command may not exist
        // there -- a real client install always has it (ships with
        // filament/support, which filament/filament always requires).
        if ($this->getApplication()?->has('filament:assets')) {
            if ($this->callSilently('filament:assets') !== static::SUCCESS) {
                $this->components->error('Failed to publish Filament assets.');

                return static::FAILURE;
            }
        }

        // RoleResource (Story 1.3) reads/writes spatie/laravel-permission's
        // own roles/permissions/model_has_roles/model_has_permissions/
        // role_has_permissions tables -- publish that package's migration
        // into the host app's database/migrations/ so it exists to run.
        // Guarded the same way as `filament:assets` above: some minimal
        // test harnesses don't register every console command. Deliberately
        // never calls `migrate` itself -- that would also apply any other
        // pending host-app migration, a bigger side effect than this
        // command should have.
        if ($this->getApplication()?->has('vendor:publish')) {
            // laravel-package-tools registers this tag as
            // "{$package->shortName()}-migrations", where shortName() strips
            // the "laravel-" prefix off the package name
            // ("laravel-permission" -> "permission"). The tag is therefore
            // "permission-migrations", not "laravel-permission-migrations" --
            // vendor:publish silently no-ops (still exits SUCCESS) on an
            // unknown tag, so a wrong tag here fails to publish the
            // migration without ever raising an error.
            if ($this->callSilently('vendor:publish', [
                '--provider' => PermissionServiceProvider::class,
                '--tag' => 'permission-migrations',
            ]) !== static::SUCCESS) {
                $this->components->error('Failed to publish spatie/laravel-permission migrations.');

                return static::FAILURE;
            }

            // Global Settings (Story 1.6) stores its values in
            // spatie/laravel-settings' own `settings` table -- same
            // dependency-owned-table rule as the permission tables above.
            if ($this->callSilently('vendor:publish', [
                '--provider' => LaravelSettingsServiceProvider::class,
                '--tag' => 'migrations',
            ]) !== static::SUCCESS) {
                $this->components->error('Failed to publish spatie/laravel-settings migrations.');

                return static::FAILURE;
            }
        } else {
            $this->components->error('The vendor:publish command is not available.');

            return static::FAILURE;
        }

        $this->components->info("Bazaar connected to the '{$panel->getId()}' Filament panel.");
        $this->components->info('Run `php artisan migrate` to create the roles/permissions (spatie/laravel-permission) and settings (spatie/laravel-settings) tables the Roles and Global Settings screens need.');
        $this->components->warn("Manual step required: add `use Spatie\Permission\Traits\HasRoles;` to your app's User model so the Users screen's roles checklist works.");

        return self::SUCCESS;
    }
}
