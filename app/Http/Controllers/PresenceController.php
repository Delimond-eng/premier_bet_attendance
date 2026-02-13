<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupPlanning;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Services\AttendanceReportService;
use App\Services\AbsenceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        // Default schedule from agent's group (if any)
        if ($agent->groupe_id) {
            $group = AgentGroup::query()->with('horaire')->find($agent->groupe_id);
            if ($group?->horaire_id) {
                return $group->horaire ?? PresenceHoraire::find($group->horaire_id);
            }
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
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $date = $data['date'] ?? Carbon::today()->toDateString();
        $stationId = $data['station_id'] ?? null;

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where('site_id', (int) $stationId);
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
        $data = $request->validate([
            'site_id' => 'nullable|integer|exists:sites,id',
        ]);

        $siteId = $data['site_id'] ?? null;
        $horaires = PresenceHoraire::query()
            ->when($siteId !== null, fn ($q) => $q->where('site_id', (int) $siteId))
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
    public function monthlyReport(Request $request, AttendanceReportService $service): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);

        $filters = [
            'station_id' => $data['station_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'group_id' => $data['group_id'] ?? null,
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
                            'station_id' => $a->site_id,
                            'station_name' => $a->station?->name,
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

    /**
     * Rapport des absences (journalier / période) avec justificatifs (congé/autorisation/justification).
     *
     * L'agent est considéré "absent" s'il n'a pas de pointage started_at sur la date de référence.
     * Les justificatifs n'annulent pas l'absence, ils sont affichés en colonne.
     */
    public function dailyAbsenceReport(Request $request, AbsenceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'per_page' => 'nullable|integer|min:1|max:2000',
            'page' => 'nullable|integer|min:1',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $base->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $base->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $stationId = $data['station_id'] ?? null;
        $rows = $service->buildAbsenceRows($start, $end, $stationId ? (int) $stationId : null);

        $perPage = (int) ($data['per_page'] ?? 500);
        $page = (int) ($data['page'] ?? 1);
        $total = count($rows);
        $slice = array_slice($rows, max(($page - 1) * $perPage, 0), $perPage);
        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'absences' => $paginator,
        ]);
    }

    public function weeklyReport(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $baseDate->copy()->startOfWeek();
        $end = $baseDate->copy()->endOfWeek();

        $filters = [
            'station_id' => isset($data['station_id']) ? (int) $data['station_id'] : null,
        ];

        $matrix = $service->buildWeeklyMatrix($baseDate, $filters);

        return response()->json([
            'status' => 'success',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
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
                            'station_id' => $a->site_id,
                            'station_name' => $a->station?->name,
                        ],
                    ];
                }),
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
            'per_page' => 'nullable|integer|min:1|max:500',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->where('agent_id', $data['agent_id'])
            ->when(!empty($data['from']), fn ($q) => $q->whereDate('date_reference', '>=', $data['from']))
            ->when(!empty($data['to']), fn ($q) => $q->whereDate('date_reference', '<=', $data['to']))
            ->when(!empty($data['station_id']), function ($q) use ($data) {
                $stationId = (int) $data['station_id'];
                $q->where(function ($qq) use ($stationId) {
                    $qq->where('site_id', $stationId)
                        ->orWhere('station_check_in_id', $stationId)
                        ->orWhere('station_check_out_id', $stationId);
                });
            })
            ->orderByDesc('date_reference')
            ->orderByDesc('started_at');

        $perPage = (int) ($data['per_page'] ?? 15);

        $page = $query->paginate($perPage);
        $page->getCollection()->transform(function (PresenceAgents $p) {
            // Keep original fields (incl. formatted casts) but add ISO values for front-end logic.
            $p->date_reference_iso = $p->getRawOriginal('date_reference');
            $p->started_at_raw = $p->getRawOriginal('started_at');
            $p->ended_at_raw = $p->getRawOriginal('ended_at');
            return $p;
        });

        return response()->json([
            'status' => 'success',
            'history' => $page,
        ]);
    }

    /**
     * RÃ©sumÃ© "agent_attendance": profil, station, horaire du jour + stats (journalier/mensuel).
     */
    public function agentAttendanceSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'hours_date' => 'nullable|date',
            'month' => 'nullable|date_format:Y-m',
        ]);

        $now = Carbon::now()->setTimezone('Africa/Kinshasa');

        $agent = Agent::with(['station', 'horaire', 'groupe.horaire'])->findOrFail((int) $data['agent_id']);

        $horaire = $this->getHoraireForAgent($agent, $now);
        $dateForHours = !empty($data['hours_date']) ? Carbon::parse($data['hours_date']) : $now->copy();
        $dateForHours = $dateForHours->setTimezone('Africa/Kinshasa');
        $dateForHoursWithTime = $dateForHours->copy()->setTime((int) $now->hour, (int) $now->minute, (int) $now->second);
        $dateReference = $horaire ? $this->getDateReference($dateForHoursWithTime, $horaire) : $dateForHours->copy()->startOfDay();
        $dateReferenceString = $dateReference->toDateString();

        $monthBase = !empty($data['month'])
            ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth()
            : $now->copy()->startOfMonth();
        $monthStart = $monthBase->copy()->startOfMonth()->toDateString();
        $monthEnd = $monthBase->copy()->endOfMonth()->toDateString();

        $dailyRows = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReferenceString)
            ->get();

        $totalMinutes = 0;
        foreach ($dailyRows as $row) {
            $rawStart = $row->getRawOriginal('started_at');
            if (!$rawStart) {
                continue;
            }
            $start = Carbon::parse($rawStart);
            $rawEnd = $row->getRawOriginal('ended_at');
            $end = $rawEnd ? Carbon::parse($rawEnd) : $now;
            $diff = $start->diffInMinutes($end, false);
            if ($diff > 0) {
                $totalMinutes += $diff;
            }
        }

        $presenceDaysMonthly = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereBetween('date_reference', [$monthStart, $monthEnd])
            ->whereNotNull('started_at')
            ->count();

        $lateDaysMonthly = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereBetween('date_reference', [$monthStart, $monthEnd])
            ->where('retard', 'oui')
            ->count();

        $isOnLeave = Conge::query()
            ->where('agent_id', $agent->id)
            ->where('status', 'approved')
            ->whereDate('date_debut', '<=', $now->toDateString())
            ->whereDate('date_fin', '>=', $now->toDateString())
            ->exists();

        $hasPresenceToday = PresenceAgents::query()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $dateReferenceString)
            ->whereNotNull('started_at')
            ->exists();

        $todayStatus = $isOnLeave ? 'conge' : ($hasPresenceToday ? 'present' : 'absent');

        $expectedStart = $horaire ? (string) $horaire->getRawOriginal('started_at') : null;
        $expectedEnd = $horaire ? (string) $horaire->getRawOriginal('ended_at') : null;
        $expectedStart = $expectedStart ? substr($expectedStart, 0, 5) : null;
        $expectedEnd = $expectedEnd ? substr($expectedEnd, 0, 5) : null;

        return response()->json([
            'status' => 'success',
            'agent' => [
                'id' => $agent->id,
                'fullname' => $agent->fullname,
                'matricule' => $agent->matricule,
                'photo' => $agent->photo,
                'station' => $agent->station ? ['id' => $agent->station->id, 'name' => $agent->station->name] : null,
            ],
            'schedule' => $horaire ? [
                'id' => $horaire->id,
                'name' => $horaire->libelle,
                'expected_start' => $expectedStart,
                'expected_end' => $expectedEnd,
                'tolerance_minutes' => $horaire->tolerence_minutes,
            ] : null,
            'today_status' => $todayStatus,
            'periods' => [
                'daily_date_reference' => $dateReferenceString,
                'monthly_from' => $monthStart,
                'monthly_to' => $monthEnd,
            ],
            'stats' => [
                'total_hours_daily' => round($totalMinutes / 60, 1),
                'presences_monthly' => (int) $presenceDaysMonthly,
                'retards_monthly' => (int) $lateDaysMonthly,
            ],
        ]);
    }

    /**
     * Scan d'un QR code station et retour des donnÃ©es station.
     */
    public function scanStation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'required|integer|exists:sites,id',
        ]);

        $station = Station::query()->find((int) $data['station_id']);
        if (!$station) {
            return response()->json(['errors' => ['Station introuvable.']], 404);
        }

        return response()->json([
            'status' => 'success',
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'latlng' => $station->latlng,
                'adresse' => $station->adresse,
                'presence' => $station->presence,
                'status' => $station->status,
            ],
        ]);
    }

    /**
     * API pointage agent.
     * Le matricule est considÃ©rÃ© comme identifiant unique (voir createPresenceAgent).
     */
    public function punchAgent(Request $request): JsonResponse
    {
        return $this->createPresenceAgent($request);
    }
}
