<?php

use Tigaphonic\Bazaar\Settings\Filament\Pages\GlobalSettings;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;

// config for Tigaphonic/Bazaar
return [

    /*
     * The id of the Filament panel Bazaar should attach its Resources/Pages to.
     * Leave null to attach to Filament::getDefaultPanel() (AD-16 seam 3).
     */
    'panel' => null,

    /*
     * Filament Resource classes Bazaar registers onto the target panel.
     * Override an entry from a client app's own config/bazaar.php to swap
     * in a subclass instead of editing the package's own Resource directly
     * (AD-16 seam 3 — the only sanctioned way to customize this behavior).
     */
    'resources' => [
        UserResource::class,
    ],

    /*
     * Filament Page classes Bazaar registers onto the target panel. Same
     * override rule as 'resources' above.
     */
    'pages' => [
        GlobalSettings::class,
    ],

    /*
     * The model backing the User & Access domain. Bazaar never owns its own
     * Staff/users table (AD-18 amendment) — it attaches to the host app's own
     * authenticatable model. Leave null to fall back to
     * config('auth.providers.users.model').
     */
    'models' => [
        'user' => null,
    ],

];
