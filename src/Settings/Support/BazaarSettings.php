<?php

namespace Tigaphonic\Bazaar\Settings\Support;

use Spatie\LaravelSettings\Settings;

/**
 * The fixed parameter set of Global Settings (FR-28/FR-29). Staff edit values
 * only; the property list itself is never extended at runtime.
 */
class BazaarSettings extends Settings
{
    public int $timeout_otp_minutes = 5;

    public int $timeout_payment_minutes = 1440;

    public int $timeout_review_hours = 72;

    public int $timeout_tokenized_page_days = 5;

    public int $timeout_on_process_days = 3;

    public int $timeout_auto_confirm_days = 7;

    public array $payment_gateways = [
        ['provider' => 'midtrans', 'active' => false, 'server_key' => null, 'client_key' => null, 'enabled_methods' => []],
    ];

    public array $shipping_couriers = [
        ['provider' => 'rajaongkir', 'active' => false, 'api_key' => null, 'enabled_couriers' => []],
    ];

    public ?string $default_warehouse_id = null;

    public ?string $store_name = null;

    public ?string $store_logo = null;

    public ?string $store_favicon = null;

    public array $store_social_media = [];

    public int $refund_min_percent = 0;

    public int $refund_max_percent = 100;

    public ?string $robots_txt_content = null;

    public ?string $analytics_gsc_code = null;

    public ?string $analytics_ga4_code = null;

    public ?string $analytics_fb_pixel_code = null;

    public ?string $seo_default_meta_title_template = null;

    public ?string $seo_default_meta_description = null;

    public ?string $seo_default_og_image = null;

    public static function group(): string
    {
        return 'bazaar';
    }

    /**
     * @return string[]
     */
    public static function encrypted(): array
    {
        return ['payment_gateways', 'shipping_couriers'];
    }
}
