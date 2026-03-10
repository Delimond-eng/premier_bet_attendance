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

        if ($user->station_id === null || $user->station_id === '') {
            return $next($request);
        }

        $stationId = (int) $user->station_id;

        // Force station-scoped filters for manager requests.
        foreach (['station_id', 'site_id'] as $key) {
            $request->request->set($key, $stationId);
            $request->query->set($key, $stationId);
        }

        $agentId = $request->input('agent_id');
        if ($agentId !== null && $agentId !== '') {
            $isInManagerStation = Agent::withoutGlobalScopes()
                ->whereKey((int) $agentId)
                ->where('site_id', $stationId)
                ->exists();

            if (!$isInManagerStation) {
                abort(403, "Agent hors station du manager.");
            }
        }

        $presenceId = $request->input('presence_agent_id');
        if ($presenceId !== null && $presenceId !== '') {
            $isInManagerStation = PresenceAgents::withoutGlobalScopes()
                ->whereKey((int) $presenceId)
                ->where(function ($q) use ($stationId) {
                    $q->where('site_id', $stationId)
                        ->orWhere('station_check_in_id', $stationId)
                        ->orWhere('station_check_out_id', $stationId);
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
