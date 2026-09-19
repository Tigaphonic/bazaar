<?php

namespace Tigaphonic\Bazaar\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Activitylog\Models\Activity;

/**
 * Bazaar-owned Audit Trail entry, stored in `bazaar_audit_trails` (AD-18) but
 * written through spatie/laravel-activitylog's own logger. Read only through
 * AuditTrailService (AD-5) — entries are append-only (NFR4).
 */
class AuditTrail extends Activity
{
    use HasUlids;

    protected $table = 'bazaar_audit_trails';
}
