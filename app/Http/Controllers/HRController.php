<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupAssignment;
use App\Models\AgentGroupPlanning;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\CongeType;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Services\AttendanceReportService;
use App\Support\ManagerStationContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HRController extends Controller
{
    public function manualAttendanceAgents(Request $request): JsonResponse
    {
        $this->ensureElectrocoolManualAttendance();
        $stationIds = ManagerStationContext::stationIds();
        $prefix = ManagerStationContext::matriculePrefix();

        $agents = Agent::withoutGlobalScopes()
            ->when($stationIds !== null, fn ($query) => $query->whereIn('site_id', $stationIds))
            ->when($prefix !== null, fn ($query) => $query->where('matricule', 'like', $prefix . '%'))
            ->orderBy('fullname')
            ->get(['id', 'fullname', 'matricule']);

        return response()->json([
            'results' => $agents->map(fn (Agent $agent) => [
                'id' => $agent->id,
                'text' => $agent->fullname . ' (' . $agent->matricule . ')',
                'fullname' => $agent->fullname,
                'matricule' => $agent->matricule,
            ])->values(),
        ]);
    }

    public function manualAttendanceMonth(Request $request): JsonResponse
    {
        $this->ensureElectrocoolManualAttendance();
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ]);

        $agent = $this->findManualAttendanceAgent((int) $data['agent_id']);
        $start = Carbon::createFromFormat('Y-m-d', $data['from'], 'Africa/Kinshasa')->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $data['to'], 'Africa/Kinshasa')->startOfDay();
        $today = Carbon::now('Africa/Kinshasa')->startOfDay();
        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $schedule = $this->manualAttendanceSchedule($agent, $date);
            $presence = PresenceAgents::withoutGlobalScopes()
                ->where('agent_id', $agent->id)
                ->whereDate('date_reference', $date)
                ->orderByDesc('id')
                ->first();
            $completed = PresenceAgents::withoutGlobalScopes()
                ->where('agent_id', $agent->id)
                ->whereDate('date_reference', $date)
                ->whereNotNull('started_at')
                ->whereNotNull('ended_at')
                ->exists();

            $days[] = [
                'date' => $date,
                'weekday' => $day->locale('fr')->translatedFormat('l'),
                'future' => $day->gt($today),
                'completed' => $completed,
                'has_entry' => $presence && $presence->started_at !== null,
                'started_at' => $presence?->getRawOriginal('started_at')
                    ? Carbon::parse($presence->getRawOriginal('started_at'))->setTimezone('Africa/Kinshasa')->format('H:i')
                    : null,
                'ended_at' => $presence?->getRawOriginal('ended_at')
                    ? Carbon::parse($presence->getRawOriginal('ended_at'))->setTimezone('Africa/Kinshasa')->format('H:i')
                    : null,
                'horaire' => $schedule['horaire']?->libelle ?? ($schedule['rest'] ? 'Repos prévu' : 'Horaire à définir'),
                'default_started_at' => $schedule['horaire']?->getRawOriginal('started_at')
                    ? substr((string) $schedule['horaire']->getRawOriginal('started_at'), 0, 5)
                    : null,
                'default_ended_at' => $schedule['horaire']?->getRawOriginal('ended_at')
                    ? substr((string) $schedule['horaire']->getRawOriginal('ended_at'), 0, 5)
                    : null,
                'manual' => (bool) ($presence?->is_manual ?? false),
            ];
        }

        return response()->json([
            'agent' => ['id' => $agent->id, 'fullname' => $agent->fullname, 'matricule' => $agent->matricule],
            'days' => $days,
        ]);
    }

    public function manualAttendanceStore(Request $request): JsonResponse
    {
        $this->ensureElectrocoolManualAttendance();
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'date' => 'required|date_format:Y-m-d',
            'started_at' => 'nullable|date_format:H:i',
            'ended_at' => 'nullable|date_format:H:i',
        ]);

        $agent = $this->findManualAttendanceAgent((int) $data['agent_id']);
        $date = Carbon::parse($data['date'], 'Africa/Kinshasa')->startOfDay();
        abort_if($date->gt(Carbon::now('Africa/Kinshasa')->startOfDay()), 422, 'Les dates futures ne peuvent pas être pointées.');

        $query = PresenceAgents::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->whereDate('date_reference', $date->toDateString())
            ->orderByDesc('id');
        $presence = (clone $query)->whereNotNull('started_at')->whereNull('ended_at')->first();
        $hasCompletedPresence = (clone $query)->whereNotNull('started_at')->whereNotNull('ended_at')->exists();

        abort_if($hasCompletedPresence, 422, 'Le pointage de cette journée est déjà complet.');
        if (!$presence && empty($data['started_at'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'started_at' => 'L’heure d’entrée est requise pour créer un pointage.',
            ]);
        }

        $horaireData = $this->manualAttendanceSchedule($agent, $date->toDateString());
        $horaire = $horaireData['horaire'];
        $siteId = $horaireData['site_id'] ?: $agent->site_id;
        abort_if(!$siteId, 422, 'Aucune station n’est définie pour cet agent.');

        $startedAt = $presence?->getRawOriginal('started_at')
            ? Carbon::parse($presence->getRawOriginal('started_at'))->setTimezone('Africa/Kinshasa')
            : null;
        if (!$presence && !empty($data['started_at'])) {
            $startedAt = $date->copy()->setTimeFromTimeString($data['started_at']);
        }
        if (!empty($data['ended_at']) && !$startedAt) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'started_at' => 'Une heure d’entrée est nécessaire avant l’heure de sortie.',
            ]);
        }

        $endedAt = null;
        if (!empty($data['ended_at'])) {
            $endedAt = $date->copy()->setTimeFromTimeString($data['ended_at']);
            if ($endedAt->lt($startedAt)) {
                $endedAt->addDay();
            }
        }

        $retard = 'non';
        if ($startedAt && $horaire?->getRawOriginal('started_at')) {
            $scheduledStart = $date->copy()->setTimeFromTimeString((string) $horaire->getRawOriginal('started_at'));
            $tolerance = (int) ($horaire->tolerence_minutes ?? 15);
            $retard = $startedAt->gt($scheduledStart->addMinutes($tolerance)) ? 'oui' : 'non';
        }

        $presence ??= new PresenceAgents([
            'agent_id' => $agent->id,
            'site_id' => $siteId,
            'date_reference' => $date->toDateString(),
            'status' => 'arrive',
        ]);
        $presence->fill([
            'site_id' => $siteId,
            'horaire_id' => $horaire?->id,
            'started_at' => $startedAt?->toDateTimeString(),
            'ended_at' => $endedAt?->toDateTimeString(),
            'retard' => $retard,
            'status' => $endedAt ? 'depart' : 'arrive',
            'is_manual' => true,
            'manual_recorded_by' => auth()->id(),
        ])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Pointage manuel enregistré.',
            'presence' => $presence,
        ]);
    }

    private function ensureElectrocoolManualAttendance(): void
    {
        abort_unless(strtolower((string) request()->user()?->role) === 'admin', 403);

        $host = strtolower(request()->getHost());
        abort_unless(
            str_contains($host, 'electrocool') || in_array($host, ['localhost', '127.0.0.1'], true),
            404
        );
    }

    private function findManualAttendanceAgent(int $agentId): Agent
    {
        $stationIds = ManagerStationContext::stationIds();
        $prefix = ManagerStationContext::matriculePrefix();

        return Agent::withoutGlobalScopes()
            ->when($stationIds !== null, fn ($query) => $query->whereIn('site_id', $stationIds))
            ->when($prefix !== null, fn ($query) => $query->where('matricule', 'like', $prefix . '%'))
            ->findOrFail($agentId);
    }

    private function manualAttendanceSchedule(Agent $agent, string $date): array
    {
        $assignment = AgentGroupAssignment::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->whereDate('start_date', '<=', $date)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->orderByDesc('start_date')
            ->first();
        $groupId = $assignment?->agent_group_id ?: $agent->groupe_id;
        $group = $groupId ? AgentGroup::withoutGlobalScopes()->find($groupId) : null;

        $planning = AgentGroupPlanning::withoutGlobalScopes()
            ->where('agent_id', $agent->id)
            ->when($groupId, fn ($query) => $query->where('agent_group_id', $groupId))
            ->whereDate('date', $date)
            ->where(function ($query) use ($agent) {
                $query->whereNull('site_id')->orWhere('site_id', $agent->site_id);
            })
            ->orderByRaw('CASE WHEN site_id = ? THEN 0 WHEN site_id IS NULL THEN 1 ELSE 2 END', [$agent->site_id])
            ->first();

        if ($planning) {
            return [
                'horaire' => $planning->horaire_id ? PresenceHoraire::withoutGlobalScopes()->find($planning->horaire_id) : null,
                'rest' => (bool) $planning->is_rest_day,
                'site_id' => $planning->site_id,
            ];
        }

        $horaire = $group?->horaire_id
            ? PresenceHoraire::withoutGlobalScopes()->find($group->horaire_id)
            : null;
        $horaire ??= $agent->horaire_id
            ? PresenceHoraire::withoutGlobalScopes()->find($agent->horaire_id)
            : null;
        $horaire ??= $agent->site_id
            ? PresenceHoraire::withoutGlobalScopes()->where('site_id', $agent->site_id)->orderBy('started_at')->first()
            : null;

        return ['horaire' => $horaire, 'rest' => false, 'site_id' => $agent->site_id];
    }

    /**
     * Congés (TYPES) : liste.
     */
    public function congesIndex(Request $request): JsonResponse
    {
        $query = CongeType::query()
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'conges' => $query->paginate($perPage),
        ]);
    }

    /**
     * Congés (TYPES) : création / modification.
     */
    public function congesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:conge_types,id',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:actif,inactif',
        ]);

        $type = CongeType::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'actif',
            ]
        );

        return response()->json([
            'status' => 'success',
            'result' => $type,
        ]);
    }

    /**
     * Congés (TYPES) : suppression.
     */
    public function congesDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:conge_types,id',
        ]);

        $type = CongeType::findOrFail($data['id']);
        if ($type->isProtectedType()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ce type de congé ne peut pas être supprimé.',
            ], 403);
        }

        $type->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Type de congé supprimé.',
        ]);
    }

    public function authorizationsIndex(Request $request): JsonResponse
    {
        $userPrefix = ManagerStationContext::matriculePrefix();

        $query = AttendanceAuthorization::query()
            ->with(['agent.station'])
            ->when($userPrefix !== null, function($q) use ($userPrefix) {
                $q->whereHas('agent', function($qq) use ($userPrefix) {
                    $qq->withoutGlobalScopes()->where('matricule', 'like', $userPrefix . '%');
                });
            })
            ->when($request->query('agent_id'), fn ($q) => $q->where('agent_id', $request->query('agent_id')))
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->query('station_id'), function ($q) use ($request) {
                $q->whereHas('agent', function ($qq) use ($request) {
                    $qq->withoutGlobalScopes()->where('site_id', (int) $request->query('station_id'));
                });
            })
            ->when($request->query('region_id') || $request->query('city_id'), function ($q) use ($request) {
                $q->whereHas('agent', function ($agentQuery) use ($request) {
                    $agentQuery->withoutGlobalScopes()->whereHas('station', function ($stationQuery) use ($request) {
                        $stationQuery->withoutGlobalScopes()
                            ->when($request->query('region_id'), fn ($query) => $query->where('region_id', (int) $request->query('region_id')))
                            ->when($request->query('city_id'), fn ($query) => $query->where('city_id', (int) $request->query('city_id')));
                    });
                });
            })
            ->when($request->query('from'), fn ($q) => $q->whereDate('date_reference', '>=', $request->query('from')))
            ->when($request->query('to'), fn ($q) => $q->whereDate('date_reference', '<=', $request->query('to')))
            ->orderByDesc('date_reference')
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'authorizations' => $query->paginate($perPage),
        ]);
    }

    public function authorizationsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:attendance_authorizations,id',
            'agent_id' => 'required|integer|exists:agents,id',
            'date_reference' => 'required|date',
            'type' => 'required|string',
            'started_at' => 'nullable|date_format:H:i',
            'ended_at' => 'nullable|date_format:H:i',
            'minutes' => 'nullable|integer|min:0',
            'reason' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        if (strtolower($data['type']) === 'double shift') {
            $hasPresenceForDate = PresenceAgents::where('agent_id', $data['agent_id'])
                ->whereDate('date_reference', $data['date_reference'])
                ->whereNotNull('started_at')
                ->exists();

            if (!$hasPresenceForDate) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ["L'autorisation 'Double Shift' ne peut être créée sans présence existante pour cette date."]
                ], 200);
            }
        }

        $data['approved_by'] = ($data['status'] ?? null) === 'approved' ? auth()->id() : null;

        $auth = AttendanceAuthorization::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json([
            'status' => 'success',
            'result' => $auth->load(['agent.station']),
        ]);
    }

    /**
     * Création d'une autorisation spéciale (ex: retard autorisé) et approbation immédiate.
     */
    public function specialAuthorizationStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'date' => 'required|date',
            'comment' => 'nullable|string',
            'type' => 'required|string', // ex: 'retard'
        ]);

        if (strtolower($data['type']) === 'double shift') {
            $hasPresenceForDate = PresenceAgents::where('agent_id', $data['agent_id'])
                ->whereDate('date_reference', $data['date'])
                ->whereNotNull('started_at')
                ->exists();

            if (!$hasPresenceForDate) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ["L'autorisation 'Double Shift' ne peut être créée sans présence existante pour cette date."]
                ], 200);
            }
        }

        $auth = AttendanceAuthorization::create([
            'agent_id' => $data['agent_id'],
            'date_reference' => $data['date'],
            'type' => $data['type'],
            'reason' => $data['comment'],
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'result' => $auth->load(['agent.station']),
        ]);
    }

    public function globalAuthorizationStore(Request $request): JsonResponse
    {
        $stationRule = Rule::exists('sites', 'id')
            ->where(fn ($query) => $query->where('region_id', $request->input('region_id')));
        if ($request->filled('city_id')) {
            $stationRule->where('city_id', $request->input('city_id'));
        }

        $data = $request->validate([
            'region_id' => 'required|integer|exists:regions,id',
            'city_id' => [
                'nullable',
                'integer',
                Rule::exists('cities', 'id')->where(fn ($query) => $query->where('region_id', $request->input('region_id'))),
            ],
            'station_id' => ['nullable', 'integer', $stationRule],
            'date_reference' => 'required|date',
            'type' => 'required|string|max:255',
            'minutes' => 'nullable|integer|min:0',
            'reason' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $stationIds = ManagerStationContext::stationIds();
        $agents = Agent::withoutGlobalScopes()
            ->when($stationIds !== null, fn ($query) => $query->whereIn('site_id', $stationIds))
            ->whereHas('station', function ($query) use ($data) {
                $query->withoutGlobalScopes()
                    ->where('region_id', (int) $data['region_id'])
                    ->when(!empty($data['city_id']), fn ($q) => $q->where('city_id', (int) $data['city_id']))
                    ->when(!empty($data['station_id']), fn ($q) => $q->whereKey((int) $data['station_id']));
            })
            ->get(['id']);

        if ($agents->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'errors' => ['Aucun agent trouvé pour la région, la cité et la station sélectionnées.'],
            ], 422);
        }

        $status = $data['status'] ?? 'approved';
        $now = now();
        $rows = $agents->map(fn ($agent) => [
            'agent_id' => $agent->id,
            'date_reference' => $data['date_reference'],
            'type' => $data['type'],
            'minutes' => $data['minutes'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => $status,
            'approved_by' => $status === 'approved' ? auth()->id() : null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::transaction(function () use ($rows) {
            AttendanceAuthorization::insert($rows);
        });

        return response()->json([
            'status' => 'success',
            'message' => "Autorisation accordée à {$agents->count()} agent(s).",
            'count' => $agents->count(),
        ]);
    }

    public function authorizationsDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:attendance_authorizations,id',
        ]);

        AttendanceAuthorization::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Autorisation supprimée.',
        ]);
    }

    public function justificationsIndex(Request $request): JsonResponse
    {
        $userPrefix = ManagerStationContext::matriculePrefix();

        $query = AttendanceJustification::query()
            ->with(['agent.station', 'presence.assignedStation'])
            ->when($userPrefix !== null, function($q) use ($userPrefix) {
                $q->whereHas('agent', function($qq) use ($userPrefix) {
                    $qq->withoutGlobalScopes()->where('matricule', 'like', $userPrefix . '%');
                });
            })
            ->when($request->query('agent_id'), fn ($q) => $q->where('agent_id', $request->query('agent_id')))
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('kind'), fn ($q) => $q->where('kind', $request->query('kind')))
            ->when($request->query('from'), fn ($q) => $q->whereDate('date_reference', '>=', $request->query('from')))
            ->when($request->query('to'), fn ($q) => $q->whereDate('date_reference', '<=', $request->query('to')))
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        return response()->json([
            'status' => 'success',
            'justifications' => $query->paginate($perPage),
        ]);
    }

    public function justificationsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:attendance_justifications,id',
            'agent_id' => 'required|integer|exists:agents,id',
            'presence_agent_id' => 'nullable|integer|exists:presence_agents,id',
            'date_reference' => 'required|date',
            'kind' => 'required|string|in:retard,absence',
            'justification' => 'required|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $data['approved_by'] = ($data['status'] ?? null) === 'approved' ? auth()->id() : null;

        $justif = AttendanceJustification::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json([
            'status' => 'success',
            'result' => $justif->load(['agent.station', 'presence.assignedStation']),
        ]);
    }

    public function justificationsDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:attendance_justifications,id',
        ]);

        AttendanceJustification::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Justification supprimée.',
        ]);
    }

    /**
     * Pointage mensuel regroupé par stations (avec impact RH basique).
     */
    public function monthlyTimesheet(Request $request, AttendanceReportService $service): JsonResponse
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);

        $stationId = $data['station_id'] ?? null;
        $groupId = $data['group_id'] ?? null;

        $stations = $stationId
            ? \App\Models\Station::query()->where('id', $stationId)->get()
            : \App\Models\Station::query()->orderBy('name')->get();

        $result = [];
        foreach ($stations as $station) {
            $matrix = $service->buildMonthlyMatrix($month, $year, [
                'station_id' => $station->id,
                'group_id' => $groupId,
            ]);

            if (empty($matrix['data'])) continue; // Ne pas inclure les stations vides pour ce préfixe

            $result[] = [
                'station' => $station,
                'data' => $matrix['data'],
            ];
        }

        return response()->json([
            'status' => 'success',
            'month' => $month,
            'year' => $year,
            'stations' => $result,
        ]);
    }

    /**
     * Attributions : liste des congés assignés aux agents.
     */
    public function attributionsIndex(Request $request): JsonResponse
    {
        $tz = 'Africa/Kinshasa';
        $today = Carbon::now($tz)->startOfDay();
        $userPrefix = ManagerStationContext::matriculePrefix();

        $query = Conge::query()
            ->with(['agent.station', 'congeType'])
            ->when($userPrefix !== null, function($q) use ($userPrefix) {
                $q->whereHas('agent', function($qq) use ($userPrefix) {
                    $qq->withoutGlobalScopes()->where('matricule', 'like', $userPrefix . '%');
                });
            })
            ->when($request->query('agent_id'), fn ($q) => $q->where('agent_id', $request->query('agent_id')))
            ->when($request->query('conge_type_id'), fn ($q) => $q->where('conge_type_id', $request->query('conge_type_id')))
            ->when($request->query('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('from'), fn ($q) => $q->whereDate('date_fin', '>=', $request->query('from')))
            ->when($request->query('to'), fn ($q) => $q->whereDate('date_debut', '<=', $request->query('to')))
            ->orderByDesc('date_debut')
            ->orderByDesc('id');

        $perPage = (int) ($request->query('per_page', 15));

        $page = $query->paginate($perPage);
        $page->getCollection()->transform(function (Conge $c) use ($today, $tz) {
            try {
                $start = Carbon::parse($c->date_debut, $tz)->startOfDay();
                $end = Carbon::parse($c->date_fin, $tz)->startOfDay();

                // Jours totaux (inclusif)
                $totalDays = $start->diffInDays($end) + 1;

                // Jours consommés: du début jusqu'à aujourd'hui inclus, sans dépasser la fin.
                $consumeTo = $today->lt($start) ? null : ($today->lt($end) ? $today : $end);
                $daysConsumed = $consumeTo ? ($start->diffInDays($consumeTo) + 1) : 0;

                // Statut de période (indépendant du workflow pending/approved/rejected)
                $periodStatus = $today->lt($start) ? 'a_venir' : ($today->gt($end) ? 'termine' : 'en_cours');

                $c->days_total = $totalDays;
                $c->days_consumed = min($daysConsumed, $totalDays);
                $c->period_status = $periodStatus;
            } catch (\Throwable $_) {
                $c->days_total = null;
                $c->days_consumed = null;
                $c->period_status = null;
            }
            return $c;
        });

        return response()->json([
            'status' => 'success',
            'attributions' => $page,
        ]);
    }

    /**
     * Attributions : assigner un congé à un agent.
     */
    public function attributionsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:conges,id',
            'agent_id' => 'required|integer|exists:agents,id',
            'conge_type_id' => 'required|integer|exists:conge_types,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'nullable|string',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $type = CongeType::findOrFail($data['conge_type_id']);

        $payload = [
            'agent_id' => $data['agent_id'],
            'conge_type_id' => $data['conge_type_id'],
            'type' => $type->libelle, // compat legacy
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'motif' => $data['motif'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'approved_by' => ($data['status'] ?? null) === 'approved' ? auth()->id() : null,
        ];

        $conge = Conge::updateOrCreate(['id' => $data['id'] ?? null], $payload);

        return response()->json([
            'status' => 'success',
            'result' => $conge->load(['agent.station', 'congeType']),
        ]);
    }

    /**
     * Attributions : supprimer une assignation.
     */
    public function attributionsDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:conges,id',
        ]);

        Conge::whereKey($data['id'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Attribution supprimée.',
        ]);
    }

    /**
     * Listes de référence (agents + types) pour les formulaires Vue.
     */
    public function referenceData(): JsonResponse
    {
        // Agent::query() respectera automatiquement matricule_prefix via le Global Scope
        return response()->json([
            'status' => 'success',
            'agents' => Agent::query()->with('station')->orderBy('fullname')->get(),
            'types' => CongeType::query()->where('status', 'actif')->orderBy('libelle')->get(),
        ]);
    }
}
