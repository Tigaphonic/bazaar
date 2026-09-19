<?php

namespace Tigaphonic\Bazaar\Settings\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Tigaphonic\Bazaar\Install\Filament\Widgets\BazaarStatusWidget;
use Tigaphonic\Bazaar\Settings\Exceptions\InvalidSettingValueException;
use Tigaphonic\Bazaar\Settings\Services\SettingsService;
use Tigaphonic\Bazaar\Shell\Support\Toast;
use UnitEnum;

/**
 * List+Edit surface for the fixed Global Settings parameters (FR-28/FR-29).
 * Reads and writes only through SettingsService (AD-5); there is deliberately
 * no create/delete action.
 */
class GlobalSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    private const INTEGER_FIELDS = [
        'timeout_otp_minutes',
        'timeout_payment_minutes',
        'timeout_review_hours',
        'timeout_tokenized_page_days',
        'timeout_on_process_days',
        'timeout_auto_confirm_days',
        'refund_min_percent',
        'refund_max_percent',
    ];

    private const COURIERS = [
        'jne' => 'JNE',
        'sicepat' => 'SiCepat',
        'jnt' => 'J&T',
        'pos' => 'POS Indonesia',
        'tiki' => 'TIKI',
        'anteraja' => 'AnterAja',
    ];

    protected static string|UnitEnum|null $navigationGroup = 'Global Settings';

    protected string $view = 'bazaar::settings.global-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(SettingsService::PERMISSION);
    }

    public function getTitle(): string
    {
        return __('bazaar::settings.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('bazaar::settings.title');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->getSchema('form')->fill(app(SettingsService::class)->get()->toArray());
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [BazaarStatusWidget::class];
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return $this->getWidgets();
    }

    /**
     * @return array<int, Action>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('bazaar::settings.section_timeout'))->columns(3)->schema(
                    array_map(
                        fn (string $key) => TextInput::make($key)
                            ->label(__("bazaar::settings.{$key}"))
                            ->numeric()->integer()->minValue(1)->required(),
                        array_slice(self::INTEGER_FIELDS, 0, 6),
                    ),
                ),
                Section::make(__('bazaar::settings.section_payment'))->schema([
                    Repeater::make('payment_gateways')->label('')
                        ->addable(false)->deletable(false)->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => $state['provider'] ?? null)
                        ->schema([
                            Toggle::make('active')->label(__('bazaar::settings.gateway_active')),
                            TextInput::make('server_key')->label(__('bazaar::settings.gateway_server_key'))->password()->revealable(),
                            TextInput::make('client_key')->label(__('bazaar::settings.gateway_client_key')),
                            CheckboxList::make('enabled_methods')
                                ->label(__('bazaar::settings.gateway_enabled_methods'))
                                ->options([
                                    'bank_transfer' => __('bazaar::settings.payment_method_bank_transfer'),
                                    'credit_card' => __('bazaar::settings.payment_method_credit_card'),
                                    'gopay' => __('bazaar::settings.payment_method_gopay'),
                                    'qris' => __('bazaar::settings.payment_method_qris'),
                                    'shopeepay' => __('bazaar::settings.payment_method_shopeepay'),
                                    'cstore' => __('bazaar::settings.payment_method_cstore'),
                                ])->columns(3),
                        ]),
                ]),
                Section::make(__('bazaar::settings.section_shipping'))->schema([
                    Repeater::make('shipping_couriers')->label('')
                        ->addable(false)->deletable(false)->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => $state['provider'] ?? null)
                        ->schema([
                            Toggle::make('active')->label(__('bazaar::settings.courier_active')),
                            TextInput::make('api_key')->label(__('bazaar::settings.courier_api_key'))->password()->revealable(),
                            CheckboxList::make('enabled_couriers')
                                ->label(__('bazaar::settings.courier_enabled'))
                                ->options(self::COURIERS)->columns(3),
                        ]),
                ]),
                Section::make(__('bazaar::settings.section_warehouse'))->schema([
                    TextInput::make('default_warehouse_id')
                        ->label(__('bazaar::settings.default_warehouse_id'))
                        ->helperText(__('bazaar::settings.default_warehouse_hint'))
                        ->disabled()->dehydrated(false),
                ]),
                Section::make(__('bazaar::settings.section_store'))->columns(2)->schema([
                    TextInput::make('store_name')->label(__('bazaar::settings.store_name')),
                    TextInput::make('store_logo')->label(__('bazaar::settings.store_logo')),
                    TextInput::make('store_favicon')->label(__('bazaar::settings.store_favicon')),
                    KeyValue::make('store_social_media')
                        ->label(__('bazaar::settings.store_social_media'))
                        ->keyLabel(__('bazaar::settings.store_social_key'))
                        ->valueLabel(__('bazaar::settings.store_social_value')),
                ]),
                Section::make(__('bazaar::settings.section_refund'))->columns(2)->schema([
                    TextInput::make('refund_min_percent')
                        ->label(__('bazaar::settings.refund_min_percent'))
                        ->numeric()->integer()->minValue(0)->maxValue(100)
                        ->lt('refund_max_percent')->required(),
                    TextInput::make('refund_max_percent')
                        ->label(__('bazaar::settings.refund_max_percent'))
                        ->numeric()->integer()->minValue(0)->maxValue(100)->required(),
                ]),
                Section::make(__('bazaar::settings.section_robots'))->schema([
                    Textarea::make('robots_txt_content')
                        ->label(__('bazaar::settings.robots_txt_content'))
                        ->helperText(__('bazaar::settings.robots_txt_hint'))
                        ->rows(6),
                ]),
                Section::make(__('bazaar::settings.section_analytics'))->columns(3)->schema([
                    TextInput::make('analytics_gsc_code')->label(__('bazaar::settings.analytics_gsc_code')),
                    TextInput::make('analytics_ga4_code')->label(__('bazaar::settings.analytics_ga4_code')),
                    TextInput::make('analytics_fb_pixel_code')->label(__('bazaar::settings.analytics_fb_pixel_code')),
                ]),
                Section::make(__('bazaar::settings.section_seo'))->schema([
                    TextInput::make('seo_default_meta_title_template')->label(__('bazaar::settings.seo_default_meta_title_template')),
                    Textarea::make('seo_default_meta_description')->label(__('bazaar::settings.seo_default_meta_description'))->rows(3),
                    TextInput::make('seo_default_og_image')->label(__('bazaar::settings.seo_default_og_image')),
                ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->getSchema('form')->getState();

        foreach (self::INTEGER_FIELDS as $key) {
            if (isset($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }

        try {
            app(SettingsService::class)->update($data);
        } catch (InvalidSettingValueException $exception) {
            throw ValidationException::withMessages(["data.{$exception->key}" => $exception->getMessage()]);
        }

        Toast::success(__('bazaar::settings.saved'));
    }
}
