<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeoRestriction
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
        // Autoriser l'accès en environnement local (artisan serve ou localhost)
        if (app()->environment('local')) {
            return $next($request);
        }

        try {
            // Récupération de la localisation IP
            $location = geoip()->getLocation($request->ip());

            // Vérification du code ISO du pays (CD = RDC)
            if ($location->iso_code !== 'CD') {
                abort(403, 'Accès restreint pour cette localisation.');
            }
        } catch (\Exception $e) {
            // En cas d'erreur (ex. service GeoIP non disponible), bloquer ou journaliser
            abort(403, 'Impossible de déterminer la localisation.');
            // ou bien logger l'erreur si tu veux suivre
            Log::warning('GeoIP erreur: ' . $e->getMessage());
        }
        return $next($request);
    }
}
