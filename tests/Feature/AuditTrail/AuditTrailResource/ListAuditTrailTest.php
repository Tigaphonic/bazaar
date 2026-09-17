<?php

// Story 1.5 RED-PHASE scaffold — AC3 (filter & pagination):
//
// "Given Staff ingin menyelidiki insiden tertentu. When Staff memfilter Audit
// Trail per User, per entity, atau rentang waktu. Then hasil terfilter sesuai
// kriteria ditampilkan dalam Data Table dengan pagination standar."
//
// Also covers the required Data Table columns from AC1's data model:
// siapa (User/causer name), apa (event + subject_type + subject_id),
// kapan (timestamp), perubahan before-after — these must be legible in the
// list surface (EXPERIENCE.md UX-DR3: klik baris buka detail; no infinite
// scroll — pagination only).
//
// AuditTrailService::list() is the read gateway (AD-5): presentation code
// calls the Service, never Activity::query() directly.

use Livewire\Livewire;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource\Pages\ListAuditTrail;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;

// ---------------------------------------------------------------------------
// Data Table columns — AC1 data model surfaced in AC3 list surface
// ---------------------------------------------------------------------------

it('displays the required columns in the AuditTrail table: causer, event, subject_type, subject_id, created_at, properties')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC3 — Filter per User (causer)
// ---------------------------------------------------------------------------

it('filters AuditTrail entries to only show those caused by the selected User when the causer_id filter is applied')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('shows all entries when no causer filter is applied')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC3 — Filter per entity type (subject_type)
// ---------------------------------------------------------------------------

it('filters AuditTrail entries to only show the selected entity type when the subject_type filter is applied')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('does not show entries from other entity types when a subject_type filter is active')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC3 — Filter per date/time range
// ---------------------------------------------------------------------------

it('shows only entries within the date range when a created_at from/until filter is applied')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('excludes entries older than the from boundary when the date range filter is active')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AC3 — Pagination (no infinite scroll — UX-DR3 + UX-DR15)
// ---------------------------------------------------------------------------

it('paginates AuditTrail entries, showing only the first page worth when there are more records than the page size')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('does not load all records at once — the count of visible rows equals the page size, not the total record count')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

// ---------------------------------------------------------------------------
// AuditTrailService::list() — the read gateway (AD-5)
// ---------------------------------------------------------------------------

it('AuditTrailService::list() returns a collection of Activity entries')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('AuditTrailService::list() accepts a causerId filter and returns only entries from that causer')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('AuditTrailService::list() accepts a subjectType filter and returns only entries for that entity')
    ->skip('Story 1.5 not implemented yet — RED PHASE');

it('AuditTrailService::list() accepts a date range filter and returns only entries within the window')
    ->skip('Story 1.5 not implemented yet — RED PHASE');
