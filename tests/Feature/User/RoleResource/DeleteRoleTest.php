<?php

// Story 1.3 RED-PHASE scaffold. AC3: "Role bisa dihapus dari dashboard (dengan modal
// konfirmasi karena aksi sulit dibalik)." Filament's own Filament\Actions\DeleteAction
// already calls ->requiresConfirmation() in its setUp() by default
// (vendor/filament/actions/src/DeleteAction.php) -- wiring the stock action into the
// table is sufficient, no bespoke confirmation mechanism needed. These tests prove
// RoleResource actually wires it in and that deletion only happens once the mounted
// action is confirmed/called, not on mount alone.

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\ListRoles;

it('exposes a delete table action for each Role row', function () {
    Role::create(['name' => 'Temporary Role']);

    Livewire::test(ListRoles::class)
        ->assertTableActionExists('delete');
});

it('does not delete the Role merely by mounting the delete confirmation modal', function () {
    $role = Role::create(['name' => 'Temporary Role']);

    // assertTableActionMounted() (deprecated) takes no $record and always
    // expects context ['table' => true] with no recordKey, but
    // mountTableAction('delete', $role) always mounts with a recordKey --
    // the two can never match for a record-scoped action. assertActionMounted()
    // with a TestAction::table($role) builds the matching expected context.
    Livewire::test(ListRoles::class)
        ->mountTableAction('delete', $role)
        ->assertActionMounted(TestAction::make('delete')->table($role));

    expect(Role::find($role->id))->not->toBeNull();
});

it('deletes the Role once the delete action is confirmed', function () {
    $role = Role::create(['name' => 'Temporary Role']);

    Livewire::test(ListRoles::class)
        ->callTableAction('delete', $role);

    expect(Role::find($role->id))->toBeNull();
});
