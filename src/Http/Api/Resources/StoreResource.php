<?php

namespace Tigaphonic\Bazaar\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tigaphonic\Bazaar\Settings\Support\BazaarSettings;

/**
 * Explicit whitelist: BazaarSettings also holds gateway/courier credentials,
 * which must never reach a portal client.
 *
 * @property BazaarSettings $resource
 */
class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'store_name' => $this->resource->store_name,
            'store_logo' => $this->resource->store_logo,
            'store_favicon' => $this->resource->store_favicon,
            'store_social_media' => $this->resource->store_social_media,
            'seo_default_meta_title_template' => $this->resource->seo_default_meta_title_template,
            'seo_default_meta_description' => $this->resource->seo_default_meta_description,
            'seo_default_og_image' => $this->resource->seo_default_og_image,
        ];
    }
}
