<?php

namespace Tigaphonic\Bazaar\User\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource\Pages\ListAuditTrail;
use UnitEnum;

/**
 * Read-only, filterable log of every mutation (FR-20). List-only by design:
 * no create/edit pages and no record or bulk actions — the read-only floor is
 * absolute for every User, admin included (NFR4).
 */
class AuditTrailResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'User & Access';

    protected static ?string $modelLabel = 'Audit Trail';

    protected static ?string $pluralModelLabel = 'Audit Trail';

    public static function getModel(): string
    {
        // Resolved from config, not imported: AD-5 lets only a domain's own
        // Services/Actions reference its Models directly.
        return (string) config('activitylog.activity_model');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('created_at')->orderByDesc('id'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Who')
                    ->placeholder('System'),
                TextColumn::make('event')
                    ->badge(),
                TextColumn::make('subject_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : null),
                TextColumn::make('subject_id')
                    ->label('Entity ID'),
                TextColumn::make('properties')
                    ->label('Changes (before → after)')
                    ->getStateUsing(fn (Activity $record) => static::describeChanges($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(fn () => static::causerOptions())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $parts = explode(':', $data['value'], 2);
                            if (count($parts) === 2) {
                                $query->where('causer_type', $parts[0])->where('causer_id', $parts[1]);
                            }
                        }
                        return $query;
                    }),
                SelectFilter::make('subject_type')
                    ->label('Entity')
                    ->options(fn () => static::subjectTypeOptions())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->where('subject_type', $data['value'])
                        : $query),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($data['from'])->startOfDay()))
                        ->when(filled($data['until'] ?? null), fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($data['until'])->endOfDay()))),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditTrail::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function causerOptions(): array
    {
        $causers = static::getModel()::query()
            ->whereNotNull('causer_id')
            ->select(['causer_type', 'causer_id'])
            ->distinct()
            ->get();

        $causers->load('causer');

        return $causers->mapWithKeys(fn (Model $entry) => [
            $entry->getAttribute('causer_type') . ':' . $entry->getAttribute('causer_id') => $entry->causer?->getAttribute('name') ?? $entry->getAttribute('causer_id')
        ])->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function subjectTypeOptions(): array
    {
        return static::getModel()::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $type) => [$type => class_basename($type)])
            ->all();
    }

    protected static function describeChanges(Activity $record): string
    {
        $changes = $record->attribute_changes;
        $old = (array) ($changes?->get('old') ?? []);
        $new = (array) ($changes?->get('attributes') ?? []);

        return collect(array_keys($old + $new))
            ->map(fn (string $key) => sprintf(
                '%s: %s → %s',
                $key,
                json_encode($old[$key] ?? null),
                json_encode($new[$key] ?? null),
            ))
            ->implode('; ');
    }
}
