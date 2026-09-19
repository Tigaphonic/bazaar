<?php

namespace Tigaphonic\Bazaar\User\Services;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Tigaphonic\Bazaar\User\Models\AuditTrail;

/**
 * Sole read path into the Audit Trail (AD-5). Deliberately exposes no
 * mutation of any kind — Audit Trail is append-only (NFR4); entries are
 * written only by AuditTrailRecorder from model events.
 */
class AuditTrailService
{
    /**
     * @return Collection<int, AuditTrail>
     */
    public function list(
        string|int|null $causerId = null,
        ?string $subjectType = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $until = null,
    ): Collection {
        return AuditTrail::query()
            ->when($causerId !== null, fn ($q) => $q->where('causer_id', (string) $causerId))
            ->when($subjectType !== null, fn ($q) => $q->where('subject_type', $subjectType))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($until, fn ($q) => $q->where('created_at', '<=', $until))
            ->latest()
            ->latest('id')
            ->get();
    }
}
