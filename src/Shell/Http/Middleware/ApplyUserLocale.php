<?php

namespace Tigaphonic\Bazaar\Shell\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Tigaphonic\Bazaar\Shell\Support\UserPreferences;

/**
 * Applies the authenticated Staff member's persisted locale preference
 * (Shell\Support\UserPreferences) to the app for the current request.
 * Registered on the Bazaar panel's own middleware stack (never the global
 * `web` group) — EXPERIENCE.md's bilingual requirement is scoped to the
 * admin panel, and only the panel's `->middleware([...])` receives it.
 */
class ApplyUserLocale
{
    public function __construct(private readonly UserPreferences $preferences) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model) {
            App::setLocale($this->preferences->locale($user));
        }

        return $next($request);
    }
}
