<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Site;
use App\Models\Schedules;
use App\Models\SitePlanningConfig;
use App\Models\Area;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class AppManagerController
 * Gère les configurations globales de l'application Time Attendance.
 */
class AppManagerController extends Controller
{
    /**
     * Calcule la distance entre deux coordonnées GPS (Formule de Haversine).
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2): float|int
    {
        $earthRadius = 6371000;
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $latDiff = $lat2 - $lat1;
        $lngDiff = $lng2 - $lng1;

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos($lat1) * cos($lat2) *
            sin($lngDiff / 2) * sin($lngDiff / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c);
    }

    /**
     * Gère la suppression générique de données dans les tables autorisées.
     */
    public function triggerDelete(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'table' => 'required|string|in:agents,sites,presence_horaires,agent_groups',
                'id' => 'required|int'
            ]);

            $result = DB::table($data['table'])->where('id', $data['id'])->delete();

            return response()->json([
                "status" => "success",
                "result" => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }

    /**
     * Affiche les configurations de plannings automatiques par station.
     */
    public function viewSitePlannings(Request $request): JsonResponse
    {
        $search = $request->query("search");

        $plannings = SitePlanningConfig::with("site")
            ->when($search, function ($query) use ($search) {
                $query->whereHas("site", function ($subQuery) use ($search) {
                    $subQuery->where("name", "LIKE", "%$search%");
                });
            })
            ->orderByDesc('activate')
            ->paginate(10);

        return response()->json([
            "status" => "success",
            "plannings" => $plannings
        ]);
    }

    /**
     * Met à jour la configuration du planning automatique pour une station.
     */
    public function createOrUpdateSiteAutoPlanningConfig(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'site_id' => 'required|int|exists:sites,id',
                'start_hour' => 'required|string',
                'interval' => 'required|int',
                'pause' => 'required|int',
                'number_of_plannings' => 'required|int',
            ]);

            SitePlanningConfig::updateOrCreate(
                ['site_id' => $data['site_id']],
                $data
            );

            return response()->json([
                'status' => 'success',
                'result' => 'Configuration enregistrée avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }
}
