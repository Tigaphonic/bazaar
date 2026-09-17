<?php

// Story 1.5 RED-PHASE scaffold: Tigaphonic\Bazaar\User\Services\AuditTrailService and
// Tigaphonic\Bazaar\User\Models\AuditTrail do not exist yet.
//
// AC1: "Given aksi create/update/delete terjadi di domain manapun. When aksi tsb
// tersimpan. Then Audit Trail otomatis mencatat entri: siapa (User), apa (aksi &
// entity), kapan (timestamp), dan nilai before-after — tanpa perlu instrumentasi
// manual per domain."
//
// Implementation note: Audit Trail uses spatie/laravel-activitylog (^5.1.1+);
// AuditTrail is a Bazaar-owned subclass of Spatie\Activitylog\Models\Activity
// (see epic-1-context.md: "Models User, Role, Permission, AuditTrail (subclass
// Activity dari spatie/laravel-activitylog)"). Auto-capture is enabled via the
// LogsActivity trait on each domain Model (or the package's global events),
// without any manual call to activity() inside Service/Action classes.
//
// AD-5 binds all domains: AuditTrailService is the only read path — no direct
// AuditTrail::query() from presentation code.
//
// Scope for Story 1.5: prove auto-capture is wired using the mutating domain
// models already available from Story 1.3 (Role) and Story 1.4 (User).
// Future domain stories will add their own models to the auto-capture surface;
// this file only covers what's available now, per the ATDD decision recorded
// in atdd-checklist-1-5-audit-trail.md.

use Spatie\Activitylog\Models\Activity;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;
use Tigaphonic\Bazaar\User\Services\RoleService;
use Tigaphonic\Bazaar\User\Services\UserService;
use Workbench\App\Models\User;

// ---------------------------------------------------------------------------
// AC1 — Auto-capture: create
// ---------------------------------------------------------------------------

it('records an AuditTrail entry automatically when a Role is created, without any manual activity() call in RoleService')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('records which event=created and the correct subject_type when a Role is created')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('records an AuditTrail entry automatically when a User is created')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('records old=null for the first (created) entry of any entity, because no prior state exists')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC1 — Auto-capture: update (before-after values present)
// ---------------------------------------------------------------------------

it('records before and after values when a Role is updated, so the before snapshot holds the previous name')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('records before and after values when a User name is updated')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('only records changed attributes in the diff, not the full model on every update')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC1 — Auto-capture: delete
// ---------------------------------------------------------------------------

it('records an AuditTrail entry automatically when a Role is deleted, with event=deleted')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC1 — Causer: the acting User is recorded when a causer is set
// ---------------------------------------------------------------------------

it('records which User performed the action as causer when one is bound to the request lifecycle')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('records causer_id and causer_type on the Activity entry when the action is performed by an authenticated Staff')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC1 — No manual instrumentation: AuditTrail must not require activity()
//        calls inside Services or Actions
// ---------------------------------------------------------------------------

it('does not require any direct activity() call inside RoleService to capture events', function () {
    // Structural: RoleService source must not reference the activitylog helper
    // directly — auto-capture must be wired at the Model level (LogsActivity
    // trait), not by hand in each Service. This assertion is NOT skipped because
    // it verifies the source file of already-existing Story 1.3 code. It will
    // go red only if a developer incorrectly instruments Story 1.5 by adding
    // manual activity() calls inside the Service.
    $roleServiceSource = file_get_contents(
        __DIR__ . '/../../../src/User/Services/RoleService.php'
    );

    expect($roleServiceSource)
        ->not->toContain('activity()')
        ->not->toContain('LogActivity')
        ->not->toContain('CauserResolver');
})->skip('Story 1.5 not implemented yet — RED PHASE (guard becomes active once RoleService exists with LogsActivity wiring)');

it('does not require any direct activity() call inside UserService to capture events')
    ->skip('Story 1.5 not implemented yet — RED PHASE');
