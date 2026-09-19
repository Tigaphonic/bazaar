<?php

// Story 1.3 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\
// Pages\CreateRole does not exist yet.
//
// AC1: "Given Staff berwenang membuka User & Access -> Roles. When Staff membuat Role
// baru dan mencentang sejumlah permission (mis. 'can approve publish Item', 'can
// approve Return'). Then Role tersimpan dengan kumpulan permission granular tsb, tanpa
// perlu deploy kode."

use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\CreateRole;

it('creates a Role with checked permissions via the dashboard form', function () {
    Permission::create(['name' => 'approve publish item']);
    Permission::create(['name' => 'approve return']);

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Supervisor Retur',
            'permissions' => ['approve return'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::where('name', 'Supervisor Retur')->first();
    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('approve return'))->toBeTrue()
        ->and($role->hasPermissionTo('approve publish item'))->toBeFalse();
});

it('rejects a Role name left blank', function () {
    Livewire::test(CreateRole::class)
        ->fillForm(['name' => '', 'permissions' => []])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('rejects a duplicate Role name', function () {
    Role::create(['name' => 'Supervisor Retur']);

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Supervisor Retur', 'permissions' => []])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

it('trims whitespace from the Role name', function () {
    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => '   Supervisor Spasi   ',
            'permissions' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::where('name', 'Supervisor Spasi')->exists())->toBeTrue();
});

it('RoleResource form schema includes manage-settings in the permissions checkbox options', function () {
    app(\Tigaphonic\Bazaar\User\Services\RoleService::class)->ensureAdminRole();

    $component = Livewire::test(CreateRole::class);
    $field = $component->instance()->getForm('form')->getComponent('permissions');
    
    expect($field->getOptions())->toHaveKey('manage-settings');
});
