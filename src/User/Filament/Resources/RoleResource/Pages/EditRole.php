<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource;
use Tigaphonic\Bazaar\User\Services\RoleService;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * `permissions` isn't a column on the Role table — it's a relation —
     * so the default attributesToArray()-based fill needs this to pre-fill
     * the CheckboxList with the Role's currently checked permissions.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        $data['permissions'] = $role->permissions->pluck('name')->all();

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
        /** @var Role $record */
        return app(RoleService::class)->update($record, $data);
    }
}
