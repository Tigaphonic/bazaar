<?php

namespace Tigaphonic\Bazaar\Media\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tigaphonic\Bazaar\Media\Conversions\BazaarWebpConversion;

/**
 * Gateway other domains use to read or remove media (AD-5). Returns absolute
 * URLs only (a headless portal cannot resolve a relative disk URL), never
 * filesystem paths.
 */
class MediaService
{
    public function getOriginalUrl(Media $media): string
    {
        return url($media->getUrl());
    }

    public function getVariantUrl(Media $media, string $conversion = BazaarWebpConversion::NAME): string
    {
        return url($media->getUrl($conversion));
    }

    /**
     * Deleting a Media row also removes its original and every conversion file.
     */
    public function deleteMedia(Media $media): void
    {
        if (!$media->delete()) {
            throw new \RuntimeException("Failed to delete media ID: {$media->getKey()}");
        }
    }
}
