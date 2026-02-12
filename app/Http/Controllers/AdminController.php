<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentHistory;
use App\Models\AttendanceAuthorization;
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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminController extends Controller
{
    public function createAgencieSite(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'nullable|integer',
                'name' => 'required|string',
                'code' => 'required|string|unique:sites,code,' . ($request->id ?? 'NULL'),
                'latlng' => 'nullable|string',
                'adresse' => 'required|string',
                'phone' => 'nullable|string',
                'presence' => 'nullable|integer',
            ]);

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
                'site_id' => 'required|integer|exists:sites,id',
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
        $toDate = $to->toDateString();
        $toCursor = $to->copy()->startOfDay();
        $daysCount = max($from->copy()->startOfDay()->diffInDays($toCursor) + 1, 1);

        $totalStations = Station::count();
        $totalAgents = Agent::count();

        $presentAgents = PresenceAgents::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->whereNotNull('started_at')
            ->count();

        $lateAgents = PresenceAgents::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->whereNotNull('started_at')
            ->where('retard', 'oui')
            ->count();

        $absentAgents = max(($totalAgents * $daysCount) - $presentAgents, 0);

        $aggregate = PresenceAgents::query()
            ->selectRaw('DATE(date_reference) as d')
            ->selectRaw('SUM(CASE WHEN started_at IS NOT NULL THEN 1 ELSE 0 END) as present_count')
            ->selectRaw("SUM(CASE WHEN retard = 'oui' THEN 1 ELSE 0 END) as late_count")
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $labels = [];
        $dates = [];
        $seriesPresent = [];
        $seriesLate = [];
        $seriesAbsent = [];

        $cursor = $from->copy();
        while ($cursor->lte($toCursor)) {
            $d = $cursor->toDateString();
            $dates[] = $d;
            $labels[] = $cursor->format('d/m');
            $p = (int) ($aggregate[$d]->present_count ?? 0);
            $l = (int) ($aggregate[$d]->late_count ?? 0);
            $a = max($totalAgents - $p, 0);
            $seriesPresent[] = $p;
            $seriesLate[] = $l;
            $seriesAbsent[] = $a;
            $cursor->addDay();
        }

        $latest = PresenceAgents::query()
            ->with(['agent', 'stationCheckIn', 'assignedStation'])
            ->whereNotNull('started_at')
            ->whereBetween('started_at', [$from, $to])
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $authMaladie = AttendanceAuthorization::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->where('status', 'approved')
            ->where('type', 'maladie')
            ->count();

        $authConges = Conge::query()
            ->where('status', 'approved')
            ->whereDate('date_debut', '<=', $toDate)
            ->whereDate('date_fin', '>=', $fromDate)
            ->count();

        $authAutres = AttendanceAuthorization::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->where('status', 'approved')
            ->whereIn('type', ['deuil', 'autre'])
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
                'maladies' => $authMaladie,
                'conges' => $authConges,
                'autres' => $authAutres,
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

    public function enrollAgent(Request $request): JsonResponse
    {
        return response()->json(['status' => 'error', 'errors' => ['Endpoint legacy non implémenté.']], 501);
    }
}
