<?php

// Story 1.3 RED-PHASE scaffold. AC2: "Given sebuah Role sudah dipakai beberapa User.
// When Staff mengubah permission Role tsb. Then perubahan berlaku ke semua User
// pemegang Role itu secara instan." This exercises spatie/laravel-permission's own
// cache invalidation (PermissionRegistrar::forgetCachedPermissions(), triggered
// automatically by syncPermissions()) through Bazaar's own RoleService -- not a
// bespoke propagation mechanism, per epic-1-context.md ("dibangun di atas
// spatie/laravel-permission").
//
// AC3's third clause is verified here too: "'Approval Role' dirujuk di berbagai
// domain ... sistem tidak memiliki role hardcoded bernama 'Approval Role'" -- any Role
// holding the relevant permission must gate the same action, with no special case for
// a role literally named "Approval Role".
//
// Workbench\App\Models\User is used here (not the generic Illuminate\Foundation\Auth\
// User the Shell tests use for theme/locale preferences) because this AC is
// specifically about the User<->Role relationship: UserResource::getModel() already
// resolves to this same model via config('auth.providers.users.model') (Testbench's
// workbench convention) -- it is the "host User model" this suite already treats as
// canonical for User & Access.

use Spatie\Permission\Models\Permission;
use Tigaphonic\Bazaar\User\Services\RoleService;
use Workbench\App\Models\User;

it('propagates a Role permission change instantly to every User holding that Role', function () {
    Permission::create(['name' => 'approve return']);
    Permission::create(['name' => 'approve refund']);

    $role = app(RoleService::class)->create(['name' => 'Supervisor Retur', 'permissions' => ['approve return']]);

    $rara = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $bagas = User::create(['name' => 'Bagas', 'email' => 'bagas@example.com', 'password' => bcrypt('password')]);
    $rara->assignRole($role);
    $bagas->assignRole($role);

    app(RoleService::class)->update($role, ['name' => 'Supervisor Retur', 'permissions' => ['approve refund']]);

    expect($rara->fresh()->hasPermissionTo('approve refund'))->toBeTrue()
        ->and($rara->fresh()->hasPermissionTo('approve return'))->toBeFalse()
        ->and($bagas->fresh()->hasPermissionTo('approve refund'))->toBeTrue()
        ->and($bagas->fresh()->hasPermissionTo('approve return'))->toBeFalse();
});

it('never hardcodes "Approval Role" -- any Role holding the relevant permission gates the same action', function () {
    Permission::create(['name' => 'approve refund']);

    $roleA = app(RoleService::class)->create(['name' => 'Supervisor Retur', 'permissions' => ['approve refund']]);
    $roleB = app(RoleService::class)->create(['name' => 'CS Lead', 'permissions' => ['approve refund']]);

    $rara = User::create(['name' => 'Rara', 'email' => 'rara2@example.com', 'password' => bcrypt('password')]);
    $dian = User::create(['name' => 'Dian', 'email' => 'dian@example.com', 'password' => bcrypt('password')]);
    $rara->assignRole($roleA);
    $dian->assignRole($roleB);

    expect($rara->fresh()->hasPermissionTo('approve refund'))->toBeTrue()
        ->and($dian->fresh()->hasPermissionTo('approve refund'))->toBeTrue();
});
