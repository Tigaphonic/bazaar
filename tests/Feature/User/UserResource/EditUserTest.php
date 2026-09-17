<?php

// Story 1.4 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Filament\Resources\UserResource\
// Pages\EditUser does not exist yet.
//
// Story statement: "Staff berwenang ... membuat, mengedit, dan menonaktifkan akun User
// internal serta menetapkan Role-nya" -- edit is explicitly in scope even though the
// three ACs in epics.md only spell out create (AC1/AC3) and deactivate (AC2)
// end-to-end; this mirrors Story 1.3's EditRoleTest, which existed under AC2 the same
// way. AC3's "User wajib punya minimal 1 Role" is a standing realm invariant
// (epic-1-context.md: "wajib punya minimal 1 Role"), not a create-only rule, so it is
// re-asserted here at edit time too.

use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\EditUser;
use Workbench\App\Models\User;

it('pre-fills the form with the User\'s current name, email, and Roles', function () {
    Role::create(['name' => 'Supervisor Retur']);
    Role::create(['name' => 'CS Lead']);

    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormSet([
            'name' => 'Rara',
            'email' => 'rara@example.com',
            'roles' => ['Supervisor Retur'],
        ]);
});

it('saves an updated Role assignment for an existing User', function () {
    Role::create(['name' => 'Supervisor Retur']);
    Role::create(['name' => 'CS Lead']);

    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['roles' => ['CS Lead']])
        ->call('save')
        ->assertHasNoFormErrors();

    $user = $user->fresh();
    expect($user->hasRole('CS Lead'))->toBeTrue()
        ->and($user->hasRole('Supervisor Retur'))->toBeFalse();
});

it('rejects removing every Role from an existing User via edit', function () {
    Role::create(['name' => 'Supervisor Retur']);

    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['roles' => []])
        ->call('save')
        ->assertHasFormErrors(['roles' => 'required']);
});
