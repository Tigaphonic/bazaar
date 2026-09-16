<?php

// Story 1.3 RED-PHASE scaffold. AC3: "Role bisa dihapus dari dashboard (dengan modal
// konfirmasi karena aksi sulit dibalik)." Filament's own Filament\Actions\DeleteAction
// already calls ->requiresConfirmation() in its setUp() by default
// (vendor/filament/actions/src/DeleteAction.php) -- wiring the stock action into the
// table is sufficient, no bespoke confirmation mechanism needed. These tests prove
// RoleResource actually wires it in and that deletion only happens once the mounted
// action is confirmed/called, not on mount alone.

use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\ListRoles;

it('exposes a delete table action for each Role row', function () {
    Role::create(['name' => 'Temporary Role']);

    Livewire::test(ListRoles::class)
        ->assertTableActionExists('delete');
})->skip('Story 1.3 not implemented — RoleResource\Pages\ListRoles does not exist yet');

it('does not delete the Role merely by mounting the delete confirmation modal', function () {
    $role = Role::create(['name' => 'Temporary Role']);

    Livewire::test(ListRoles::class)
        ->mountTableAction('delete', $role)
        ->assertTableActionMounted('delete');

    expect(Role::find($role->id))->not->toBeNull();
})->skip('Story 1.3 not implemented — ListRoles does not exist yet');

it('deletes the Role once the delete action is confirmed', function () {
    $role = Role::create(['name' => 'Temporary Role']);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role);

    expect(Role::find($role->id))->toBeNull();
})->skip('Story 1.3 not implemented — ListRoles does not exist yet');
