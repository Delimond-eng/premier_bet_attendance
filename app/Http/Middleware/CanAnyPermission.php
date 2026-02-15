<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanAnyPermission
{
    /**
     * Route middleware: allows access if the user has ANY of the given abilities.
     *
     * Usage:
     * - ->middleware('canany:agents.create,agents.update')
     * - ->middleware('canany:agents.create|agents.update')
     */
    public function handle(Request $request, Closure $next, ...$abilities)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $flat = [];
        foreach ($abilities as $a) {
            $a = (string) $a;
            if ($a === '') {
                continue;
            }
            // Support "a|b" and "a,b" syntaxes.
            $parts = preg_split('/[|,]/', $a) ?: [];
            foreach ($parts as $p) {
                $p = trim((string) $p);
                if ($p !== '') {
                    $flat[] = $p;
                }
            }
        }

        $flat = array_values(array_unique($flat));
        if (empty($flat)) {
            abort(403);
        }

        if (method_exists($user, 'canAny')) {
            if (!$user->canAny($flat)) {
                abort(403);
            }
        } else {
            // Fallback for older user implementations.
            $ok = false;
            foreach ($flat as $ability) {
                if ($user->can($ability)) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                abort(403);
            }
        }

        return $next($request);
    }
}

