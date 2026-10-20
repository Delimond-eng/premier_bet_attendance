<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use App\Models\PresenceAgents;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerStationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Pointage agent: never enforce station restrictions for any user.
        if ($this->isPresencePunchRequest($request)) {
            return $next($request);
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $next($request);
        }

        $stationIds = \App\Support\ManagerStationContext::stationIds();
        if ($stationIds === null) {
            return $next($request);
        }

        foreach (['station_id', 'site_id'] as $key) {
            $requestedStationId = $request->input($key);
            if ($requestedStationId !== null && $requestedStationId !== '' && !in_array((int) $requestedStationId, $stationIds, true)) {
                abort(403, "Station hors périmètre autorisé.");
            }
        }

        $requestedStationIds = $request->input('station_ids');
        if ($requestedStationIds !== null) {
            if (!is_array($requestedStationIds) || array_diff(array_map('intval', $requestedStationIds), $stationIds)) {
                abort(403, "Stations hors périmètre autorisé.");
            }
        }

        $stationId = count($stationIds) === 1 ? $stationIds[0] : null;
        if ($stationId !== null) {
            foreach (['station_id', 'site_id'] as $key) {
                $request->request->set($key, $stationId);
                $request->query->set($key, $stationId);
            }
        }
        $request->attributes->set('accessible_station_ids', $stationIds);

        $agentId = $request->input('agent_id');
        if ($agentId !== null && $agentId !== '') {
            $isInManagerStation = Agent::withoutGlobalScopes()
                ->whereKey((int) $agentId)
                ->whereIn('site_id', $stationIds)
                ->exists();

            if (!$isInManagerStation) {
                abort(403, "Agent hors station du manager.");
            }
        }

        $presenceId = $request->input('presence_agent_id');
        if ($presenceId !== null && $presenceId !== '') {
            $isInManagerStation = PresenceAgents::withoutGlobalScopes()
                ->whereKey((int) $presenceId)
                ->where(function ($q) use ($stationIds) {
                    $q->whereIn('site_id', $stationIds)
                        ->orWhereIn('station_check_in_id', $stationIds)
                        ->orWhereIn('station_check_out_id', $stationIds);
                })
                ->exists();

            if (!$isInManagerStation) {
                abort(403, "Presence hors station du manager.");
            }
        }

        return $next($request);
    }

    private function isPresencePunchRequest(Request $request): bool
    {
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
