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
        $stationId = $request->query('station_id');
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $planning = AgentGroupPlanning::with(['agent', 'horaire'])
            ->whereHas('agent', function($q) use ($stationId) {
                $q->where('site_id', $stationId);
            })
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->get()
            ->groupBy('date');

        return response()->json([
            "status" => "success",
            "data" => $planning
        ]);
    }
}
