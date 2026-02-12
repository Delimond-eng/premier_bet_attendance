<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GeoRestrictionApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('local') || $request->ip() === '127.0.0.1' || $request->ip() === '192.168.112.223') {
            return $next($request);
        }

        $location = geoip()->getLocation($request->ip());

        if ($location->iso_code !== 'CD') {
            return response()->json([
                'message' => 'Accès restreint pour cette localisation.',
            ], 403);
        }

        return $next($request);
    }
}
