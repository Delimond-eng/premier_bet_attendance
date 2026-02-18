<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagerStationContext
{
    public static function stationId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        // During attendance punch routes, station scoping must be disabled for any user.
        if (self::isPresencePunchRequest()) {
            return null;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return null;
        }

        if ($user->station_id === null || $user->station_id === '') {
            return null;
        }

        return (int) $user->station_id;
    }

    public static function isScopedManager(): bool
    {
        return self::stationId() !== null;
    }

    private static function isPresencePunchRequest(): bool
    {
        if (!app()->bound('request')) {
            return false;
        }

        $request = app('request');
        if (!$request instanceof Request) {
            return false;
        }

        if ($request->routeIs('presence.store') || $request->routeIs('agent.punch')) {
            return true;
        }

        if (strtoupper((string) $request->method()) !== 'POST') {
            return false;
        }

        return $request->is('presences/store')
            || $request->is('api/agent.punch');
    }
}
