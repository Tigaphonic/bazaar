<?php

namespace Tigaphonic\Bazaar\Settings\Services;

/**
 * Resolves per-entity SEO metadata against the Global SEO Defaults. Callers
 * pass a plain metadata snapshot, never a Model, so no domain is imported
 * across the boundary (AD-5). Every method falls back in the same order for
 * all entity types, entity value first and Global SEO Defaults last.
 */
class SeoResolverService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  array<string, mixed>  $entityMeta
     */
    public function resolveOgImage(array $entityMeta): ?string
    {
        return $this->filled($entityMeta['og_image'] ?? null)
            ?? $this->filled($this->settings->get()->seo_default_og_image);
    }

    /**
     * @param  array<string, mixed>  $entityMeta
     */
    public function resolveMetaTitle(array $entityMeta): ?string
    {
        $own = $this->filled($entityMeta['meta_title'] ?? null);

        if ($own !== null) {
            return $own;
        }

        $name = $this->filled($entityMeta['name'] ?? null);
        $settings = $this->settings->get();
        $template = $this->filled($settings->seo_default_meta_title_template);

        if ($template === null || $name === null) {
            return $name;
        }

        return str_replace(
            ['{nama entity}', '{nama toko}'],
            [$name, (string) $settings->store_name],
            $template,
        );
    }

    /**
     * @param  array<string, mixed>  $entityMeta
     */
    public function resolveMetaDescription(array $entityMeta): ?string
    {
        return $this->filled($entityMeta['meta_description'] ?? null)
            ?? $this->filled($this->settings->get()->seo_default_meta_description);
    }

    /**
     * OG Title, then Meta Title, then entity name (FR-24).
     *
     * @param  array<string, mixed>  $entityMeta
     */
    public function resolveOgTitle(array $entityMeta): ?string
    {
        return $this->filled($entityMeta['og_title'] ?? null)
            ?? $this->resolveMetaTitle($entityMeta);
    }

    private function filled(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
