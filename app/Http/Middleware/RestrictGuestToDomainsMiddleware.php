<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictGuestToDomainsMiddleware
{
    private const ALLOWED_ROUTE_NAMES = [
        'websites.index',
        'websites.data',
        'websites.show',
        'websites.favorites.toggle',
        'websites.favorites.export.csv',
        'websites.favorites.export.pdf',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isGuest()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Guest users may only access domains pages.',
            ], 403);
        }

        return redirect()->route('websites.index');
    }
}
