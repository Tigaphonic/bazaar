<?php

// Story 1.4 RED-PHASE scaffold. AC1's Given clause: "Staff berwenang membuka User &
// Access -> Users" -- EXPERIENCE.md §Navigation: "Users | Sidebar -> User & Access ->
// Users | Create/edit/deactivate internal User accounts, assign Role(s) (FR-18)".
//
// UserResource + its ListUsers page already exist (Story 1.1 skeleton) with `name`
// and `email` columns only -- that much already passes today. The genuinely red part
// of this scaffold is the status column: nothing surfaces whether a User is active or
// deactivated yet, which Staff need to see at a glance in the list (EXPERIENCE.md's
// "Create/edit/deactivate" surface implies deactivated accounts stay visible in the
// list, not hidden, so their state must be legible).

use Livewire\Livewire;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\ListUsers;
use Tigaphonic\Bazaar\User\Services\UserService;
use Workbench\App\Models\User;

it('lists every User with name, email, and status columns', function () {
    $user = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('is_active');
});

it('renders the is_active column true for active Users and false for deactivated Users', function () {
    $activeUser = User::create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => bcrypt('password')]);
    $deactivatedUser = User::create(['name' => 'Budi', 'email' => 'budi@example.com', 'password' => bcrypt('password')]);

    app(UserService::class)->deactivate($deactivatedUser);

    Livewire::test(ListUsers::class)
        ->assertTableColumnStateSet('is_active', true, $activeUser)
        ->assertTableColumnStateSet('is_active', false, $deactivatedUser);
});
