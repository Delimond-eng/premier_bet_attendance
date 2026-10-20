<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManagerStationContext
{
    public static function stationId(): ?int
    {
        $stationIds = self::stationIds();
        return $stationIds !== null && count($stationIds) === 1 ? $stationIds[0] : null;
    }

    /**
     * Null means unrestricted; an empty array means the user has no station access.
     */
    public static function stationIds(): ?array
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

        $request = app()->bound('request') ? app('request') : null;
        $cacheKey = 'manager_station_ids.' . $user->id;
        if ($request instanceof Request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $assignedStationIds = DB::table('user_station')
            ->where('user_id', $user->id)
            ->pluck('station_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!$assignedStationIds && $user->station_id !== null && $user->station_id !== '') {
            $assignedStationIds = [(int) $user->station_id];
        }

        $hasGeographicAssignment = $user->region_id !== null || $user->city_id !== null;
        if (!$assignedStationIds && !$hasGeographicAssignment) {
            return null;
        }

        $query = DB::table('sites')->select('id');
        if ($assignedStationIds) {
            $query->whereIn('id', $assignedStationIds);
        }
        if ($user->region_id !== null) {
            $query->where('region_id', $user->region_id);
        }
        if ($user->city_id !== null) {
            $query->where('city_id', $user->city_id);
        }

        $stationIds = $query->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($request instanceof Request) {
            $request->attributes->set($cacheKey, $stationIds);
        }

        return $stationIds;
    }

    /**
     * Retourne le préfixe de matricule pour les utilisateurs de type sous-traitance.
     */
    public static function matriculePrefix(): ?string
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return null;
        }

        return $user->matricule_prefix ?: null;
    }

    public static function isScopedManager(): bool
    {
        return self::stationIds() !== null || self::matriculePrefix() !== null;
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
