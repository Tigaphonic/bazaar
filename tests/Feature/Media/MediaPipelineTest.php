<?php

// Story 1.10 RED-PHASE scaffold. AC source: epics.md §Story 1.10.
// Architecture constraint: AD-31 — satu shared pipeline, bukan per-domain reimplementation.
// Package: spatie/laravel-medialibrary ^11.23 (epic-1-context.md / addendum.md).
//
// Un-skip setiap test ketika implementasi yang terkait selesai:
//   - MediaConversion class di src/Media/ atau src/Shell/ (AD-31)
//   - Trait HasBazaarMedia yang di-use oleh setiap model yang upload gambar
//   - spatie/image atau spatie/image-optimizer terpasang untuk konversi WebP
//
// Pact tidak relevan: tidak ada dua independently-deployable service dalam repo ini.
// Browser E2E untuk Dropzone hint ada di tests/Browser/Media/dropzone-hint.spec.ts.
//
// Referensi AC:
// AC1: Gambar dikompres & dikonversi ke WebP lewat 1 mekanisme bersama (spatie/laravel-medialibrary
//      conversions) — bukan implementasi terpisah per domain.
// AC2: File asli tetap disimpan sebagai sumber; hasil WebP tersimpan sebagai varian terpisah.
// AC3: Dropzone dipakai sebagai UI upload seragam di semua domain.

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tigaphonic\Bazaar\Media\Concerns\HasBazaarMedia;
use Tigaphonic\Bazaar\Media\Conversions\BazaarWebpConversion;
use Tigaphonic\Bazaar\Media\Services\MediaService;

// ---------------------------------------------------------------------------
// AC1a: Pipeline terdaftar satu kali — tidak ada per-domain registration
// ---------------------------------------------------------------------------

it('BazaarWebpConversion class exists at the canonical location (one shared pipeline, AD-31)', function () {
    expect(class_exists(BazaarWebpConversion::class))->toBeTrue();
});

it('HasBazaarMedia trait registers exactly one conversion named "webp" via BazaarWebpConversion', function () {
    // Buat model fixture yang menggunakan trait
    $model = new class {
        use HasBazaarMedia;
    };

    $conversions = $model->getRegisteredMediaConversions();

    // Tepat satu konversi terdaftar
    expect($conversions)->toHaveCount(1);

    // Konversinya bernama 'webp' — nama ini yang dipakai Service Layer saat expose varian
    expect($conversions->first()->getName())->toBe('webp');
});

it('HasBazaarMedia trait does not define its own image manipulation logic — delegates to BazaarWebpConversion', function () {
    $source = file_get_contents(__DIR__.'/../../../src/Media/Concerns/HasBazaarMedia.php');

    // Trait tidak boleh berisi manipulasi gambar inline
    expect($source)
        ->not->toContain('->width(')
        ->not->toContain('->height(')
        ->not->toContain('->format(')
        ->not->toContain('->optimize(');
});

// ---------------------------------------------------------------------------
// AC1b: Konversi WebP berjalan otomatis saat media di-add ke model
// ---------------------------------------------------------------------------

it('uploading an image via addMedia() triggers the webp conversion automatically', function () {
    Storage::fake('public');

    // Gunakan model fixture yang menerapkan HasBazaarMedia
    $model = \Workbench\App\Models\MediaTestModel::create([]);

    $file = UploadedFile::fake()->image('product.jpg', 800, 600);

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    // Konversi berjalan — varian 'webp' harus ada
    expect($media->getPath('webp'))->not->toBeEmpty();
    expect(file_exists($media->getPath('webp')))->toBeTrue();
});

it('webp conversion produces a .webp file format, not the original jpg/png format', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $file  = UploadedFile::fake()->image('banner.png', 1200, 400);

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    $webpPath = $media->getPath('webp');

    // File ekstensi harus .webp
    expect(pathinfo($webpPath, PATHINFO_EXTENSION))->toBe('webp');

    // MIME type harus image/webp
    expect(mime_content_type($webpPath))->toBe('image/webp');
});

it('webp conversion file size is smaller than the original file', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $file  = UploadedFile::fake()->image('large-product.jpg', 2000, 2000);

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    $originalSize = filesize($media->getPath());
    $webpSize     = filesize($media->getPath('webp'));

    // WebP harus lebih kecil dari original (kompresi aktif)
    expect($webpSize)->toBeLessThan($originalSize);
});

// ---------------------------------------------------------------------------
// AC2: File asli tetap disimpan; WebP adalah varian terpisah — tidak pernah overwrite
// ---------------------------------------------------------------------------

it('original file is preserved after webp conversion — not overwritten', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $file  = UploadedFile::fake()->image('logo.jpg', 400, 400);

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    // Path tanpa conversion name = file original
    $originalPath = $media->getPath();

    // Original harus ada dan masih berupa jpg
    expect(file_exists($originalPath))->toBeTrue();
    expect(pathinfo($originalPath, PATHINFO_EXTENSION))->toBe('jpg');
});

it('getUrl() without conversion returns original file URL; getUrl("webp") returns the webp variant URL', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $file  = UploadedFile::fake()->image('item.jpg', 500, 500);

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    $originalUrl = $media->getUrl();
    $webpUrl     = $media->getUrl('webp');

    // Dua URL berbeda — bukan URL yang sama
    expect($originalUrl)->not->toBe($webpUrl);

    // WebP URL mengandung 'webp' (konvensional spatie/medialibrary)
    expect($webpUrl)->toContain('webp');
});

it('Service Layer exposes webp URL via MediaService::getVariantUrl() — never exposes internal paths', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $file  = UploadedFile::fake()->image('favicon.png', 32, 32);

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    $service = app(MediaService::class);

    $url = $service->getVariantUrl($media, 'webp');

    // URL harus berupa string yang valid (bukan path filesystem)
    expect($url)->toBeString();
    expect(str_starts_with($url, 'http'))->toBeTrue();
    expect($url)->toContain('webp');
});

// ---------------------------------------------------------------------------
// AC2 (negatif): Konversi TIDAK menimpa original secara permanen
// ---------------------------------------------------------------------------

it('webp variant is stored as a separate conversion path — never modifies the original file in place', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $file  = UploadedFile::fake()->image('original.jpg', 800, 600);

    $originalContent = $file->get();

    $media = $model
        ->addMedia($file)
        ->toMediaCollection('images');

    // Isi file original tidak boleh berubah
    $storedContent = file_get_contents($media->getPath());

    expect($storedContent)->toBe($originalContent);
});

// ---------------------------------------------------------------------------
// AD-31: Shared pipeline dipakai ulang — bukan per-domain reimplementation
// ---------------------------------------------------------------------------

it('no domain outside src/Media/ or src/Shell/ defines its own media conversion class', function () {
    // Scan semua domain directories
    $domainDirs = glob(__DIR__.'/../../../src/*', GLOB_ONLYDIR);

    $violatingFiles = [];

    foreach ($domainDirs as $dir) {
        $domainName = basename($dir);

        // Izinkan hanya Media dan Shell yang mendefinisikan conversion
        if (in_array($domainName, ['Media', 'Shell'], true)) {
            continue;
        }

        // Cari file yang mengandung 'registerMediaConversions' — tanda per-domain reimplementation
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (str_contains($content, 'registerMediaConversions')) {
                $violatingFiles[] = $file->getPathname();
            }
        }
    }

    // Tidak ada domain lain yang override registerMediaConversions
    expect($violatingFiles)
        ->toBeEmpty("Domain berikut mendefinisikan sendiri media conversion (violation AD-31): "
            .implode(', ', $violatingFiles));
});

it('HasBazaarMedia trait is the only entry point for registering media conversions — singleton pattern (AD-31)', function () {
    // Semua model yang upload gambar harus menggunakan HasBazaarMedia, bukan InteractsWithMedia langsung
    // Ini diperiksa dengan memastikan tidak ada Model yang implements InteractsWithMedia secara manual
    // tanpa juga menggunakan HasBazaarMedia

    $modelFiles = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(__DIR__.'/../../../src')
    );

    $violators = [];

    foreach ($modelFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if (str_contains($content, 'InteractsWithMedia')
            && ! str_contains($content, 'HasBazaarMedia')
        ) {
            $violators[] = $file->getPathname();
        }
    }

    expect($violators)
        ->toBeEmpty("Model berikut menggunakan InteractsWithMedia secara langsung tanpa HasBazaarMedia (AD-31): "
            .implode(', ', $violators));
});

it('MediaService::deleteMedia() removes the original and the webp variant files from disk', function () {
    Storage::fake('public');

    $model = \Workbench\App\Models\MediaTestModel::create([]);
    $media = $model
        ->addMedia(UploadedFile::fake()->image('gone.jpg', 300, 300))
        ->toMediaCollection('images');

    $originalPath = $media->getPath();
    $webpPath = $media->getPath('webp');

    expect(file_exists($originalPath))->toBeTrue();
    expect(file_exists($webpPath))->toBeTrue();

    app(MediaService::class)->deleteMedia($media);

    expect(file_exists($originalPath))->toBeFalse();
    expect(file_exists($webpPath))->toBeFalse();
    expect(Media::query()->count())->toBe(0);
});
