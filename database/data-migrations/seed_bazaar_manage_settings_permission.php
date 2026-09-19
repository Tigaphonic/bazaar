<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

/**
 * Makes the `manage-settings` permission selectable in the Role form. Skipped
 * when spatie/laravel-permission's tables do not exist yet (its migration runs
 * first in a normal install, since its timestamped filename sorts ahead).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        foreach ((array) config('bazaar.permissions', []) as $permission) {
            Permission::findOrCreate($permission);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            return;
        }

        foreach ((array) config('bazaar.permissions', []) as $permission) {
            Permission::query()->where('name', $permission)->delete();
        }
    }
};
