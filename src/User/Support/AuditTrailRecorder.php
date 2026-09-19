<?php

namespace Tigaphonic\Bazaar\User\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\LaravelSettings\Models\SettingsProperty;

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

    private const MASK = '***';

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
     * Global Settings are persisted through the query builder, so no Eloquent
     * event fires for them; spatie/laravel-settings' own SavingSettings event
     * (which still carries the previous values) feeds the same trail instead.
     * Encrypted properties (gateway credentials) are masked, never logged.
     */
    public function handleSettingsSaving(\Spatie\LaravelSettings\Events\SettingsSaved $event): void
    {
        $encrypted = $event->settings::encrypted();

        $attributes = [];

        foreach ($event->settings->toArray() as $name => $value) {
            $masked = in_array($name, $encrypted, true);
            $attributes[$name] = $masked ? self::MASK : $value;
        }

        if ($attributes === []) {
            return;
        }

        $logger = activity('bazaar')
            ->event('updated')
            ->withChanges(['attributes' => $attributes]);

        // Same log string used in elqouent.* event handler above
        $logger->log('updated');
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
        // would trigger another one. Settings storage rows are excluded too:
        // Global Settings changes are recorded once, masked, through
        // handleSettingsSaving() instead of row by row.
        $excludedModels = [
            (string) config('activitylog.activity_model'),
            config('settings.settings_property_model', \Spatie\LaravelSettings\Models\SettingsProperty::class),
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
