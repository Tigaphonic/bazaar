<?php

namespace Tigaphonic\Bazaar\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Bazaar-owned active/inactive status for the host app's own authenticatable
 * User model (Story 1.4, AD-18 amendment) — never a column on the host's
 * `users` table. Used only by UserService (AD-5) — never accessed directly
 * from presentation code.
 *
 * @property string $id
 * @property string $user_id
 * @property bool $is_active
 */
class UserStatus extends Model
{
    use HasUlids;

    protected $table = 'bazaar_user_statuses';

    protected $fillable = ['user_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
