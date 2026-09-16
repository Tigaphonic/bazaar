<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource;
use Tigaphonic\Bazaar\User\Services\RoleService;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * Presentation code may only call a Service (AD-5) — never
     * Role::create() directly.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var Role $role */
        $role = app(RoleService::class)->create($data);

        return $role;
    }
}
