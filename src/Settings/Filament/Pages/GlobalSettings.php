<?php

namespace Tigaphonic\Bazaar\Settings\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

/**
 * Placeholder until Story 1.6 replaces this with the real
 * spatie/laravel-settings-backed List+Edit page.
 */
class GlobalSettings extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Global Settings';

    protected string $view = 'bazaar::settings.global-settings';
}
