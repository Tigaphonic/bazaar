<?php

// Story 1.4 RED-PHASE scaffold. AC2: "Given seorang User yang sudah tidak aktif
// bekerja. When Staff menonaktifkan akun User tsb. Then akses User tsb dicabut
// seketika, tapi jejak historis aksinya di Audit Trail (Story 1.5) tetap tercatat
// atas nama User tsb, tidak terhapus."
//
// This is deliberately a separate file from UserServiceTest (same split as Story
// 1.3's RolePermissionPropagationTest vs RoleServiceTest): it proves the AC's "instant"
// and "not deleted, still resolvable by name" clauses as their own concern, not just
// as a side-assertion of the create/deactivate happy path.
//
// Unknown (declared in the Confidence Gate, atdd-checklist-1-4-manage-user.md):
// BazaarServiceProvider has no ->login() panel auth wired yet (per its own doc
// comment), so an actual HTTP/session-level "login blocked" assertion is not possible
// at this story's stage -- "access revoked" is proven at the data layer via
// UserService::isActive(), the same seam a future canAccessPanel() implementation
// would read from.

use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Services\UserService;

it('revokes access immediately when a User is deactivated, with no stale cached state', function () {
    Role::create(['name' => 'Supervisor Retur']);

    $user = app(UserService::class)->create([
        'name' => 'Bagas',
        'email' => 'bagas@example.com',
        'password' => 'password',
        'roles' => ['Supervisor Retur'],
    ]);

    app(UserService::class)->deactivate($user);

    // Re-fetching through a second, independent isActive() call (rather than reusing
    // any state cached on the $user instance from the deactivate() call itself) is
    // what rules out a stale in-memory flag masquerading as "instant".
    expect(app(UserService::class)->isActive($user->fresh()))->toBeFalse();
})->skip('Story 1.4 not implemented — UserService::deactivate() / isActive() do not exist yet');

it('preserves the deactivated User\'s name and email untouched, so future Audit Trail entries can still resolve to them', function () {
    Role::create(['name' => 'Supervisor Retur']);

    $user = app(UserService::class)->create([
        'name' => 'Bagas',
        'email' => 'bagas@example.com',
        'password' => 'password',
        'roles' => ['Supervisor Retur'],
    ]);

    app(UserService::class)->deactivate($user);

    $user = $user->fresh();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Bagas')
        ->and($user->email)->toBe('bagas@example.com');
})->skip('Story 1.4 not implemented — UserService::deactivate() does not exist yet');
