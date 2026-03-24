<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupAssignment;
use App\Models\AgentGroupPlanning;
use App\Models\PresenceHoraire;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

class PlanningController extends Controller
{
    /**
     * Résout l'ID du groupe pour un agent.
     */
    private function resolveAgentGroupId($agentOrGroupId)
    {
        $groupId = is_object($agentOrGroupId) ? $agentOrGroupId->groupe_id : $agentOrGroupId;

        if ($groupId && AgentGroup::where('id', $groupId)->exists()) {
            return $groupId;
        }

        $flexibleGroup = AgentGroup::whereNull('horaire_id')->first();
        if ($flexibleGroup) {
            return $flexibleGroup->id;
        }

        return AgentGroup::first()?->id;
    }

    public function getAgentsForStation(Request $request): JsonResponse
    {
        $stationId = $request->query('station_id');
        $agents = Agent::withoutGlobalScopes()
            ->when($stationId, fn($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'matricule', 'site_id', 'groupe_id']);

        return response()->json(['status' => 'success', 'agents' => $agents]);
    }

    public function duplicatePreviousWeek(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
            'current_week_date' => 'required|date',
        ]);

        $currentWeekStart = Carbon::parse($data['current_week_date'])->startOfWeek(Carbon::MONDAY);
        $prevWeekStart = $currentWeekStart->copy()->subWeek();
        $prevWeekEnd = $prevWeekStart->copy()->addDays(6);

        $prevPlannings = AgentGroupPlanning::query()
            ->with('agent')
            ->whereBetween('date', [$prevWeekStart->toDateString(), $prevWeekEnd->toDateString()])
            ->when($request->station_id, function ($q) use ($request) {
                $q->where('site_id', $request->station_id);
            })
            ->get();

        if ($prevPlannings->isEmpty()) {
            return response()->json(['errors' => ['Aucun planning trouvé la semaine passée.']], 422);
        }

        try {
            DB::beginTransaction();
            foreach ($prevPlannings as $p) {
                $targetDate = $currentWeekStart->copy()->addDays(Carbon::parse($p->date)->dayOfWeekIso - 1);
                $siteId = $p->site_id ?? $p->agent?->site_id;

                AgentGroupPlanning::updateOrCreate(
                    [
                        'agent_id' => $p->agent_id,
                        'date' => $targetDate->toDateString(),
                        'site_id' => $siteId
                    ],
                    [
                        'agent_group_id' => $this->resolveAgentGroupId($p->agent_group_id ?? $p->agent),
                        'horaire_id' => $p->horaire_id,
                        'is_rest_day' => $p->is_rest_day,
                    ]
                );
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Planning régénéré.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    public function importWeeklyPlanning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
            'station_id' => 'required|integer|exists:sites,id',
            'start_date' => 'required|date',
        ]);

        $startOfWeek = Carbon::parse($data['start_date'])->startOfWeek(Carbon::MONDAY);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            DB::beginTransaction();
            foreach ($rows as $index => $row) {
                if ($index === 1) continue;
                $matricule = trim((string)($row['A'] ?? ''));
                if (!$matricule) continue;

                $agent = Agent::where('matricule', $matricule)->first();
                if (!$agent) continue;

                // ANCIENNE LOGIQUE : On met à jour la station d'affectation
                $agent->update(['site_id' => $data['station_id']]);

                // ANCIENNE LOGIQUE : On supprime tout le planning de la semaine pour cet agent
                AgentGroupPlanning::where('agent_id', $agent->id)
                    ->whereBetween('date', [$startOfWeek->toDateString(), $startOfWeek->copy()->addDays(6)->toDateString()])
                    ->delete();

                $groupId = $this->resolveAgentGroupId($agent);

                for ($i = 0; $i < 7; $i++) {
                    $cell = trim((string)($row[chr(66 + $i)] ?? ''));
                    $date = $startOfWeek->copy()->addDays($i)->toDateString();
                    $parsed = $this->parsePlanningCell($cell);

                    AgentGroupPlanning::create([
                        'agent_id' => $agent->id,
                        'agent_group_id' => $groupId,
                        'horaire_id' => $parsed['type'] === 'range' ? $this->resolveHoraire($agent, $parsed, $data['station_id'])['id'] : null,
                        'site_id' => $data['station_id'],
                        'date' => $date,
                        'is_rest_day' => $parsed['type'] === 'off',
                    ]);
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Import réussi.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    private function parsePlanningCell($raw) {
        $val = strtoupper(trim($raw));
        if (!$val || in_array($val, ['OFF', 'REPOS'])) return ['type' => 'off'];
        if (preg_match('/(\d{1,2})[:H](\d{2})\s*-\s*(\d{1,2})[:H](\d{2})/', $val, $m)) {
            return ['type' => 'range', 'start' => sprintf('%02d:%02d', $m[1], $m[2]), 'end' => sprintf('%02d:%02d', $m[3], $m[4])];
        }
        return ['type' => 'off'];
    }

    private function resolveHoraire($agent, $p, $siteId = null) {
        $sid = $siteId ?? $agent->site_id;
        return PresenceHoraire::firstOrCreate(
            ['started_at' => $p['start'], 'ended_at' => $p['end'], 'site_id' => $sid],
            ['libelle' => "Shift {$p['start']}-{$p['end']}", 'tolerence_minutes' => 15]
        );
    }

    public function getStationWeeklyPlanning(Request $request): JsonResponse
    {
        $startOfWeek = Carbon::parse($request->date)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        $query = AgentGroupPlanning::query()
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->when($request->station_id, fn($q) => $q->where('site_id', $request->station_id));

        if ($request->exists_only) {
            return response()->json(['status' => 'success', 'exists' => $query->exists()]);
        }

        $plannings = $query->with(['agent.station', 'horaire', 'station'])->get();

        $days = [];
        for ($i=0; $i<7; $i++) {
            $d = $startOfWeek->copy()->addDays($i);
            $days[] = ['date' => $d->toDateString(), 'label' => ucfirst($d->locale('fr')->dayName)];
        }

        $matrix = $plannings->groupBy(function($p) {
            return $p->agent_id . '_' . $p->site_id;
        })->map(function ($ps) use ($days) {
            $first = $ps->first();
            $res = ['agent' => $first->agent, 'site_id' => $first->site_id, 'days' => []];
            foreach ($days as $day) {
                $p = $ps->firstWhere('date', $day['date']);
                $label = 'OFF';
                if ($p && $p->horaire) {
                    $start = Carbon::parse($p->horaire->getRawOriginal('started_at') ?? $p->horaire->started_at)->format('H:i');
                    $end = Carbon::parse($p->horaire->getRawOriginal('ended_at') ?? $p->horaire->ended_at)->format('H:i');
                    $label = "{$start}-{$end}";
                }
                $res['days'][$day['date']] = $p ? [
                    'status' => $p->is_rest_day ? 'off' : 'work',
                    'label' => $label,
                    'station_name' => $p->station?->name,
                    'horaire_id' => $p->horaire_id,
                    'site_id' => $p->site_id
                ] : ['status' => 'none', 'label' => '--'];
            }
            return $res;
        });

        $groups = $matrix->groupBy('site_id')->map(fn($rows, $sid) => [
            'key' => $sid,
            'station_name' => Station::withoutGlobalScopes()->find($sid)?->name ?? 'Sans station',
            'rows' => $rows->values()
        ])->values()->sortBy('station_name')->values();

        return response()->json(['status' => 'success', 'days' => $days, 'stations' => $groups]);
    }

    public function updateAgentWeeklyPlanning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'start_date' => 'required|date',
            'plannings' => 'required|array',
            'plannings.*.date' => 'required|date',
            'plannings.*.horaire_id' => 'nullable|integer|exists:presence_horaires,id',
            'plannings.*.site_id' => 'nullable|integer|exists:sites,id',
            'plannings.*.is_rest_day' => 'required|boolean',
        ]);

        $agent = Agent::withoutGlobalScopes()->findOrFail($data['agent_id']);

        try {
            DB::beginTransaction();
            $groupId = $this->resolveAgentGroupId($agent);

            foreach ($data['plannings'] as $p) {
                $siteId = $p['site_id'] ?? $agent->site_id;

                // On ne supprime que le planning pour CETTE station précise
                AgentGroupPlanning::where('agent_id', $agent->id)
                    ->where('date', $p['date'])
                    ->where('site_id', $siteId)
                    ->delete();

                AgentGroupPlanning::create([
                    'agent_id' => $agent->id,
                    'agent_group_id' => $groupId,
                    'horaire_id' => $p['horaire_id'],
                    'site_id' => $siteId,
                    'date' => $p['date'],
                    'is_rest_day' => (bool) $p['is_rest_day'],
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Mis à jour.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    public function deleteAgentWeeklyPlanning(Request $request): JsonResponse
    {
        $startOfWeek = Carbon::parse($request->start_date)->startOfWeek(Carbon::MONDAY);
        $query = AgentGroupPlanning::where('agent_id', $request->agent_id)
            ->whereBetween('date', [$startOfWeek->toDateString(), $startOfWeek->copy()->addDays(6)->toDateString()]);

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        $query->delete();
        return response()->json(['status' => 'success', 'message' => 'Supprimé.']);
    }
}
