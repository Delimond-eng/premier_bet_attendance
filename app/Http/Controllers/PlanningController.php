<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupPlanning;
use App\Models\PresenceHoraire;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class PlanningController
 * Gère la génération des plannings de rotation pour les agents.
 */
class PlanningController extends Controller
{
    /**
     * Génère le planning pour un groupe d'agents sur un mois donné.
     */
    public function generateMonthlyPlanning(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'group_id' => 'required|exists:agent_groups,id',
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer',
                'rotation_type' => 'required|in:fixed,alternating', // Fixe ou alterné (ex: 2j jour, 2j nuit, 2j repos)
            ]);

            $group = AgentGroup::with('horaire')->findOrFail($data['group_id']);
            $agents = Agent::where('groupe_id', $group->id)->get();

            $startDate = Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            DB::beginTransaction();

            foreach ($agents as $agent) {
                // Nettoyage du planning existant pour ce mois
                AgentGroupPlanning::where('agent_id', $agent->id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->delete();

                $currentDate = $startDate->copy();

                while ($currentDate->lte($endDate)) {
                    // Logique simplifiée : Assignation de l'horaire par défaut du groupe
                    // On peut complexifier ici pour gérer les cycles (ex: 4x4, 2x12, etc.)
                    AgentGroupPlanning::create([
                        'agent_id' => $agent->id,
                        'agent_group_id' => $group->id,
                        'horaire_id' => $group->horaire_id,
                        'date' => $currentDate->toDateString(),
                        'is_rest_day' => $currentDate->isWeekend(), // Exemple : repos le weekend
                    ]);

                    $currentDate->addDay();
                }
            }

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Planning généré avec succès pour " . count($agents) . " agents."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Récupère le planning d'une station pour une semaine.
     */
    public function getStationWeeklyPlanning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'required|integer|exists:sites,id',
            'date' => 'nullable|date',
        ]);

        $base = !empty($data['date']) ? Carbon::parse($data['date']) : Carbon::now();
        $startOfWeek = $base->copy()->startOfWeek();
        $endOfWeek = $base->copy()->endOfWeek();

        $plannings = AgentGroupPlanning::query()
            ->with(['agent', 'horaire'])
            ->whereHas('agent', fn ($q) => $q->where('site_id', $data['station_id']))
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->get();

        $agents = $plannings->pluck('agent')->filter()->unique('id')->values();

        $days = [];
        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'label' => $cursor->translatedFormat('D'),
            ];
            $cursor->addDay();
        }

        $matrix = [];
        foreach ($agents as $agent) {
            $row = [
                'agent' => $agent,
                'days' => [],
            ];

            foreach ($days as $day) {
                $entry = $plannings
                    ->first(fn ($p) => (int) $p->agent_id === (int) $agent->id && $p->date === $day['date']);

                if (!$entry) {
                    $row['days'][$day['date']] = ['status' => 'unknown', 'label' => '--'];
                    continue;
                }

                if ($entry->is_rest_day) {
                    $row['days'][$day['date']] = ['status' => 'off', 'label' => 'OFF'];
                    continue;
                }

                $label = $entry->horaire
                    ? ($entry->horaire->started_at . ' - ' . $entry->horaire->ended_at)
                    : '--';

                $row['days'][$day['date']] = ['status' => 'work', 'label' => $label];
            }

            $matrix[] = $row;
        }

        return response()->json([
            'status' => 'success',
            'from' => $startOfWeek->toDateString(),
            'to' => $endOfWeek->toDateString(),
            'days' => $days,
            'data' => $matrix,
        ]);
    }
}
