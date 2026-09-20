<?php

use Illuminate\Support\Facades\Route;
use Tigaphonic\Bazaar\Http\Api\Controllers\StoreController;
use Tigaphonic\Bazaar\Http\Api\Middleware\AuthenticateApi;

// Loaded only when bazaar.api.enabled is true (BazaarServiceProvider). Gateway
// webhooks never belong here: Payment/Shipping register their own always-on
// routes so a Service-Layer-only install still receives vendor callbacks.
Route::prefix('bazaar/api/v1')
    ->middleware(['api', AuthenticateApi::class.':sanctum'])
    ->group(function (): void {
        Route::get('store', [StoreController::class, 'show']);
    });
