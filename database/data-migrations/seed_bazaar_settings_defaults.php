<?php

use Spatie\LaravelSettings\Exceptions\SettingAlreadyExists;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * spatie/laravel-settings refuses to save a property that has no stored row,
 * so every fixed Global Settings parameter is seeded once with its default.
 * Later parameters ship as their own migration.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $plain = [
            'timeout_otp_minutes' => 5,
            'timeout_payment_minutes' => 1440,
            'timeout_review_hours' => 72,
            'timeout_tokenized_page_days' => 5,
            'timeout_on_process_days' => 3,
            'timeout_auto_confirm_days' => 7,
            'default_warehouse_id' => null,
            'store_name' => null,
            'store_logo' => null,
            'store_favicon' => null,
            'store_social_media' => [],
            'refund_min_percent' => 0,
            'refund_max_percent' => 100,
            'robots_txt_content' => null,
            'analytics_gsc_code' => null,
            'analytics_ga4_code' => null,
            'analytics_fb_pixel_code' => null,
            'seo_default_meta_title_template' => null,
            'seo_default_meta_description' => null,
            'seo_default_og_image' => null,
        ];

        $encrypted = [
            'payment_gateways' => [
                ['provider' => 'midtrans', 'active' => false, 'server_key' => null, 'client_key' => null, 'enabled_methods' => []],
            ],
            'shipping_couriers' => [
                ['provider' => 'rajaongkir', 'active' => false, 'api_key' => null, 'enabled_couriers' => []],
            ],
        ];

        foreach ($plain as $name => $value) {
            $this->seed(fn () => $this->migrator->add("bazaar.{$name}", $value));
        }

        foreach ($encrypted as $name => $value) {
            $this->seed(fn () => $this->migrator->addEncrypted("bazaar.{$name}", $value));
        }
    }

    private function seed(Closure $add): void
    {
        try {
            $add();
        } catch (SettingAlreadyExists) {
            // Re-run against an install that already holds this row.
        }
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('settings')->where('group', 'bazaar')->delete();
    }
};
