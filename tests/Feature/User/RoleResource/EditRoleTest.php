<?php

// Story 1.3 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\
// Pages\EditRole does not exist yet.
//
// AC2: "Given sebuah Role sudah dipakai beberapa User. When Staff mengubah permission
// Role tsb. Then perubahan berlaku ke semua User pemegang Role itu secara instan."
// The instant-propagation half (through actual User holders) is covered in
// tests/Feature/User/RolePermissionPropagationTest.php -- this file covers the
// dashboard form contract only: pre-filled state and persistence of the new
// permission set on the Role record itself.

use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\EditRole;

it('pre-fills the form with the Role\'s current name and checked permissions', function () {
    Permission::create(['name' => 'approve return']);
    Permission::create(['name' => 'approve refund']);

    $role = Role::create(['name' => 'Supervisor Retur']);
    $role->givePermissionTo('approve return');

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertFormSet([
            'name' => 'Supervisor Retur',
            'permissions' => ['approve return'],
        ]);
})->skip('Story 1.3 not implemented — RoleResource\Pages\EditRole does not exist yet');

it('saves an updated permission set for an existing Role', function () {
    Permission::create(['name' => 'approve return']);
    Permission::create(['name' => 'approve refund']);

    $role = Role::create(['name' => 'Supervisor Retur']);
    $role->givePermissionTo('approve return');

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['permissions' => ['approve refund']])
        ->call('save')
        ->assertHasNoFormErrors();

    $role = $role->fresh();
    expect($role->hasPermissionTo('approve refund'))->toBeTrue()
        ->and($role->hasPermissionTo('approve return'))->toBeFalse();
})->skip('Story 1.3 not implemented — EditRole does not exist yet');
