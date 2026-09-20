<?php

// Story 1.10 RED-PHASE scaffold — MediaService Unit Tests.
// Menguji MediaService sebagai satu-satunya gateway yang dipakai domain lain
// untuk mengakses URL media (AD-5: akses lintas domain hanya via Service).
//
// Un-skip ketika: MediaService di src/Media/Services/MediaService.php tersedia.
//
// Referensi AC:
// AC2: Varian WebP disajikan lewat Service/API — tidak pernah expose internal path.
// AD-5: Domain lain tidak boleh akses Media Model langsung.
// AD-31: Satu pipeline bersama, bukan per-domain.

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tigaphonic\Bazaar\Media\Services\MediaService;

// ---------------------------------------------------------------------------
// MediaService sebagai Service Layer gateway (AD-5)
// ---------------------------------------------------------------------------

it('MediaService::class exists in src/Media/Services/', function () {
    $this->markTestSkipped('RED — MediaService belum ada; Story 1.10');

    expect(class_exists(MediaService::class))->toBeTrue();
});

it('MediaService::getVariantUrl() returns a string URL for the "webp" conversion', function () {
    $this->markTestSkipped('RED — MediaService belum ada; Story 1.10');

    // Mock Media model — kita uji hanya logic service, bukan disk
    $media = $this->mock(Media::class);
    $media->shouldReceive('getUrl')
        ->with('webp')
        ->once()
        ->andReturn('https://example.com/storage/1/conversions/photo-webp.webp');

    $service = app(MediaService::class);
    $url     = $service->getVariantUrl($media, 'webp');

    expect($url)->toBe('https://example.com/storage/1/conversions/photo-webp.webp');
});

it('MediaService::getOriginalUrl() returns the original file URL without any conversion', function () {
    $this->markTestSkipped('RED — MediaService belum ada; Story 1.10');

    $media = $this->mock(Media::class);
    $media->shouldReceive('getUrl')
        ->withNoArgs()
        ->once()
        ->andReturn('https://example.com/storage/1/photo.jpg');

    $service     = app(MediaService::class);
    $originalUrl = $service->getOriginalUrl($media);

    expect($originalUrl)->toBe('https://example.com/storage/1/photo.jpg');
});

it('MediaService::deleteMedia() removes both original and all conversion variants', function () {
    $this->markTestSkipped('RED — MediaService belum ada; Story 1.10');

    $media = $this->mock(Media::class);
    $media->shouldReceive('delete')
        ->once()
        ->andReturnNull();

    $service = app(MediaService::class);
    $service->deleteMedia($media);

    // Tidak ada exception — media di-delete via service
});

// ---------------------------------------------------------------------------
// AD-5: Domain lain tidak boleh import atau menggunakan Media Model langsung
// ---------------------------------------------------------------------------

it('no domain outside src/Media/ accesses the Media model directly', function () {
    $this->markTestSkipped('RED — src/Media/ belum ada; Story 1.10');

    $domainDirs = glob(__DIR__.'/../../../src/*', GLOB_ONLYDIR);
    $violations = [];

    foreach ($domainDirs as $dir) {
        if (basename($dir) === 'Media') {
            continue;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            // Akses langsung ke Spatie Media model — harus via MediaService
            if (str_contains($content, 'Spatie\MediaLibrary\MediaCollections\Models\Media')
                && ! str_contains($content, 'MediaService')
            ) {
                $violations[] = $file->getPathname();
            }
        }
    }

    expect($violations)
        ->toBeEmpty('Domain ini mengakses Media model langsung tanpa MediaService (violation AD-5): '
            .implode(', ', $violations));
});
