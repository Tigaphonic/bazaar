<?php

namespace Tigaphonic\Bazaar\Media\Concerns;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tigaphonic\Bazaar\Media\Conversions\BazaarWebpConversion;

/**
 * Only entry point for attaching media to a Bazaar model (AD-31): every model
 * that stores images uses this trait instead of InteractsWithMedia directly.
 */
trait HasBazaarMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        BazaarWebpConversion::apply($this->addMediaConversion(BazaarWebpConversion::NAME));
    }

    /**
     * @return Collection<int, Conversion>
     */
    public function getRegisteredMediaConversions(): Collection
    {
        $this->mediaConversions = [];
        $this->registerMediaConversions();

        return collect($this->mediaConversions)->values();
    }
}
