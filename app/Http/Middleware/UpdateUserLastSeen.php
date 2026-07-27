<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastSeen
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // On vérifie l'authentification après le passage dans le contrôleur
        // pour capturer aussi le moment où l'utilisateur vient de se connecter.
        if (Auth::check()) {
            $user = Auth::user();

            // On ne met à jour que si la dernière activité date de plus d'une minute
            // Le cast 'datetime' dans le modèle User assure que last_seen_at est un objet Carbon ou null
            if (!$user->last_seen_at || $user->last_seen_at->diffInMinutes(now()) >= 1) {
                // On utilise une requête directe pour ne pas modifier 'updated_at'
                \App\Models\User::where('id', $user->id)->update([
                    'last_seen_at' => now()
                ]);
            }
        }

        return $response;
    }
}
