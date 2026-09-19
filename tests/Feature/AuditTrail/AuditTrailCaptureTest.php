<?php

// Story 1.5 AC1: "Given aksi create/update/delete terjadi di domain manapun. When
// aksi tsb tersimpan. Then Audit Trail otomatis mencatat entri: siapa (User), apa
// (aksi & entity), kapan (timestamp), dan nilai before-after — tanpa perlu
// instrumentasi manual per domain."
//
// Capture is wired by a global Eloquent-event listener (AuditTrailRecorder), so
// these tests drive the existing Story 1.3/1.4 Services and assert the entries
// appear without any activity() call inside those Services.

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Models\AuditTrail;
use Tigaphonic\Bazaar\User\Services\RoleService;
use Tigaphonic\Bazaar\User\Services\UserService;
use Workbench\App\Models\User;

function auditEntriesFor(string $subjectType, string $event): Collection
{
    return AuditTrail::query()
        ->where('subject_type', $subjectType)
        ->where('event', $event)
        ->get();
}

it('records an AuditTrail entry automatically when a Role is created, without any manual activity() call in RoleService', function () {
    $role = app(RoleService::class)->create(['name' => 'Editor']);

    $entries = auditEntriesFor(Role::class, 'created');

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->subject_id)->toBe((string) $role->getKey());
});

it('records which event=created and the correct subject_type when a Role is created', function () {
    app(RoleService::class)->create(['name' => 'Editor']);

    $entry = AuditTrail::query()->latest()->first();

    expect($entry->event)->toBe('created')
        ->and($entry->subject_type)->toBe(Role::class)
        ->and($entry->log_name)->toBe('bazaar')
        ->and($entry->created_at)->not->toBeNull();
});

it('records an AuditTrail entry automatically when a User is created', function () {
    app(RoleService::class)->create(['name' => 'Editor']);

    $user = app(UserService::class)->create([
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'password' => 'secret-password',
        'roles' => ['Editor'],
    ]);

    $entries = auditEntriesFor(User::class, 'created');

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->subject_id)->toBe((string) $user->getKey())
        ->and($entries->first()->attribute_changes->get('attributes'))->toMatchArray(['name' => 'Rara', 'email' => 'rara@example.com']);
});

it('never records password or other hidden attributes of a created User', function () {
    app(RoleService::class)->create(['name' => 'Editor']);

    app(UserService::class)->create([
        'name' => 'Rara',
        'email' => 'rara@example.com',
        'password' => 'secret-password',
        'roles' => ['Editor'],
    ]);

    $attributes = auditEntriesFor(User::class, 'created')->first()->attribute_changes->get('attributes');

    expect($attributes)->not->toHaveKey('password')
        ->and($attributes)->not->toHaveKey('remember_token');
});

it('records old=null for the first (created) entry of any entity, because no prior state exists', function () {
    app(RoleService::class)->create(['name' => 'Editor']);

    $changes = auditEntriesFor(Role::class, 'created')->first()->attribute_changes;

    expect($changes->has('old'))->toBeTrue()
        ->and($changes->get('old'))->toBeNull();
});

it('records before and after values when a Role is updated, so the before snapshot holds the previous name', function () {
    $service = app(RoleService::class);
    $role = $service->create(['name' => 'Editor']);

    $service->update($role, ['name' => 'Publisher']);

    $changes = auditEntriesFor(Role::class, 'updated')->first()->attribute_changes;

    expect($changes->get('old'))->toBe(['name' => 'Editor'])
        ->and($changes->get('attributes'))->toBe(['name' => 'Publisher']);
});

it('records before and after values when a User name is updated', function () {
    app(RoleService::class)->create(['name' => 'Editor']);
    $service = app(UserService::class);
    $user = $service->create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => 'secret-password', 'roles' => ['Editor']]);

    $service->update($user, ['name' => 'Rara Putri', 'email' => 'rara@example.com', 'roles' => ['Editor']]);

    $changes = auditEntriesFor(User::class, 'updated')->first()->attribute_changes;

    expect($changes->get('old'))->toBe(['name' => 'Rara'])
        ->and($changes->get('attributes'))->toBe(['name' => 'Rara Putri']);
});

it('only records changed attributes in the diff, not the full model on every update', function () {
    app(RoleService::class)->create(['name' => 'Editor']);
    $service = app(UserService::class);
    $user = $service->create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => 'secret-password', 'roles' => ['Editor']]);

    $service->update($user, ['name' => 'Rara Putri', 'email' => 'rara@example.com', 'roles' => ['Editor']]);

    $changes = auditEntriesFor(User::class, 'updated')->first()->attribute_changes;

    expect(array_keys($changes->get('attributes')))->toBe(['name'])
        ->and($changes->get('attributes'))->not->toHaveKey('updated_at');
});

it('records no entry for a save that changes nothing', function () {
    $service = app(RoleService::class);
    $role = $service->create(['name' => 'Editor']);

    $service->update($role, ['name' => 'Editor']);

    expect(auditEntriesFor(Role::class, 'updated'))->toHaveCount(0);
});

it('records an AuditTrail entry automatically when a Role is deleted, with event=deleted', function () {
    $service = app(RoleService::class);
    $role = $service->create(['name' => 'Editor']);

    $service->delete($role);

    $entry = auditEntriesFor(Role::class, 'deleted')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->attribute_changes->get('old'))->toMatchArray(['name' => 'Editor']);
});

it('records which User performed the action as causer when one is bound to the request lifecycle', function () {
    $staff = User::create(['name' => 'Bagas', 'email' => 'bagas@example.com', 'password' => 'secret-password']);

    $this->actingAs($staff);
    app(RoleService::class)->create(['name' => 'Editor']);

    $entry = auditEntriesFor(Role::class, 'created')->first();

    expect($entry->causer)->not->toBeNull()
        ->and($entry->causer->is($staff))->toBeTrue();
});

it('records causer_id and causer_type on the Activity entry when the action is performed by an authenticated Staff', function () {
    $staff = User::create(['name' => 'Bagas', 'email' => 'bagas@example.com', 'password' => 'secret-password']);

    $this->actingAs($staff);
    app(RoleService::class)->create(['name' => 'Editor']);

    $entry = auditEntriesFor(Role::class, 'created')->first();

    expect($entry->causer_id)->toBe((string) $staff->getKey())
        ->and($entry->causer_type)->toBe(User::class);
});

it('records a null causer when the action happens outside an authenticated request', function () {
    app(RoleService::class)->create(['name' => 'Editor']);

    $entry = auditEntriesFor(Role::class, 'created')->first();

    expect($entry->causer_id)->toBeNull();
});

it('does not audit the Audit Trail itself', function () {
    app(RoleService::class)->create(['name' => 'Editor']);

    expect(AuditTrail::query()->where('subject_type', AuditTrail::class)->count())->toBe(0);
});

it('does not require any direct activity() call inside RoleService to capture events', function () {
    $roleServiceSource = file_get_contents(__DIR__.'/../../../src/User/Services/RoleService.php');

    expect($roleServiceSource)
        ->not->toContain('activity()')
        ->not->toContain('LogActivity')
        ->not->toContain('CauserResolver');
});

it('does not require any direct activity() call inside UserService to capture events', function () {
    $userServiceSource = file_get_contents(__DIR__.'/../../../src/User/Services/UserService.php');

    expect($userServiceSource)
        ->not->toContain('activity()')
        ->not->toContain('LogActivity')
        ->not->toContain('CauserResolver');
});

it('never records a hidden attribute when it changes in an update', function () {
    app(RoleService::class)->create(['name' => 'Editor']);
    $service = app(UserService::class);
    $user = $service->create(['name' => 'Rara', 'email' => 'rara@example.com', 'password' => 'secret-password', 'roles' => ['Editor']]);

    $service->update($user, ['name' => 'Rara', 'email' => 'rara@example.com', 'password' => 'new-password', 'roles' => ['Editor']]);

    $changes = auditEntriesFor(User::class, 'updated')->first()?->attribute_changes;

    expect($changes)->toBeNull();
});

it('skips models listed in bazaar.audit.exclude_models, including their subclasses', function () {
    config(['bazaar.audit.exclude_models' => [Role::class]]);

    $subclass = new class extends Role
    {
        protected $table = 'roles';
    };

    app(RoleService::class)->create(['name' => 'Editor']);
    $subclass->forceFill(['name' => 'Sub', 'guard_name' => 'web'])->save();

    expect(auditEntriesFor(Role::class, 'created'))->toHaveCount(0)
        ->and(AuditTrail::query()->count())->toBe(0);
});
