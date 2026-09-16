<?php

namespace Tigaphonic\Bazaar\Shell\Support;

use Filament\Notifications\Notification;

/**
 * Thin wrapper around Filament's own native toast/notification system
 * (`Filament\Notifications\Notification`) — DESIGN.md/AD-33 classify Toast
 * as a restyle of that Filament primitive, not a bespoke component. Every
 * later epic's state-changing action calls one of these instead of building
 * `Notification::make()` chains ad hoc, keeping the "Toast fires on every
 * action" rule (EXPERIENCE.md) one call site per variant.
 *
 * Method names follow Filament's own vocabulary (success/danger/warning/info)
 * rather than DESIGN.md's "warn" label, since this wraps `Notification::make()`
 * directly.
 */
final class Toast
{
    public static function success(string $message): void
    {
        Notification::make()->success()->title($message)->send();
    }

    public static function danger(string $message): void
    {
        Notification::make()->danger()->title($message)->send();
    }

    public static function warning(string $message): void
    {
        Notification::make()->warning()->title($message)->send();
    }

    public static function info(string $message): void
    {
        Notification::make()->info()->title($message)->send();
    }
}
