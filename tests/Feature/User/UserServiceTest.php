<?php

// Story 1.4 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Services\UserService does not
// exist yet. AC1: "Staff berwenang membuka User & Access -> Users. Staff membuat User
// baru dan menetapkan minimal 1 Role (dari Story 1.3). User tersimpan sebagai akun
// internal, terpisah total dari skema Customer." AC2: "Staff menonaktifkan akun User
// tsb. Akses User tsb dicabut seketika, tapi jejak historis aksinya di Audit Trail
// (Story 1.5) tetap tercatat atas nama User tsb, tidak terhapus."
//
// AD-5 binds all domains: presentation code (Filament Resource) may only call a
// Service, never a Model directly for a mutation -- UserService is that Service. The
// underlying Model is the host app's own authenticatable model (config('bazaar.
// models.user') ?? config('auth.providers.users.model')), same resolution as
// UserResource::getModel() -- Bazaar never owns its own Staff/users table (AD-18
// amendment), so UserService never creates a Bazaar-owned "User" Model of its own.
// "Terpisah total dari skema Customer" is an AD-20 guarantee (separate realms, no
// shared table) already true by construction of using the host's own model class --
// Customer itself does not exist as a domain yet (Epic 4, backlog), so there is no
// customers table to assert against here.

use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Services\UserService;
use Workbench\App\Models\User;

it('creates a User with the given Roles, stored on the host\'s own authenticatable model', function () {
    $supervisor = Role::create(['name' => 'Supervisor Retur']);

    $user = app(UserService::class)->create([
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'password' => 'password',
        'roles' => ['Supervisor Retur'],
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Rara')
        ->and($user->fresh()->hasRole($supervisor))->toBeTrue();
})->skip('Story 1.4 not implemented — Tigaphonic\Bazaar\User\Services\UserService does not exist yet');

it('replaces a User\'s Role assignment on update rather than appending to it', function () {
    Role::create(['name' => 'Supervisor Retur']);
    Role::create(['name' => 'CS Lead']);

    $user = app(UserService::class)->create([
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'password' => 'password',
        'roles' => ['Supervisor Retur'],
    ]);

    app(UserService::class)->update($user, [
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'roles' => ['CS Lead'],
    ]);

    $user = $user->fresh();
    expect($user->hasRole('CS Lead'))->toBeTrue()
        ->and($user->hasRole('Supervisor Retur'))->toBeFalse();
})->skip('Story 1.4 not implemented — UserService::update() does not exist yet');

it('deactivates a User, revoking access immediately', function () {
    Role::create(['name' => 'Supervisor Retur']);

    $user = app(UserService::class)->create([
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'password' => 'password',
        'roles' => ['Supervisor Retur'],
    ]);

    expect(app(UserService::class)->isActive($user))->toBeTrue();

    app(UserService::class)->deactivate($user);

    expect(app(UserService::class)->isActive($user))->toBeFalse();
})->skip('Story 1.4 not implemented — UserService::deactivate() / isActive() do not exist yet');

it('does not delete the User record when deactivating', function () {
    Role::create(['name' => 'Supervisor Retur']);

    $user = app(UserService::class)->create([
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'password' => 'password',
        'roles' => ['Supervisor Retur'],
    ]);

    app(UserService::class)->deactivate($user);

    expect(User::find($user->id))->not->toBeNull();
})->skip('Story 1.4 not implemented — UserService::deactivate() does not exist yet');
