<?php

use Tigaphonic\Bazaar\Settings\Filament\Pages\GlobalSettings;
use Tigaphonic\Bazaar\User\Filament\Resources\AuditTrailResource;
use Tigaphonic\Bazaar\User\Filament\Resources\RoleResource;
use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;
use Tigaphonic\Bazaar\User\Models\AuditTrail;

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
        RoleResource::class,
        AuditTrailResource::class,
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

    /*
     * Shell domain defaults (AD-33, Story 1.2). Used whenever a Staff member
     * has never set a personal preference in bazaar_user_preferences yet.
     */
    'shell' => [
        'default_theme' => 'light',
        'default_locale' => 'en',
    ],

    /*
     * Permissions Bazaar ships. Created on demand (Role form, bazaar:grant-admin)
     * so they exist even when the permission tables were migrated after Bazaar's
     * own data migrations ran.
     */
    'permissions' => [
        'manage-settings',
    ],

    /*
     * Optional headless API Layer (FR-35). Off by default: no Bazaar API route
     * is registered until a host app opts in. Read at boot, so run
     * `php artisan route:cache` again after changing it. Payment/Shipping
     * webhook ingress is never governed by this flag (AD-11).
     */
    'api' => [
        'enabled' => false,
    ],

    /*
     * Audit Trail (FR-20) records every create/update/delete of every
     * Eloquent model automatically. Models listed here (or subclasses) are
     * skipped — use it for high-churn, non-business rows.
     */
    'audit' => [
        'model' => AuditTrail::class,

        'exclude_models' => [],
    ],

];
