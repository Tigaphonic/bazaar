<?php

namespace Tigaphonic\Bazaar\Http\Api\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;

/**
 * An API never redirects or renders HTML. The stock middleware treats a
 * request without an Accept header as a browser one and resolves the host's
 * `login` route, which a headless install may not define (500). Forcing JSON
 * here, first in the stack, also keeps later 403/422 responses JSON.
 */
class AuthenticateApi extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (! $request->wantsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        return parent::handle($request, $next, ...$guards);
    }

    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
