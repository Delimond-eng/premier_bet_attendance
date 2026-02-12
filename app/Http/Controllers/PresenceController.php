<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupPlanning;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Services\AttendanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresenceController extends Controller
{
    /**
     * Enregistre un pointage (check-in / check-out).
     *
     * Règles:
     * - Chaque check-in et check-out doit être lié à une station.
     * - La présence conserve la station d’affectation (site_id) au moment du pointage.
     * - Plus de logique photo (champ ignoré si envoyé).
     */
    public function createPresenceAgent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'matricule' => 'required|string|exists:agents,matricule',
            'key' => 'required|string|in:check-in,check-out',
            'station_id' => 'nullable|integer|exists:sites,id',
            'coordonnees' => 'nullable|string', // "lat,lng" (mobile)
        ]);

        $now = Carbon::now()->setTimezone('Africa/Kinshasa');

        $agent = Agent::with(['station', 'horaire', 'groupe'])->where('matricule', $data['matricule'])->firstOrFail();
        $assignedStationId = $agent->site_id;

        $stationId = $this->resolveStationId(
            stationId: $data['station_id'] ?? null,
            coordonnees: $data['coordonnees'] ?? null,
            fallbackAssignedStationId: $assignedStationId,
        );

        if (!$stationId) {
            return response()->json(['errors' => ['Station introuvable pour ce pointage.']], 422);
        }

        $horaire = $this->getHoraireForAgent($agent, $now);
        $dateReference = $horaire ? $this->getDateReference($now, $horaire) : $now->copy()->startOfDay();

        try {
            return DB::transaction(function () use ($data, $agent, $assignedStationId, $stationId, $horaire, $dateReference, $now) {
                if ($data['key'] === 'check-in') {
                    return $this->handleCheckIn($agent, $assignedStationId, $stationId, $horaire, $dateReference, $now);
                }

                return $this->handleCheckOut($agent, $stationId, $now);
            });
        } catch (\Throwable $e) {
            Log::error('createPresenceAgent failed', [
                'error' => $e->getMessage(),
                'agent_id' => $agent->id ?? null,
            ]);

            return response()->json(['errors' => ['Erreur interne lors du pointage.']], 500);
        }
    }

    private function handleCheckIn(Agent $agent, ?int $assignedStationId, int $stationId, ?PresenceHoraire $horaire, Carbon $dateReference, Carbon $now): JsonResponse
    {
        $existing = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReference->toDateString())
            ->first();

        if ($existing && $existing->started_at) {
            return response()->json(['errors' => ['Pointage d’entrée déjà effectué pour cette période.']], 422);
        }

        $retard = 'non';
        if ($horaire) {
            $heureRef = $dateReference->copy()->setTimeFromTimeString($horaire->started_at);
            $toleranceMinutes = (int) ($horaire->tolerence_minutes ?? 15);
            if ($now->gt($heureRef->copy()->addMinutes($toleranceMinutes))) {
                $retard = 'oui';
            }
        }

        $presence = PresenceAgents::create([
            'agent_id' => $agent->id,
            'site_id' => $assignedStationId, // station d'affectation (référence/contrôle)
            'gps_site_id' => $stationId, // legacy
            'station_check_in_id' => $stationId,
            'horaire_id' => $horaire?->id,
            'date_reference' => $dateReference->toDateString(),
            'started_at' => $now,
            'retard' => $retard,
            'status' => 'arrive',
        ]);

        $presence->load(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Entrée enregistrée.',
            'result' => $presence,
        ]);
    }

    private function handleCheckOut(Agent $agent, int $stationId, Carbon $now): JsonResponse
    {
        $presence = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if (!$presence) {
            return response()->json(['errors' => ['Aucun pointage d’entrée ouvert trouvé.']], 422);
        }

        $startedAt = Carbon::parse($presence->started_at);
        $dureeMinutes = $startedAt->diffInMinutes($now);
        $dureeFormat = $this->formatDuration($dureeMinutes);

        $presence->update([
            'ended_at' => $now,
            'duree' => $dureeFormat,
            'station_check_out_id' => $stationId,
            'status' => 'depart',
        ]);

        $presence->load(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation']);

        return response()->json([
            'status' => 'success',
            'message' => 'Sortie enregistrée.',
            'result' => $presence,
        ]);
    }

    private function resolveStationId(?int $stationId, ?string $coordonnees, ?int $fallbackAssignedStationId): ?int
    {
        if ($stationId) {
            return $stationId;
        }

        if ($coordonnees) {
            $nearest = $this->findNearestStation($coordonnees);
            if ($nearest) {
                return $nearest->id;
            }
        }

        return $fallbackAssignedStationId;
    }

    private function findNearestStation(string $coords): ?Station
    {
        $parts = array_map('trim', explode(',', $coords));
        if (count($parts) !== 2) {
            return null;
        }
        [$lat1, $lng1] = $parts;
        if (!is_numeric($lat1) || !is_numeric($lng1)) {
            return null;
        }

        $stations = Station::query()->whereNotNull('latlng')->get();
        $appManager = new AppManagerController();

        return $stations
            ->map(function (Station $station) use ($lat1, $lng1, $appManager) {
                $coords = array_map('trim', explode(',', (string) $station->latlng));
                if (count($coords) !== 2) {
                    return null;
                }
                [$lat2, $lng2] = $coords;
                if (!is_numeric($lat2) || !is_numeric($lng2)) {
                    return null;
                }
                $station->distance = $appManager->calculateDistance($lat1, $lng1, $lat2, $lng2);
                return $station;
            })
            ->filter(fn ($s) => $s && isset($s->distance) && $s->distance <= 500)
            ->sortBy('distance')
            ->first();
    }

    private function getHoraireForAgent(Agent $agent, Carbon $now): ?PresenceHoraire
    {
        $date = $now->toDateString();

        $planning = AgentGroupPlanning::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date', $date)
            ->where('is_rest_day', false)
            ->first();

        if ($planning?->horaire_id) {
            return PresenceHoraire::find($planning->horaire_id);
        }

        if ($agent->horaire_id) {
            return PresenceHoraire::find($agent->horaire_id);
        }

        if ($agent->site_id) {
            return PresenceHoraire::query()->where('site_id', $agent->site_id)->orderBy('started_at')->first();
        }

        return null;
    }

    private function getDateReference(Carbon $now, PresenceHoraire $horaire): Carbon
    {
        $heureDebut = Carbon::createFromTimeString($horaire->started_at);
        $heureFin = Carbon::createFromTimeString($horaire->ended_at);
        $dateReference = $now->copy()->startOfDay();

        if ($heureFin->lt($heureDebut)) {
            $limiteFin = $now->copy()->startOfDay()->setTimeFromTimeString($horaire->ended_at);
            if ($now->lt($limiteFin)) {
                $dateReference = $now->copy()->subDay()->startOfDay();
            }
        }

        return $dateReference;
    }

    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . $mins . 'min';
    }

    /**
     * Récupère la liste des pointages pour une station et une date.
     * Compatible avec le flow existant (station_id filtre sur la station d'affectation).
     */
    public function getPresencesBySiteAndDate(Request $request): JsonResponse
    {
        $date = $request->query('date') ?? Carbon::today()->toDateString();
        $stationId = $request->query('station_id');

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId) {
            $query->where('site_id', $stationId);
        }

        return response()->json([
            'status' => 'success',
            'presences' => $query
                ->orderByDesc('date_reference')
                ->orderByDesc('started_at')
                ->get(),
        ]);
    }

    public function getAllHoraires(Request $request): JsonResponse
    {
        $siteId = $request->query('site_id');
        $horaires = PresenceHoraire::query()
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->orderBy('site_id')
            ->orderBy('started_at')
            ->get();

        return response()->json(['status' => 'success', 'horaires' => $horaires]);
    }

    public function createHoraire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'libelle' => 'required|string',
            'started_at' => 'required|string',
            'ended_at' => 'required|string',
            'tolerence_minutes' => 'nullable|integer|min:0',
            'site_id' => 'required|integer|exists:sites,id',
        ]);

        $horaire = PresenceHoraire::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json(['status' => 'success', 'result' => $horaire]);
    }

    public function createGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:agent_groups,id',
            'libelle' => 'required|string',
            'horaire_id' => 'nullable|integer|exists:presence_horaires,id',
            'cycle_days' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:actif,inactif',
        ]);

        $group = AgentGroup::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json(['status' => 'success', 'result' => $group]);
    }

    public function getAllGroups(Request $request): JsonResponse
    {
        $groups = AgentGroup::query()
            ->with('horaire')
            ->withCount('plannings')
            ->orderBy('libelle')
            ->get();

        return response()->json(['status' => 'success', 'groups' => $groups]);
    }

    public function countDashboard(Request $request): JsonResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $stationId = $request->query('station_id');

        $totalAgents = Agent::query()
            ->when($stationId, fn ($q) => $q->where('site_id', $stationId))
            ->count();

        $presentAgents = PresenceAgents::query()
            ->whereDate('date_reference', $date->toDateString())
            ->when($stationId, fn ($q) => $q->where('site_id', $stationId))
            ->whereNotNull('started_at')
            ->count();

        $lateAgents = PresenceAgents::query()
            ->whereDate('date_reference', $date->toDateString())
            ->when($stationId, fn ($q) => $q->where('site_id', $stationId))
            ->where('retard', 'oui')
            ->count();

        $absentAgents = max($totalAgents - $presentAgents, 0);

        return response()->json([
            'status' => 'success',
            'count' => [
                'agents' => $totalAgents,
                'presences' => $presentAgents,
                'retards' => $lateAgents,
                'absents' => $absentAgents,
            ],
        ]);
    }

    /**
     * Rapport mensuel (JSON + PDF si export=pdf).
     */
    public function monthlyReport(Request $request, AttendanceReportService $service): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $filters = [
            'station_id' => $request->query('station_id'),
            'agent_id' => $request->query('agent_id'),
            'group_id' => $request->query('group_id'),
        ];

        $matrix = $service->buildMonthlyMatrix($month, $year, $filters);

        if ($request->query('export') === 'pdf') {
            $pdf = Pdf::loadView('pdf.reports.monthly_report', [
                'data' => $matrix['data'],
                'mois' => $month,
                'annee' => $year,
            ])->setPaper('a4', 'portrait');

            return $pdf->download("rapport_mensuel_{$month}_{$year}.pdf");
        }

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'data' => $matrix['data'],
            'agents' => $matrix['agents']
                ->mapWithKeys(function (Agent $a) {
                    $key = $a->fullname . ' (' . $a->matricule . ')';
                    return [
                        $key => [
                            'id' => $a->id,
                            'fullname' => $a->fullname,
                            'matricule' => $a->matricule,
                            'photo' => $a->photo,
                        ],
                    ];
                }),
        ]);
    }

    /**
     * Endpoint mobile legacy (superviseur). Non couvert dans le périmètre "attendance agents".
     */
    public function createSupervisorSiteVisit(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'errors' => ['Fonctionnalité superviseur non disponible sur ce backend.'],
        ], 501);
    }

    public function dailyReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();

        $agentsQuery = Agent::query()
            ->when(!empty($data['station_id']), fn ($q) => $q->where('site_id', $data['station_id']))
            ->when(!empty($data['agent_id']), fn ($q) => $q->where('id', $data['agent_id']))
            ->when(!empty($data['group_id']), fn ($q) => $q->where('groupe_id', $data['group_id']));

        $totalAgents = $agentsQuery->count();
        $agentIds = $agentsQuery->pluck('id')->all();

        $presencesQuery = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date)
            ->whereIn('agent_id', $agentIds);

        $present = (clone $presencesQuery)->whereNotNull('started_at')->count();
        $late = (clone $presencesQuery)->where('retard', 'oui')->count();
        $absent = max($totalAgents - $present, 0);

        $perPage = (int) ($data['per_page'] ?? 25);

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'count' => [
                'agents' => $totalAgents,
                'presences' => $present,
                'retards' => $late,
                'absents' => $absent,
            ],
            'presences' => $presencesQuery
                ->orderByDesc('date_reference')
                ->orderByDesc('started_at')
                ->paginate($perPage),
        ]);
    }

    public function weeklyReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $baseDate->copy()->startOfWeek();
        $end = $baseDate->copy()->endOfWeek();

        $agentsQuery = Agent::query()
            ->when(!empty($data['station_id']), fn ($q) => $q->where('site_id', $data['station_id']))
            ->when(!empty($data['agent_id']), fn ($q) => $q->where('id', $data['agent_id']))
            ->when(!empty($data['group_id']), fn ($q) => $q->where('groupe_id', $data['group_id']));

        $totalAgents = $agentsQuery->count();
        $agentIds = $agentsQuery->pluck('id')->all();

        $presencesAll = PresenceAgents::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (PresenceAgents $p) => Carbon::parse($p->date_reference)->toDateString());

        $daily = [];
        $cursor = $end->copy();
        while ($cursor->gte($start)) {
            $d = $cursor->toDateString();
            $dayPresences = $presencesAll->get($d, collect());
            $present = (int) $dayPresences->whereNotNull('started_at')->count();
            $late = (int) $dayPresences->where('retard', 'oui')->count();
            $absent = max($totalAgents - $present, 0);
            $daily[] = [
                'date' => $d,
                'count' => [
                    'agents' => $totalAgents,
                    'presences' => $present,
                    'retards' => $late,
                    'absents' => $absent,
                ],
            ];
            $cursor->subDay();
        }

        $perPage = (int) ($data['per_page'] ?? 200);

        $presencesQuery = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()]);

        $daysCount = max($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1, 1);

        $agentsByStation = Agent::query()
            ->selectRaw('site_id, COUNT(*) as c')
            ->whereIn('id', $agentIds)
            ->groupBy('site_id')
            ->pluck('c', 'site_id');

        $presenceByStation = PresenceAgents::query()
            ->selectRaw('site_id, SUM(CASE WHEN started_at IS NOT NULL THEN 1 ELSE 0 END) as c')
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->groupBy('site_id')
            ->pluck('c', 'site_id');

        $lateByStation = PresenceAgents::query()
            ->selectRaw('site_id, SUM(CASE WHEN retard = \'oui\' THEN 1 ELSE 0 END) as c')
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->groupBy('site_id')
            ->pluck('c', 'site_id');

        $stationStats = Station::query()
            ->whereIn('id', $agentsByStation->keys()->filter()->all())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Station $s) use ($agentsByStation, $presenceByStation, $lateByStation, $daysCount) {
                $agents = (int) ($agentsByStation[$s->id] ?? 0);
                $presences = (int) ($presenceByStation[$s->id] ?? 0);
                $retards = (int) ($lateByStation[$s->id] ?? 0);
                $expected = $agents * $daysCount;
                return [
                    'station_id' => $s->id,
                    'station_name' => $s->name,
                    'agents' => $agents,
                    'presences' => $presences,
                    'retards' => $retards,
                    'absents' => max($expected - $presences, 0),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'daily' => $daily,
            // Détail par agent/station sur la période (pour un rapport cohérent avec les autres vues).
            'station_stats' => $stationStats,
            'presences' => $presencesQuery
                ->orderByDesc('date_reference')
                ->orderByDesc('started_at')
                ->paginate($perPage),
        ]);
    }

    /**
     * Historique détaillé des pointages d'un agent (stations check-in/out + affectation).
     */
    public function agentHistory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->where('agent_id', $data['agent_id'])
            ->when(!empty($data['from']), fn ($q) => $q->whereDate('date_reference', '>=', $data['from']))
            ->when(!empty($data['to']), fn ($q) => $q->whereDate('date_reference', '<=', $data['to']))
            ->orderByDesc('date_reference')
            ->orderByDesc('started_at');

        $perPage = (int) ($data['per_page'] ?? 15);

        return response()->json([
            'status' => 'success',
            'history' => $query->paginate($perPage),
        ]);
    }
}
