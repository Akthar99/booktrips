<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Allow only super admins through.
     *
     * Everyone else gets a plain 404: the console is not advertised to the
     * public, so probing /admin should look like any other missing page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}
