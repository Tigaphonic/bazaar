<?php

// Story 1.4 RED-PHASE scaffold. AC2: "Given seorang User yang sudah tidak aktif
// bekerja. When Staff menonaktifkan akun User tsb. Then akses User tsb dicabut
// seketika...". EXPERIENCE.md line 154/223: "Deactivate User" is one of the few
// actions that gets a confirmation modal (hard-to-reverse action) -- same default-
// confirmation pattern as Story 1.3's RoleResource DeleteAction
// (vendor/filament/actions/src/DeleteAction.php), but this is a bespoke
// `Filament\Actions\Action::make('deactivate')` (not DeleteAction) since deactivating
// never deletes the underlying record (AC2's "tidak terhapus" clause) --
// ->requiresConfirmation() must be added explicitly here, unlike DeleteAction's own
// default.

use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\ListUsers;
use Tigaphonic\Bazaar\User\Services\UserService;
use Workbench\App\Models\User;

it('exposes a deactivate table action for each active User row', function () {
    Role::create(['name' => 'Supervisor Retur']);
    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');

    Livewire::test(ListUsers::class)
        ->assertTableActionExists('deactivate');
})->skip('Story 1.4 not implemented — UserResource has no deactivate table action yet');

it('does not deactivate the User merely by mounting the confirmation modal', function () {
    Role::create(['name' => 'Supervisor Retur']);
    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');

    Livewire::test(ListUsers::class)
        ->mountTableAction('deactivate', $user)
        ->assertTableActionMounted('deactivate');

    expect(app(UserService::class)->isActive($user->fresh()))->toBeTrue();
})->skip('Story 1.4 not implemented — ListUsers deactivate action does not exist yet');

it('deactivates the User once the action is confirmed', function () {
    Role::create(['name' => 'Supervisor Retur']);
    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');

    Livewire::test(ListUsers::class)
        ->callTableAction('deactivate', $user);

    expect(app(UserService::class)->isActive($user->fresh()))->toBeFalse();
})->skip('Story 1.4 not implemented — ListUsers deactivate action does not exist yet');

it('hides the deactivate action once the User is already deactivated', function () {
    Role::create(['name' => 'Supervisor Retur']);
    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $user->assignRole('Supervisor Retur');
    app(UserService::class)->deactivate($user);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('deactivate', $user->fresh());
})->skip('Story 1.4 not implemented — deactivate action has no visibility guard yet');
