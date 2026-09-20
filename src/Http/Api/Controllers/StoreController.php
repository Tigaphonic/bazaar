<?php

namespace Tigaphonic\Bazaar\Http\Api\Controllers;

use Tigaphonic\Bazaar\Http\Api\Resources\StoreResource;
use Tigaphonic\Bazaar\Settings\Services\SettingsService;

class StoreController
{
    public function __construct(private readonly SettingsService $settings) {}

    public function show(): StoreResource
    {
        return new StoreResource($this->settings->get());
    }
}
