<?php

// Story 1.3 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Services\RoleService does not
// exist yet. AC1: "Staff berwenang membuka User & Access -> Roles. Staff membuat Role
// baru dan mencentang sejumlah permission (mis. 'can approve publish Item', 'can
// approve Return'). Role tersimpan dengan kumpulan permission granular tsb, tanpa
// perlu deploy kode."
//
// AD-5 binds all domains: presentation code (Filament Resource) may only call a
// Service, never a Model directly for a mutation -- RoleService is that Service. The
// underlying Model is Spatie\Permission\Models\Role (spatie/laravel-permission, per
// epic-1-context.md "dibangun di atas spatie/laravel-permission"); Bazaar does not
// subclass or re-model it -- AD-18 scopes dependency-owned tables/models (including
// spatie/laravel-permission's) to keep their native, unmodified shape.

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Services\RoleService;

it('creates a Role with the given granular permissions, no deploy required', function () {
    Permission::create(['name' => 'approve publish item']);
    Permission::create(['name' => 'approve return']);

    $role = app(RoleService::class)->create([
        'name' => 'Supervisor Retur',
        'permissions' => ['approve return'],
    ]);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->name)->toBe('Supervisor Retur')
        ->and($role->fresh()->hasPermissionTo('approve return'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('approve publish item'))->toBeFalse();
})->skip('Story 1.3 not implemented — Tigaphonic\Bazaar\User\Services\RoleService does not exist yet');

it('creates a Role with zero permissions when none are checked', function () {
    $role = app(RoleService::class)->create(['name' => 'Read Only', 'permissions' => []]);

    expect($role->fresh()->permissions)->toHaveCount(0);
})->skip('Story 1.3 not implemented — RoleService::create() does not exist yet');

it('replaces a Role\'s permission set on update rather than appending to it', function () {
    Permission::create(['name' => 'approve return']);
    Permission::create(['name' => 'approve refund']);

    $role = app(RoleService::class)->create(['name' => 'Supervisor Retur', 'permissions' => ['approve return']]);

    app(RoleService::class)->update($role, ['name' => 'Supervisor Retur', 'permissions' => ['approve refund']]);

    $role = $role->fresh();
    expect($role->hasPermissionTo('approve refund'))->toBeTrue()
        ->and($role->hasPermissionTo('approve return'))->toBeFalse();
})->skip('Story 1.3 not implemented — RoleService::update() does not exist yet');

it('deletes a Role', function () {
    $role = app(RoleService::class)->create(['name' => 'Temporary Role', 'permissions' => []]);

    app(RoleService::class)->delete($role);

    expect(Role::find($role->id))->toBeNull();
})->skip('Story 1.3 not implemented — RoleService::delete() does not exist yet');
