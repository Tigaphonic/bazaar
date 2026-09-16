<?php

namespace Tigaphonic\Bazaar\Shell\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reads/writes per-Staff theme + locale preference from the Shell-owned
 * `bazaar_user_preferences` table (ULID PK, AD-18). Deliberately a plain
 * Support class over the query builder rather than an Eloquent Model — Shell
 * has no Models/Actions (AD-33: "Shell has no Models/Actions and is carved
 * out of AD-5's Service-call discipline"), keeping ArchDomainBoundaryTest a
 * no-op for this domain.
 *
 * `user_id` is stored as a string: the host app's authenticatable model's
 * primary key type is not guaranteed to be a ULID (AD-18 amendment).
 */
final class UserPreferences
{
    private const TABLE = 'bazaar_user_preferences';

    /**
     * @var array<string, ?object>
     */
    private array $memoizedRows = [];

    public function setTheme(Model $user, string $theme): void
    {
        $this->upsert($user, ['theme' => $theme]);
    }

    public function theme(Model $user): string
    {
        $row = $this->row($user);

        if ($row === null) {
            return (string) config('bazaar.shell.default_theme', 'light');
        }

        return $row->theme ?? (string) config('bazaar.shell.default_theme', 'light');
    }

    public function setLocale(Model $user, string $locale): void
    {
        $this->upsert($user, ['locale' => $locale]);
    }

    public function locale(Model $user): string
    {
        $row = $this->row($user);

        if ($row === null) {
            return (string) config('bazaar.shell.default_locale', 'en');
        }

        return $row->locale ?? (string) config('bazaar.shell.default_locale', 'en');
    }

    private function row(Model $user): ?object
    {
        $userId = $this->userId($user);

        if (array_key_exists($userId, $this->memoizedRows)) {
            return $this->memoizedRows[$userId];
        }

        return $this->memoizedRows[$userId] = DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function upsert(Model $user, array $attributes): void
    {
        $userId = $this->userId($user);
        unset($this->memoizedRows[$userId]);

        // Atomic: a separate exists()-then-insert()/update() would race two
        // concurrent first-writes for the same Staff member into the unique
        // `user_id` constraint. DB::table()->upsert() does the equivalent of
        // INSERT ... ON DUPLICATE KEY UPDATE / ON CONFLICT in one statement.
        DB::table(self::TABLE)->upsert(
            [[
                'id' => (string) Str::ulid(),
                'user_id' => $userId,
                'theme' => null,
                'locale' => null,
                ...$attributes,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id'],
            [...array_keys($attributes), 'updated_at'],
        );
    }

    private function userId(Model $user): string
    {
        return (string) $user->getKey();
    }
}
