<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Tigaphonic\Bazaar\User\Contracts\HasRolesUser;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;
use Tigaphonic\Bazaar\User\Services\UserService;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * `roles` isn't a column on the User table — it's a relation — so the
     * default attributesToArray()-based fill needs this to pre-fill the
     * CheckboxList with the User's currently assigned Roles.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // The host's own authenticatable model class is only known at
        // runtime (config('bazaar.models.user') / config('auth.providers.
        // users.model')) — Spatie\Permission\Traits\HasRoles is mixed into
        // it at install time (AD-16), so its `roles` relation isn't
        // statically declared on the base Model type getRecord() returns.
        /** @var Model&HasRolesUser $user */
        $user = $this->getRecord();

        $data['roles'] = $user->roles()->pluck('name')->all();

        return $data;
    }

    /**
     * Presentation code may only call a Service (AD-5) — never
     * $record->update() directly.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Model&HasRolesUser $record */
        return app(UserService::class)->update($record, $data);
    }
}
