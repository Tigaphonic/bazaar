<?php

namespace Tigaphonic\Bazaar\Settings\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use ReflectionNamedType;
use ReflectionProperty;
use Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException;
use Tigaphonic\Bazaar\Settings\Support\BazaarSettings;

/**
 * Sole entry point for reading and mutating Global Settings (AD-5). The
 * parameter set is fixed: unknown keys are ignored, never stored.
 *
 * A logged-in Staff member needs the `manage-settings` permission; calls with
 * no authenticated user (console, queued job, portal guest) pass through.
 */
class SettingsService
{
    public const PERMISSION = 'manage-settings';

    /**
     * @throws AuthorizationException
     */
    public function get(): BazaarSettings
    {
        $this->authorize();

        return app(BazaarSettings::class);
    }

    /**
     * @param  array<string, mixed>  $values
     *
     * @throws AuthorizationException
     * @throws InvalidSettingValueException
     */
    public function update(array $values): BazaarSettings
    {
        $this->authorize();

        $values = array_intersect_key($values, get_class_vars(BazaarSettings::class));

        foreach ($values as $key => $value) {
            $this->assertValidType($key, $value);
        }

        $settings = app(BazaarSettings::class);

        $this->assertRefundRange($settings, $values);

        // The transaction lets the audit entry written while saving roll back
        // together with a failed write.
        DB::transaction(fn () => $settings->fill($values)->save());

        return $settings;
    }

    private function authorize(): void
    {
        $user = auth()->user();

        if ($user !== null && ! $user->can(self::PERMISSION)) {
            throw new AuthorizationException;
        }
    }

    private function assertValidType(string $key, mixed $value): void
    {
        /** @var ReflectionNamedType $type */
        $type = (new ReflectionProperty(BazaarSettings::class, $key))->getType();

        if ($value === null) {
            if (! $type->allowsNull()) {
                throw new InvalidSettingValueException($key, "{$key} must not be null.");
            }

            return;
        }

        $valid = match ($type->getName()) {
            'int' => is_int($value),
            'string' => is_string($value),
            'array' => is_array($value),
            default => false,
        };

        if (! $valid) {
            throw new InvalidSettingValueException($key, "{$key} must be of type {$type->getName()}.");
        }

        if (str_starts_with($key, 'timeout_') && $value < 1) {
            throw new InvalidSettingValueException($key, "{$key} must be at least 1.");
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function assertRefundRange(BazaarSettings $settings, array $values): void
    {
        if (! array_key_exists('refund_min_percent', $values) && ! array_key_exists('refund_max_percent', $values)) {
            return;
        }

        $min = $values['refund_min_percent'] ?? $settings->refund_min_percent;
        $max = $values['refund_max_percent'] ?? $settings->refund_max_percent;

        foreach (['refund_min_percent' => $min, 'refund_max_percent' => $max] as $key => $percent) {
            if ($percent < 0 || $percent > 100) {
                throw new InvalidSettingValueException($key, "{$key} must be between 0 and 100.");
            }
        }

        if ($min >= $max) {
            throw new InvalidSettingValueException('refund_min_percent', 'refund_min_percent must be less than refund_max_percent.');
        }
    }
}
