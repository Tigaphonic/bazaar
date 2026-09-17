<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\CreateRole;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\EditRole;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource\Pages\ListRoles;
use Tigaphonic\Bazaar\User\Services\RoleService;
use UnitEnum;

/**
 * Lets Staff create/edit/delete Role and check granular Permission for it,
 * no deploy required (FR-19). Backed by Spatie\Permission\Models\Role — see
 * epic-1-context.md ("dibangun di atas spatie/laravel-permission") and
 * AD-18 (dependency-owned tables/models keep their native shape).
 *
 * Presentation code here only ever calls RoleService (AD-5) — never
 * Role::create()/$record->update()/$record->delete() directly.
 */
class RoleResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'User & Access';

    /**
     * Lets Filament's native global search (active by default,
     * HasGlobalSearch.php:31) actually return results for Role records --
     * without this, the search provider has no title field to match against
     * or display.
     */
    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return Role::class;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->trim()
                ->unique(table: fn () => config('permission.table_names.roles', 'roles')),
            CheckboxList::make('permissions')
                ->options(fn () => Permission::pluck('name', 'name'))
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (Role $record) { app(RoleService::class)->delete($record); return true; }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
