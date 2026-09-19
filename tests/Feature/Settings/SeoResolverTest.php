<?php

// Story 1.7 acceptance tests. AC: "sebuah Item/Blog/Page/Category tidak punya
// gambar sama sekali → default OG image dari Global SEO Defaults dipakai sebagai
// fallback terakhir" (FR-24/FR-29, epics.md baris 415–417).
//
// Scope 1.7 = SeoResolverService yang mengonsumsi SettingsService untuk fallback
// berjenjang metadata SEO per-entity. Field seo_default_* sudah ada di BazaarSettings
// dan sudah hijau di Story 1.6 (AC10) — story ini menambah resolver-nya.
//
// Domain placement: src/Settings/Services/SeoResolverService.php (Settings domain,
// mengakses SettingsService lewat DI, AD-5 compliant — tidak ada lintas-domain langsung).
// Entity domain (Item/Blog/Page/Category) tidak diimport langsung; resolver menerima
// array/DTO yang merepresentasikan metadata mentah entity (decoupled).
//
// Pact tidak relevan: tidak ada dua independently-deployable service saling memanggil.
// Browser E2E tidak dijadwalkan di red-phase ini: fallback logic adalah Service concern.
//

use Tigaphonic\Bazaar\Settings\Services\SeoResolverService;
use Tigaphonic\Bazaar\Settings\Services\SettingsService;

// ---------------------------------------------------------------------------
// Service binding — SeoResolverService dapat di-resolve dari container
// ---------------------------------------------------------------------------

it('SeoResolverService can be resolved from the container via dependency injection', function () {
    $resolver = app(SeoResolverService::class);

    expect($resolver)->toBeInstanceOf(SeoResolverService::class);
});

// ---------------------------------------------------------------------------
// OG Image fallback — AC2 inti Story 1.7
// ---------------------------------------------------------------------------

it('resolveOgImage() returns the entity\'s own image when the entity has one', function () {
    $resolver = app(SeoResolverService::class);

    // Entity array: portal atau domain lain hanya mengirim snapshot metadata mentah
    $entityMeta = ['og_image' => '/images/product-cover.jpg'];

    expect($resolver->resolveOgImage($entityMeta))->toBe('/images/product-cover.jpg');
});

it('resolveOgImage() returns seo_default_og_image from Global SEO Defaults when entity has no image', function () {
    app(SettingsService::class)->update([
        'seo_default_og_image' => '/images/og-default.jpg',
    ]);

    $resolver = app(SeoResolverService::class);

    // Entity tanpa gambar sama sekali
    $entityMeta = ['og_image' => null];

    expect($resolver->resolveOgImage($entityMeta))->toBe('/images/og-default.jpg');
});

it('resolveOgImage() returns seo_default_og_image when entity og_image key is absent entirely', function () {
    app(SettingsService::class)->update([
        'seo_default_og_image' => '/images/og-fallback.png',
    ]);

    $resolver = app(SeoResolverService::class);

    // entity tidak punya kunci og_image sama sekali
    $entityMeta = ['name' => 'Sepatu Kulit'];

    expect($resolver->resolveOgImage($entityMeta))->toBe('/images/og-fallback.png');
});

it('resolveOgImage() returns null when entity has no image and seo_default_og_image is not configured', function () {
    // Pastikan default OG kosong (bisa null atau string kosong)
    app(SettingsService::class)->update(['seo_default_og_image' => null]);

    $resolver = app(SeoResolverService::class);

    $entityMeta = ['og_image' => null];

    // Resolver mengembalikan null bila tidak ada fallback; pemanggil menentukan
    // apa yang ditampilkan (bukan tanggung jawab resolver)
    expect($resolver->resolveOgImage($entityMeta))->toBeNull();
});

// ---------------------------------------------------------------------------
// Meta Title fallback — template dari Global SEO Defaults
// ---------------------------------------------------------------------------

it('resolveMetaTitle() returns entity\'s own meta title when set explicitly', function () {
    $resolver = app(SeoResolverService::class);

    $entityMeta = [
        'meta_title' => 'Sepatu Kulit Premium — SEO Title Custom',
        'name' => 'Sepatu Kulit',
    ];

    expect($resolver->resolveMetaTitle($entityMeta))->toBe('Sepatu Kulit Premium — SEO Title Custom');
});

it('resolveMetaTitle() applies the default meta title template substituting entity name when entity meta title is empty', function () {
    // Template: "{nama entity} — {nama toko}"
    app(SettingsService::class)->update([
        'seo_default_meta_title_template' => '{nama entity} — {nama toko}',
        'store_name' => 'Toko Tigaphonic',
    ]);

    $resolver = app(SeoResolverService::class);

    $entityMeta = ['meta_title' => null, 'name' => 'Sepatu Kulit'];

    expect($resolver->resolveMetaTitle($entityMeta))->toBe('Sepatu Kulit — Toko Tigaphonic');
});

it('resolveMetaTitle() falls back to entity name alone when template is not configured', function () {
    app(SettingsService::class)->update(['seo_default_meta_title_template' => null]);

    $resolver = app(SeoResolverService::class);

    $entityMeta = ['meta_title' => null, 'name' => 'Sepatu Kulit'];

    // Fallback paling akhir: nama entity saja (tidak pernah string kosong)
    expect($resolver->resolveMetaTitle($entityMeta))->toBe('Sepatu Kulit');
});

// ---------------------------------------------------------------------------
// Meta Description fallback
// ---------------------------------------------------------------------------

it('resolveMetaDescription() returns entity\'s own meta description when set', function () {
    $resolver = app(SeoResolverService::class);

    $entityMeta = ['meta_description' => 'Deskripsi produk khusus untuk SEO.'];

    expect($resolver->resolveMetaDescription($entityMeta))->toBe('Deskripsi produk khusus untuk SEO.');
});

it('resolveMetaDescription() returns seo_default_meta_description from settings when entity meta description is empty', function () {
    app(SettingsService::class)->update([
        'seo_default_meta_description' => 'Toko brand terpercaya di Indonesia.',
    ]);

    $resolver = app(SeoResolverService::class);

    $entityMeta = ['meta_description' => null];

    expect($resolver->resolveMetaDescription($entityMeta))
        ->toBe('Toko brand terpercaya di Indonesia.');
});

it('resolveMetaDescription() returns null when entity has no description and no default is configured', function () {
    app(SettingsService::class)->update(['seo_default_meta_description' => null]);

    $resolver = app(SeoResolverService::class);

    $entityMeta = ['meta_description' => null];

    expect($resolver->resolveMetaDescription($entityMeta))->toBeNull();
});

// ---------------------------------------------------------------------------
// OG Title fallback berjenjang (FR-24): OG Title → Meta Title → nama entity
// ---------------------------------------------------------------------------

it('resolveOgTitle() uses og_title when present', function () {
    $resolver = app(SeoResolverService::class);

    $entityMeta = [
        'og_title' => 'OG Title Khusus',
        'meta_title' => 'Meta Title',
        'name' => 'Sepatu Kulit',
    ];

    expect($resolver->resolveOgTitle($entityMeta))->toBe('OG Title Khusus');
});

it('resolveOgTitle() falls back to meta title when og_title is absent', function () {
    $resolver = app(SeoResolverService::class);

    $entityMeta = [
        'og_title' => null,
        'meta_title' => 'Meta Title Produk',
        'name' => 'Sepatu Kulit',
    ];

    expect($resolver->resolveOgTitle($entityMeta))->toBe('Meta Title Produk');
});

it('resolveOgTitle() falls back to entity name when both og_title and meta_title are absent', function () {
    $resolver = app(SeoResolverService::class);

    $entityMeta = [
        'og_title' => null,
        'meta_title' => null,
        'name' => 'Sepatu Kulit',
    ];

    expect($resolver->resolveOgTitle($entityMeta))->toBe('Sepatu Kulit');
});

// ---------------------------------------------------------------------------
// Domain boundary — SeoResolverService ada di Settings domain (ArchTest)
// ---------------------------------------------------------------------------

it('SeoResolverService class lives in the Settings domain namespace, not another domain', function () {
    // Cek namespace — class harus di bawah Tigaphonic\Bazaar\Settings\
    expect(SeoResolverService::class)
        ->toStartWith('Tigaphonic\\Bazaar\\Settings\\');
});

it('SeoResolverService does not import any Catalog, User, or other domain Model directly', function () {
    // AD-5: lintas-domain hanya lewat Service. Resolver hanya menerima array metadata,
    // tidak pernah import Eloquent Model domain lain.
    $source = file_get_contents(__DIR__.'/../../../src/Settings/Services/SeoResolverService.php');

    expect($source)
        ->not->toContain('Tigaphonic\\Bazaar\\Catalog\\Models\\')
        ->not->toContain('Tigaphonic\\Bazaar\\User\\Models\\')
        ->not->toContain('Tigaphonic\\Bazaar\\Content\\Models\\');
});

// ---------------------------------------------------------------------------
// Resolve semua tipe entity: Item, Blog, Page, Category (representasi array)
// ---------------------------------------------------------------------------

it('resolveOgImage() works with entity metadata from any domain (Item/Blog/Page/Category via array)', function () {
    app(SettingsService::class)->update([
        'seo_default_og_image' => '/images/og-default.jpg',
    ]);

    $resolver = app(SeoResolverService::class);

    // Simulasi berbagai tipe entity tanpa gambar
    $entityTypes = [
        ['type' => 'item',     'og_image' => null, 'name' => 'Sepatu'],
        ['type' => 'blog',     'og_image' => null, 'name' => 'Blog Post'],
        ['type' => 'page',     'og_image' => null, 'name' => 'Tentang Kami'],
        ['type' => 'category', 'og_image' => null, 'name' => 'Aksesori'],
    ];

    foreach ($entityTypes as $entityMeta) {
        expect($resolver->resolveOgImage($entityMeta))
            ->toBe('/images/og-default.jpg', "Gagal untuk entity type: {$entityMeta['type']}");
    }
});
