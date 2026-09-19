<?php

// Story 1.5 AC3: filter per User / entity / date range, shown in a paginated
// Data Table (no infinite scroll). AuditTrailService::list() is the read gateway
// (AD-5).

use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource\Pages\ListAuditTrail;
use Tigaphonic\Bazaar\User\Models\AuditTrail;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;
use Workbench\App\Models\User;

function makeEntry(array $attributes = []): AuditTrail
{
    return AuditTrail::query()->create($attributes + [
        'log_name' => 'bazaar',
        'description' => 'created',
        'event' => 'created',
        'subject_type' => Role::class,
        'subject_id' => '1',
    ]);
}

function makeStaff(string $name): User
{
    return User::create(['name' => $name, 'email' => strtolower($name).'@example.com', 'password' => 'secret-password']);
}

it('displays the required columns in the AuditTrail table: causer, event, subject_type, subject_id, created_at, properties', function () {
    $entry = makeEntry();

    Livewire::test(ListAuditTrail::class)
        ->assertCanSeeTableRecords([$entry])
        ->assertTableColumnExists('causer.name')
        ->assertTableColumnExists('event')
        ->assertTableColumnExists('subject_type')
        ->assertTableColumnExists('subject_id')
        ->assertTableColumnExists('created_at')
        ->assertTableColumnExists('properties');
});

it('renders the before → after diff in the properties column', function () {
    $entry = makeEntry([
        'event' => 'updated',
        'attribute_changes' => ['old' => ['name' => 'Editor'], 'attributes' => ['name' => 'Publisher']],
    ]);

    Livewire::test(ListAuditTrail::class)
        ->assertTableColumnStateSet('properties', 'name: "Editor" → "Publisher"', $entry);
});

it('filters AuditTrail entries to only show those caused by the selected User when the causer_id filter is applied', function () {
    $bagas = makeStaff('Bagas');
    $rara = makeStaff('Rara');
    $byBagas = makeEntry(['causer_type' => User::class, 'causer_id' => (string) $bagas->getKey()]);
    $byRara = makeEntry(['causer_type' => User::class, 'causer_id' => (string) $rara->getKey()]);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('causer_id', (string) $bagas->getKey())
        ->assertCanSeeTableRecords([$byBagas])
        ->assertCanNotSeeTableRecords([$byRara]);
});

it('shows all entries when no causer filter is applied', function () {
    $bagas = makeStaff('Bagas');
    $byBagas = makeEntry(['causer_type' => User::class, 'causer_id' => (string) $bagas->getKey()]);
    $bySystem = makeEntry();

    Livewire::test(ListAuditTrail::class)
        ->assertCanSeeTableRecords([$byBagas, $bySystem]);
});

it('filters AuditTrail entries to only show the selected entity type when the subject_type filter is applied', function () {
    $roleEntry = makeEntry(['subject_type' => Role::class]);
    $userEntry = makeEntry(['subject_type' => User::class]);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('subject_type', Role::class)
        ->assertCanSeeTableRecords([$roleEntry])
        ->assertCanNotSeeTableRecords([$userEntry]);
});

it('does not show entries from other entity types when a subject_type filter is active', function () {
    $roleEntry = makeEntry(['subject_type' => Role::class]);
    $userEntry = makeEntry(['subject_type' => User::class]);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('subject_type', User::class)
        ->assertCanSeeTableRecords([$userEntry])
        ->assertCanNotSeeTableRecords([$roleEntry]);
});

it('shows only entries within the date range when a created_at from/until filter is applied', function () {
    $old = makeEntry(['created_at' => '2026-01-01 10:00:00']);
    $inRange = makeEntry(['created_at' => '2026-03-15 10:00:00']);
    $recent = makeEntry(['created_at' => '2026-06-01 10:00:00']);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('created_at', ['from' => '2026-03-01', 'until' => '2026-03-31'])
        ->assertCanSeeTableRecords([$inRange])
        ->assertCanNotSeeTableRecords([$old, $recent]);
});

it('excludes entries older than the from boundary when the date range filter is active', function () {
    $old = makeEntry(['created_at' => '2026-01-01 10:00:00']);
    $recent = makeEntry(['created_at' => '2026-06-01 10:00:00']);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('created_at', ['from' => '2026-03-01'])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

it('paginates AuditTrail entries, showing only the first page worth when there are more records than the page size', function () {
    foreach (range(1, 15) as $i) {
        makeEntry(['subject_id' => (string) $i]);
    }

    $component = Livewire::test(ListAuditTrail::class)->set('tableRecordsPerPage', 10);

    expect($component->instance()->getTableRecords())->toHaveCount(10)
        ->and($component->instance()->getTableRecords()->hasPages())->toBeTrue();
});

it('does not load all records at once — the count of visible rows equals the page size, not the total record count', function () {
    foreach (range(1, 30) as $i) {
        makeEntry(['subject_id' => (string) $i]);
    }

    $component = Livewire::test(ListAuditTrail::class)->set('tableRecordsPerPage', 10);

    expect($component->instance()->getTableRecords())->toHaveCount(10);
    expect(AuditTrail::query()->count())->toBe(30);
});

it('AuditTrailService::list() returns a collection of Activity entries', function () {
    makeEntry();
    makeEntry();

    $entries = app(AuditTrailService::class)->list();

    expect($entries)->toHaveCount(2)
        ->and($entries->first())->toBeInstanceOf(Activity::class);
});

it('AuditTrailService::list() accepts a causerId filter and returns only entries from that causer', function () {
    $bagas = makeStaff('Bagas');
    $rara = makeStaff('Rara');
    makeEntry(['causer_type' => User::class, 'causer_id' => (string) $bagas->getKey()]);
    makeEntry(['causer_type' => User::class, 'causer_id' => (string) $rara->getKey()]);

    $entries = app(AuditTrailService::class)->list(causerId: $bagas->getKey());

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->causer_id)->toBe((string) $bagas->getKey());
});

it('AuditTrailService::list() accepts a subjectType filter and returns only entries for that entity', function () {
    makeEntry(['subject_type' => Role::class]);
    makeEntry(['subject_type' => User::class]);

    $entries = app(AuditTrailService::class)->list(subjectType: Role::class);

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->subject_type)->toBe(Role::class);
});

it('AuditTrailService::list() accepts a date range filter and returns only entries within the window', function () {
    makeEntry(['created_at' => '2026-01-01 10:00:00']);
    makeEntry(['created_at' => '2026-03-15 10:00:00']);

    $entries = app(AuditTrailService::class)->list(
        from: Carbon::parse('2026-03-01'),
        until: Carbon::parse('2026-03-31')->endOfDay(),
    );

    expect($entries)->toHaveCount(1);
});

it('includes entries on the until day itself, even late in the day', function () {
    $lateOnUntilDay = makeEntry(['created_at' => '2026-03-31 23:00:00']);
    $after = makeEntry(['created_at' => '2026-04-01 00:30:00']);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('created_at', ['until' => '2026-03-31'])
        ->assertCanSeeTableRecords([$lateOnUntilDay])
        ->assertCanNotSeeTableRecords([$after]);
});

it('includes entries at the very start of the from day', function () {
    $startOfFromDay = makeEntry(['created_at' => '2026-03-01 00:00:00']);
    $before = makeEntry(['created_at' => '2026-02-28 23:59:00']);

    Livewire::test(ListAuditTrail::class)
        ->filterTable('created_at', ['from' => '2026-03-01'])
        ->assertCanSeeTableRecords([$startOfFromDay])
        ->assertCanNotSeeTableRecords([$before]);
});

it('offers the acting Users and the entity types found in the log as filter options', function () {
    $bagas = makeStaff('Bagas');
    makeEntry(['causer_type' => User::class, 'causer_id' => (string) $bagas->getKey(), 'subject_type' => Role::class]);

    $component = Livewire::test(ListAuditTrail::class);
    $filters = $component->instance()->getTable()->getFilters();

    expect($filters['causer_id']->getOptions())->toBe([(string) $bagas->getKey() => 'Bagas'])
        ->and($filters['subject_type']->getOptions())->toBe([Role::class => 'Role', User::class => 'User']); // makeStaff() itself is audited as a User create
});

it('orders entries created in the same second newest-first by id', function () {
    $first = makeEntry(['created_at' => '2026-03-01 10:00:00']);
    usleep(2000);
    $second = makeEntry(['created_at' => '2026-03-01 10:00:00']);

    $ids = app(AuditTrailService::class)->list()->pluck('id')->all();

    expect($ids)->toBe([$second->id, $first->id]);
});
