<?php

namespace Tigaphonic\Bazaar\User\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\ActivityLogger;

/**
 * Global Eloquent-event listener that records every create/update/delete of
 * any model into the Audit Trail (FR-20), so no Service or Action ever needs
 * a manual activity() call. Bound to `eloquent.{event}: *` by
 * BazaarServiceProvider — new domain models are captured automatically.
 */
class AuditTrailRecorder
{
    /** @var string[] */
    private const IGNORED_ON_UPDATE = ['updated_at'];

    /**
     * @param  array<int, mixed>  $payload
     */
    public function handle(string $eventName, array $payload): void
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model || $this->isExcluded($model)) {
            return;
        }

        $event = Str::of($eventName)->after('eloquent.')->before(':')->toString();

        $changes = match ($event) {
            'created' => ['attributes' => $this->visible($model, $model->getAttributes()), 'old' => null],
            'updated' => $this->updatedChanges($model),
            'deleted' => ['old' => $this->visible($model, $model->getAttributes())],
            default => [],
        };

        if ($changes === []) {
            return;
        }

        app(ActivityLogger::class)
            ->useLog('bazaar')
            ->event($event)
            ->performedOn($model)
            ->withChanges($changes)
            ->log($event);
    }

    /**
     * @return array{attributes: array<string, mixed>, old: array<string, mixed>}|array{}
     */
    private function updatedChanges(Model $model): array
    {
        $changed = $this->visible($model, array_diff_key($model->getChanges(), array_flip(self::IGNORED_ON_UPDATE)));

        if ($changed === []) {
            return [];
        }

        $old = [];
        foreach (array_keys($changed) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        return ['attributes' => $changed, 'old' => $old];
    }

    /**
     * Drops attributes the model itself hides (password, remember_token, ...)
     * so credentials never reach a log Staff can read.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function visible(Model $model, array $attributes): array
    {
        return array_diff_key($attributes, array_flip($model->getHidden()));
    }

    private function isExcluded(Model $model): bool
    {
        // The Audit Trail's own rows must never be audited, or every entry
        // would trigger another one.
        $excludedModels = [
            (string) config('activitylog.activity_model'),
            ...(array) config('bazaar.audit.exclude_models', []),
        ];

        foreach ($excludedModels as $excluded) {
            if ($model instanceof $excluded) {
                return true;
            }
        }

        return false;
    }
}
