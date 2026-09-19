<?php

namespace Tigaphonic\Bazaar\User\Services;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Tigaphonic\Bazaar\User\Contracts\HasRolesUser;
use Tigaphonic\Bazaar\User\Models\UserStatus;

/**
 * Sole entry point for mutating User state (AD-5) — presentation code
 * (UserResource + its Pages) may only call this Service, never
 * User::create()/$record->update() directly.
 *
 * The underlying Model is the host app's own authenticatable model
 * (config('bazaar.models.user') ?? config('auth.providers.users.model')),
 * same resolution as UserResource::getModel() — Bazaar never owns its own
 * Staff/users table (AD-18 amendment). Active/inactive status is stored in
 * the Bazaar-owned `bazaar_user_statuses` table via UserStatus, never as a
 * column on the host's `users` table.
 */
class UserService
{
    /**
     * @param  array{name: string, email: string, password: string, roles: string[]}  $data
     * @return Model&HasRolesUser
     */
    public function create(array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $modelClass = $this->resolveModelClass();

            /** @var Model&HasRolesUser $user */
            $user = new $modelClass;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);
            $user->save();

            $user->syncRoles($data['roles']);

            return $user;
        });
    }

    /**
     * @param  Model&HasRolesUser  $user
     * @param  array{name: string, email: string, password?: string, roles: string[]}  $data
     * @return Model&HasRolesUser
     */
    public function update(Model $user, array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data) {
            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = bcrypt($data['password']);
            }

            $user->forceFill($attributes);
            $user->save();

            $user->syncRoles($data['roles']);

            return $user;
        });
    }

    /**
     * Bootstrap access: gives the User with this email the all-permission
     * Admin Role (for the first Staff member, who has no Role yet).
     *
     * @throws RuntimeException when no User has that email
     */
    public function grantAdmin(string $email): Model
    {
        $user = $this->resolveModelClass()::query()->where('email', $email)->first()
            ?? throw new RuntimeException("No user with email {$email}.");

        $user->assignRole(app(RoleService::class)->ensureAdminRole());

        return $user;
    }

    public function deactivate(Model $user): void
    {
        UserStatus::query()->updateOrCreate(
            ['user_id' => (string) $user->getKey()],
            ['is_active' => false],
        );
    }

    public function isActive(Model $user): bool
    {
        $status = UserStatus::query()
            ->where('user_id', (string) $user->getKey())
            ->first();

        return $status === null || $status->is_active;
    }

    protected function resolveModelClass(): string
    {
        return config('bazaar.models.user')
            ?? config('auth.providers.users.model')
            ?? throw new RuntimeException("Bazaar could not resolve a User & Access model. Set config('bazaar.models.user') or config('auth.providers.users.model').");
    }
}
