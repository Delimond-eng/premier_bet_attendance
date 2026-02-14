<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentHistory;
use App\Models\AgentGroupPlanning;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminController extends Controller
{
    public function createAgencieSite(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'nullable|integer',
                'name' => 'required|string',
                'code' => 'nullable|string|unique:sites,code,' . ($request->id ?? 'NULL'),
                'adresse' => 'required|string',
            ]);

            // Phone/GPS removed from the UI. Keep columns null to avoid stale values.
            $data['latlng'] = null;
            $data['phone'] = null;

            $incomingCode = strtoupper(trim((string) ($data['code'] ?? '')));
            $data['code'] = $incomingCode !== '' ? $incomingCode : null;

            if (!empty($data['id'])) {
                $existing = Station::find((int) $data['id']);
                if ($existing && !$data['code']) {
                    $data['code'] = $existing->code;
                }
            }

            if (!$data['code']) {
                $data['code'] = $this->generateUniqueStationCode($data['name']);
            }

            $station = Station::updateOrCreate(['id' => $request->id], $data);

            if (!$request->id) {
                $this->createDefaultSchedules($station->id);
            }

            return response()->json(['status' => 'success', 'result' => $station]);
        } catch (\Throwable $e) {
            Log::error('createAgencieSite failed', ['error' => $e->getMessage()]);
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    private function generateUniqueStationCode(string $name): string
    {
        $base = strtoupper(Str::of($name)->ascii()->replaceMatches('/[^A-Za-z0-9 ]+/', ' ')->trim()->toString());
        $parts = array_values(array_filter(preg_split('/\\s+/', $base) ?: []));
        $prefix = 'ST';
        if (count($parts) >= 2) {
            $prefix = substr($parts[0], 0, 1) . substr($parts[1], 0, 1);
        } elseif (count($parts) === 1) {
            $prefix = substr($parts[0], 0, 2);
        }
        $prefix = strtoupper($prefix ?: 'ST');

        for ($i = 0; $i < 25; $i += 1) {
            $code = $prefix . random_int(1000, 9999);
            $exists = Station::query()->where('code', $code)->exists();
            if (!$exists) {
                return $code;
            }
        }

        // Very unlikely fallback.
        return 'ST' . random_int(100000, 999999);
    }

    private function createDefaultSchedules(int $stationId): void
    {
        $defaults = [
            ['libelle' => 'Shift Jour', 'started_at' => '07:00', 'ended_at' => '18:00', 'site_id' => $stationId],
            ['libelle' => 'Shift Nuit', 'started_at' => '19:00', 'ended_at' => '06:00', 'site_id' => $stationId],
        ];

        foreach ($defaults as $d) {
            PresenceHoraire::create($d);
        }
    }

    public function viewAllSites(): JsonResponse
    {
        $date = request()->query('date') ? Carbon::parse(request()->query('date')) : Carbon::today();
        $dateString = $date->toDateString();

        $stations = Station::query()
            ->select(['id', 'name', 'code', 'adresse', 'latlng', 'phone', 'presence', 'status', 'created_at'])
            ->withCount([
                'agents',
                'presences as presences_count' => fn ($q) => $q->whereDate('date_reference', $dateString)->whereNotNull('started_at'),
                'presences as late_count' => fn ($q) => $q->whereDate('date_reference', $dateString)->where('retard', 'oui'),
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'date' => $dateString,
            'sites' => $stations,
        ]);
    }

    public function createAgent(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'nullable|integer',
                'matricule' => 'required|string|unique:agents,matricule,' . ($request->id ?? 'NULL'),
                'fullname' => 'required|string',
                'fonction' => 'nullable|string',
                'site_id' => 'required|integer|exists:sites,id',
                'groupe_id' => 'nullable|integer|exists:agent_groups,id',
                'status' => 'nullable|string',
                'photo' => 'nullable|image|max:2048',
            ]);

            $before = null;
            if (!empty($data['id'])) {
                $before = Agent::find($data['id']);
            }

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/agents'), $filename);
                $data['photo'] = url('uploads/agents/' . $filename);
            }

            $data['password'] = $data['password'] ?? bcrypt('salama123');

            $agent = Agent::updateOrCreate(['id' => $request->id], $data);

            if ($before && (int) $before->site_id !== (int) $agent->site_id) {
                AgentHistory::create([
                    'date' => Carbon::now(),
                    'agent_id' => $agent->id,
                    'site_id' => $agent->site_id,
                    'site_provenance_id' => $before->site_id,
                    'status' => 'mutation',
                ]);
            }

            return response()->json(['status' => 'success', 'result' => $agent]);
        } catch (\Throwable $e) {
            Log::error('createAgent failed', ['error' => $e->getMessage()]);
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    public function getDashboardData(Request $request): JsonResponse
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $dateString = $date->toDateString();

        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : $date->copy()->subDays(6)->startOfDay();
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : $date->copy()->endOfDay();

        $fromDate = $from->toDateString();
        $today = Carbon::now('Africa/Kinshasa')->startOfDay();

        // Ne jamais inclure les jours futurs dans les KPIs du dashboard.
        if ($to->copy()->startOfDay()->gt($today)) {
            $to = $today->copy()->endOfDay();
        }

        $toDate = $to->toDateString();
        $toCursor = $to->copy()->startOfDay();
        $daysCount = max($from->copy()->startOfDay()->diffInDays($toCursor) + 1, 1);

        $totalStations = Station::count();
        $totalAgents = Agent::count();

        // Dashboard: stats en "agent-jours" sur la période.
        // Règles:
        // - Un jour OFF (planning) n'est ni présent ni absent.
        // - Un agent sans pointage n'est absent que si le jour est "attendu" (non OFF)
        //   et qu'il n'a ni congé approuvé, ni autorisation approuvée, ni justification d'absence approuvée.
        $offByDay = AgentGroupPlanning::query()
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('is_rest_day', true)
            ->get(['agent_id', 'date'])
            ->groupBy(fn ($p) => Carbon::parse($p->date)->toDateString());

        $presencesByDay = PresenceAgents::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->whereNotNull('started_at')
            ->get(['agent_id', 'date_reference', 'retard', 'started_at', 'ended_at'])
            ->groupBy(fn (PresenceAgents $p) => Carbon::parse($p->date_reference)->toDateString());

        $authByDay = AttendanceAuthorization::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->where('status', 'approved')
            ->get(['agent_id', 'date_reference', 'type'])
            ->groupBy(fn (AttendanceAuthorization $a) => Carbon::parse($a->date_reference)->toDateString());

        $absenceJustifByDay = AttendanceJustification::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->where('status', 'approved')
            ->where('kind', 'absence')
            ->get(['agent_id', 'date_reference'])
            ->groupBy(fn (AttendanceJustification $j) => Carbon::parse($j->date_reference)->toDateString());

        $conges = Conge::query()
            ->where('status', 'approved')
            ->whereDate('date_debut', '<=', $toDate)
            ->whereDate('date_fin', '>=', $fromDate)
            ->get(['agent_id', 'date_debut', 'date_fin']);

        $labels = [];
        $dates = [];
        $seriesPresent = [];
        $seriesLate = [];
        $seriesAbsent = [];

        $presentAgents = 0;
        $lateAgents = 0;
        $absentAgents = 0;
        $expectedAgentDays = 0; // total "agent-jours" attendus (hors OFF)

        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($toCursor)) {
            $d = $cursor->toDateString();

            $offIds = array_values(array_unique(($offByDay[$d] ?? collect())->pluck('agent_id')->all()));
            $offLookup = array_fill_keys($offIds, true);

            $presentIds = array_values(array_unique(($presencesByDay[$d] ?? collect())->pluck('agent_id')->all()));
            $presentLookup = array_fill_keys($presentIds, true);

            $lateIds = array_values(array_unique(($presencesByDay[$d] ?? collect())
                ->filter(fn ($p) => ($p->retard ?? null) === 'oui')
                ->pluck('agent_id')
                ->all()));
            $lateLookup = array_fill_keys($lateIds, true);

            $justifiedLookup = [];

            foreach (($authByDay[$d] ?? collect()) as $a) {
                $justifiedLookup[(int) $a->agent_id] = true;
            }

            foreach (($absenceJustifByDay[$d] ?? collect()) as $j) {
                $justifiedLookup[(int) $j->agent_id] = true;
            }

            foreach ($conges as $c) {
                try {
                    $fromC = Carbon::parse($c->date_debut)->startOfDay();
                    $toC = Carbon::parse($c->date_fin)->endOfDay();
                    if ($cursor->betweenIncluded($fromC, $toC)) {
                        $justifiedLookup[(int) $c->agent_id] = true;
                    }
                } catch (\Throwable $_) {
                }
            }

            // OFF / présent => non compté comme justification.
            foreach ($offLookup as $aid => $_) {
                unset($justifiedLookup[$aid]);
            }
            foreach ($presentLookup as $aid => $_) {
                unset($justifiedLookup[$aid]);
            }

            $expectedForDay = max($totalAgents - count($offLookup), 0);
            $presentForDay = count($presentLookup);
            $lateForDay = count($lateLookup);
            $justifiedForDay = count($justifiedLookup);
            $absentForDay = max($expectedForDay - $presentForDay - $justifiedForDay, 0);

            $expectedAgentDays += $expectedForDay;
            $presentAgents += $presentForDay;
            $lateAgents += $lateForDay;
            $absentAgents += $absentForDay;

            $dates[] = $d;
            $labels[] = $cursor->format('d/m');
            $seriesPresent[] = $presentForDay;
            $seriesLate[] = $lateForDay;
            $seriesAbsent[] = $absentForDay;

            $cursor->addDay();
        }

        $workedMinutes = 0;
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $workedMinutes = (int) PresenceAgents::query()
                ->whereBetween('date_reference', [$fromDate, $toDate])
                ->whereNotNull('started_at')
                ->whereNotNull('ended_at')
                ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, started_at, ended_at)), 0) as m')
                ->value('m');
        } else {
            $rows = PresenceAgents::query()
                ->whereBetween('date_reference', [$fromDate, $toDate])
                ->whereNotNull('started_at')
                ->whereNotNull('ended_at')
                ->get(['started_at', 'ended_at']);

            foreach ($rows as $r) {
                try {
                    $start = Carbon::parse($r->getRawOriginal('started_at'));
                    $end = Carbon::parse($r->getRawOriginal('ended_at'));
                    $diff = $start->diffInMinutes($end, false);
                    if ($diff > 0) {
                        $workedMinutes += $diff;
                    }
                } catch (\Throwable $_) {
                }
            }
        }

        $workedHours = round($workedMinutes / 60, 1);
        $expectedAgentDaysForAvg = max((int) $expectedAgentDays, 1);
        $weeklyAverage = round(($presentAgents / $expectedAgentDaysForAvg) * 100, 1);

        $latest = PresenceAgents::query()
            ->with(['agent', 'stationCheckIn', 'assignedStation'])
            ->whereNotNull('started_at')
            ->whereBetween('started_at', [$from, $to])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        // Congés sur la période en "agent-jours" (aligné avec les autorisations qui sont au jour).
        $authConges = 0;
        foreach ($conges as $c) {
            try {
                $startC = Carbon::parse($c->date_debut)->startOfDay();
                $endC = Carbon::parse($c->date_fin)->endOfDay();
                $startOverlap = $startC->greaterThan($from) ? $startC : $from;
                $endOverlap = $endC->lessThan($to) ? $endC : $to;
                if ($startOverlap->lte($endOverlap)) {
                    $authConges += $startOverlap->copy()->startOfDay()->diffInDays($endOverlap->copy()->startOfDay()) + 1;
                }
            } catch (\Throwable $_) {
            }
        }

        // Autorisations spéciales sur la période (au jour), sans dépendre d'un libellé exact.
        // On exclut "retard/absence" qui sont des cas opérationnels distincts.
        $authSpeciales = AttendanceAuthorization::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->where('status', 'approved')
            ->whereNotIn('type', ['retard', 'absence'])
            ->count();

        $missedPunches = PresenceAgents::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->count();

        return response()->json([
            'status' => 'success',
            'count' => [
                'sites' => $totalStations,
                'agents' => $totalAgents,
                'presences' => $presentAgents,
                'retards' => $lateAgents,
                'absents' => $absentAgents,
            ],
            'authorizations' => [
                'conges' => $authConges,
                'speciales' => $authSpeciales,
            ],
            'charts' => [
                'range' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
                'dates' => $dates,
                'labels' => $labels,
                'series' => [
                    'present' => $seriesPresent,
                    'late' => $seriesLate,
                    'absent' => $seriesAbsent,
                ],
            ],
            'weekly_kpis' => [
                'worked_hours' => $workedHours,
                'missed_punches' => (int) $missedPunches,
                'weekly_average' => $weeklyAverage,
            ],
            'latest_checkins' => $latest,
        ]);
    }

    public function generateSiteQrcodes()
    {
        $stations = Station::all();
        $data = [];

        foreach ($stations as $station) {
            $qrData = json_encode([
                'id' => $station->id,
                'name' => $station->name,
                'type' => 'station_pointage',
            ]);

            // Use SVG to avoid requiring the Imagick extension for PNG rendering.
            $qrCode = QrCode::format('svg')->size(200)->generate($qrData);
            // The generated SVG includes an XML declaration which can break HTML parsing in Dompdf.
            $qrCode = preg_replace('/^<\\?xml[^>]*\\?>\\s*/', '', $qrCode) ?? $qrCode;
            $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCode);

            $data[] = [
                'name' => $station->name,
                'qrcode' => $qrDataUri,
            ];
        }

        $pdf = Pdf::loadView('pdf.qrcodes', ['areas' => $data])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true);
        return $pdf->download('qrcodes_stations.pdf');
    }

    public function exportPresenceReport(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $dateString = $date->toDateString();

        $sites = Station::query()
            ->select(['id', 'code', 'name', 'presence'])
            ->withCount([
                'agents',
                'presences as presences_count' => fn ($q) => $q->whereDate('date_reference', $dateString)->whereNotNull('started_at'),
            ])
            ->orderBy('name')
            ->get();

        $sites->each(function ($site) {
            $site->presence_expected = $site->presence;
        });

        $totalPresences = (int) $sites->sum('presences_count');
        $totalAgents = (int) $sites->sum(fn ($s) => ($s->presence_expected ?? $s->agents_count));

        $pdf = Pdf::loadView('pdf.reports.presence_simple_report', [
            'sites' => $sites,
            'date' => $dateString,
            'totalPresences' => $totalPresences,
            'totalAgents' => $totalAgents,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rapport_presence_' . $date->format('Ymd') . '.pdf');
    }

    public function triggerDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table' => 'required|string|in:agents,sites,presence_horaires,agent_groups,agent_group_plannings,agent_histories,presence_agents,conges,attendance_authorizations,attendance_justifications',
            'id' => 'required|integer',
        ]);

        $result = DB::table($data['table'])->where('id', $data['id'])->delete();

        return response()->json(['status' => 'success', 'result' => $result]);
    }

    public function fetchAgents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:200',
            'search' => 'nullable|string',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $perPage = (int) ($data['per_page'] ?? 10);
        $perPage = max(min($perPage, 200), 1);

        $search = $data['search'] ?? null;
        $stationId = $data['station_id'] ?? null;

        $agents = Agent::query()
            ->with('station')
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('fullname', 'like', '%' . $search . '%')
                        ->orWhere('matricule', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        $today = Carbon::today()->toDateString();
        $agentsBase = Agent::query()->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId));
        $agentIds = $stationId !== null ? $agentsBase->pluck('id')->all() : null;

        return response()->json([
            'status' => 'success',
            'agents' => $agents,
            'stats' => [
                'total' => (clone $agentsBase)->count(),
                'actif' => (clone $agentsBase)->where('status', 'actif')->count(),
                'inactif' => (clone $agentsBase)->where(function ($q) {
                    $q->where('status', '!=', 'actif')->orWhereNull('status');
                })->count(),
                'conges' => Conge::query()
                    ->where('status', 'approved')
                    ->whereDate('date_debut', '<=', $today)
                    ->whereDate('date_fin', '>=', $today)
                    ->when($agentIds !== null, fn ($q) => $q->whereIn('agent_id', $agentIds))
                    ->count(),
            ],
        ]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return response()->json(['status' => 'success', 'result' => $user]);
    }

    // ---- Endpoints API legacy (hors périmètre web attendance/RH) ----

    public function createAgencie(Request $request): JsonResponse
    {
        return response()->json(['status' => 'error', 'errors' => ['Endpoint legacy non implémenté.']], 501);
    }

    public function completeArea(Request $request): JsonResponse
    {
        return response()->json(['status' => 'error', 'errors' => ['Endpoint legacy non implémenté.']], 501);
    }

    public function completeToken(Request $request): JsonResponse
    {
        return response()->json(['status' => 'error', 'errors' => ['Endpoint legacy non implémenté.']], 501);
    }

    /**
     * Enroll agent with photo
     * @return JsonResponse
     */
    public function enrollAgent(Request $request) : JsonResponse{
        try {
            $data = $request->validate([
                "matricule"=>"required|string|exists:agents,matricule",
            ]);
            $agent = Agent::where("matricule", $data["matricule"])->first();
            if ($request->hasFile('photo') && isset($agent)) {
                $file = $request->file('photo');
                $filename = uniqid('agent_') . '.' . $file->getClientOriginalExtension();
                $destination = public_path('uploads/agents');
                $file->move($destination, $filename);
                // Générer un lien complet sans utiliser storage
                $data['photo'] = url('uploads/agents/' . $filename);

                $agent->update([
                    "photo"=>$data["photo"]
                ]);
            }

            

            return response()->json([
                "status"=>"success",
                "result"=>$agent
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            return response()->json(['errors' => $errors ]);
        }
        catch (\Illuminate\Database\QueryException $e){
            return response()->json(['errors' => $e->getMessage() ]);
        }
    }
}
