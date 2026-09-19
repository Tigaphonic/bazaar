<?php

namespace Tigaphonic\Bazaar\User\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Sole entry point for mutating Role/Permission state (AD-5) — presentation
 * code (RoleResource + its Pages) may only call this Service, never
 * Role::create()/$record->update()/$record->delete() directly.
 *
 * The underlying Model is Spatie\Permission\Models\Role
 * (spatie/laravel-permission) — Bazaar never subclasses or re-models it
 * (AD-18 scopes dependency-owned tables/models to keep their native shape).
 */
class RoleService
{
    /**
     * @param  array{name: string, permissions?: string[]}  $data
     */
    public function create(array $data): Role
    {
        // Role::create() is typed by spatie/laravel-permission itself as
        // returning `RoleContract|Role` (its own duplicate-name guard is
        // meant to be reusable by any RoleContract implementation) -- going
        // through the model's own query builder instead keeps the return
        // type concretely `Role`, since our own form validation
        // (`unique(table: 'roles')`, AC1's "Nama Role duplikat" case)
        // already guards uniqueness before this Service is ever called.
        $role = Role::query()->create(['name' => $data['name']]);

        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    /**
     * @param  array{name: string, permissions?: string[]}  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);

        // syncPermissions() replaces the Role's permission set rather than
        // appending to it (AC2) -- it also triggers spatie/laravel-permission's
        // own PermissionRegistrar::forgetCachedPermissions() automatically,
        // which is what makes the change propagate instantly to every User
        // holding this Role.
        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    /**
     * Creates any shipped permission that is missing, then returns every
     * permission as name => name for the Role form checklist.
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    public function permissionOptions(): \Illuminate\Support\Collection
    {
        $this->ensureShippedPermissions();

        return Permission::query()->pluck('name', 'name');
    }

    /**
     * Role that holds every permission; created if absent and re-synced so a
     * newly shipped permission reaches it.
     */
    public function ensureAdminRole(): Role
    {
        $this->ensureShippedPermissions();

        $role = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => config('auth.defaults.guard')]);
        $role->syncPermissions(Permission::query()->where('guard_name', $role->guard_name)->pluck('name')->all());

        return $role;
    }

    private function ensureShippedPermissions(): void
    {
        foreach ((array) config('bazaar.permissions', []) as $name) {
            Permission::findOrCreate($name);
        }
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
