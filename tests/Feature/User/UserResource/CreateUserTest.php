<?php

// Story 1.4 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Filament\Resources\UserResource\
// Pages\CreateUser does not exist yet.
//
// AC1: "Given Staff berwenang membuka User & Access -> Users. When Staff membuat User
// baru dan menetapkan minimal 1 Role (dari Story 1.3). Then User tersimpan sebagai
// akun internal, terpisah total dari skema Customer."
// AC3: "Given Staff mencoba membuat User tanpa Role. When Staff menyimpan form. Then
// sistem menolak — User wajib punya minimal 1 Role."

use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages\CreateUser;
use Workbench\App\Models\User;

it('creates a User with assigned Roles via the dashboard form', function () {
    Role::create(['name' => 'Supervisor Retur']);
    Role::create(['name' => 'CS Lead']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Rara',
            'email' => 'rara@example.com',
            'password' => 'password',
            'roles' => ['Supervisor Retur'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'rara@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('Supervisor Retur'))->toBeTrue()
        ->and($user->hasRole('CS Lead'))->toBeFalse();
});

it('rejects creating a User with no Role selected', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Rara',
            'email' => 'rara2@example.com',
            'password' => 'password',
            'roles' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['roles' => 'required']);
});

it('rejects a duplicate email', function () {
    User::create(['name' => 'Existing', 'email' => 'rara3@example.com', 'password' => bcrypt('password')]);
    Role::create(['name' => 'Supervisor Retur']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Rara',
            'email' => 'rara3@example.com',
            'password' => 'password',
            'roles' => ['Supervisor Retur'],
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});
