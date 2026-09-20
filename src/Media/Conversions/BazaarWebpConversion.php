<?php

namespace Tigaphonic\Bazaar\Media\Conversions;

use Spatie\MediaLibrary\Conversions\Conversion;

/**
 * The one compress + WebP definition every upload point shares (AD-31).
 * Domains never declare their own conversion; they use HasBazaarMedia.
 */
final class BazaarWebpConversion
{
    public const NAME = 'webp';

    public const QUALITY = 80;

    /**
     * Applies the shared settings to a conversion created by the caller. The
     * original stays untouched: medialibrary writes the result as a separate
     * file under the media's conversions path.
     */
    public static function apply(Conversion $conversion): Conversion
    {
        // Runs inline so the variant exists the moment the upload is saved;
        // a queued default would let Service/API hand out a URL whose file
        // has not been generated yet.
        $conversion->nonQueued();

        $conversion
            ->format('webp')
            ->quality(self::QUALITY);

        return $conversion;
    }
}
