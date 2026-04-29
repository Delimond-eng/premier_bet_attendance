<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentHistory;
use App\Models\AgentGroup;
use App\Models\AgentGroupAssignment;
use App\Models\AgentGroupPlanning;
use App\Models\AgentBiometric;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\MaintenanceAgent;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Models\User;
use App\Support\ManagerStationContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminController extends Controller
{
    public function createAgencieSite(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'nullable|integer',
                'name' => 'required|string',
                'type' => 'nullable|string|max:255',
                'code' => 'nullable|string|unique:sites,code,' . ($request->id ?? 'NULL'),
                'adresse' => 'required|string',
            ]);

            $managerStationId = ManagerStationContext::stationId();
            if ($managerStationId !== null) {
                $incomingId = isset($data['id']) ? (int) $data['id'] : null;
                if ($incomingId === null || $incomingId !== $managerStationId) {
                    return response()->json([
                        'status' => 'error',
                        'errors' => ['Un manager peut uniquement modifier sa propre station.'],
                    ], 403);
                }
            }

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
        $dateRaw = request()->query('date');
        $hasDate = $dateRaw !== null && trim((string) $dateRaw) !== '';
        $date = $hasDate ? Carbon::parse($dateRaw) : Carbon::today();
        $dateString = $date->toDateString();

        $stations = Station::query()
            ->withoutGlobalScopes()
            ->select(['id', 'name', 'type', 'code', 'adresse', 'latlng', 'phone', 'presence', 'status', 'created_at'])
            ->withCount([
                'agents',
                'agents as assigned_agents_count',
                'presences as presences_count' => fn ($q) => $q->whereDate('date_reference', $dateString)->whereNotNull('started_at'),
                'presences as late_count' => fn ($q) => $q->whereDate('date_reference', $dateString)->where('retard', 'oui'),
            ])
            ->orderBy('name')
            ->get();

        // When a date is provided, "agents_count" must reflect the agents expected to work
        // for that day at each station, based on the rotating planning (OFF days + flexible groups).
        if ($hasDate) {
            $agents = Agent::query()->get(['id', 'site_id', 'groupe_id']);
            $agentIds = $agents->pluck('id')->all();

            $assignments = AgentGroupAssignment::query()
                ->whereIn('agent_id', $agentIds)
                ->whereDate('start_date', '<=', $dateString)
                ->where(function ($q) use ($dateString) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $dateString);
                })
                ->orderByDesc('start_date')
                ->get(['agent_id', 'agent_group_id', 'start_date', 'end_date'])
                ->groupBy('agent_id');

            $effectiveGroupByAgent = [];
            $groupIds = [];
            foreach ($agents as $a) {
                $gid = null;
                foreach (($assignments[$a->id] ?? collect()) as $as) {
                    $gid = (int) $as->agent_group_id;
                    break; // ordered desc: first match is the effective group for the day
                }
                if ($gid === null && $a->groupe_id) {
                    $gid = (int) $a->groupe_id;
                }
                if ($gid !== null) {
                    $groupIds[] = $gid;
                }
                $effectiveGroupByAgent[(int) $a->id] = $gid;
            }

            $groupsById = AgentGroup::query()
                ->whereIn('id', array_values(array_unique($groupIds)))
                ->get(['id', 'horaire_id'])
                ->keyBy('id');

            $plannings = AgentGroupPlanning::query()
                ->whereIn('agent_id', $agentIds)
                ->whereDate('date', $dateString)
                ->get(['agent_id', 'agent_group_id', 'date', 'is_rest_day', 'horaire_id']);

            $planningByAgentGroup = [];
            $planningAnyByAgent = [];
            foreach ($plannings as $p) {
                $aid = (int) $p->agent_id;
                $gid = (int) $p->agent_group_id;
                $planningByAgentGroup[$aid . '|' . $gid] = $p;
                if (!array_key_exists($aid, $planningAnyByAgent)) {
                    $planningAnyByAgent[$aid] = $p;
                }
            }

            $expectedByStationId = [];

            foreach ($agents as $a) {
                $stationId = $a->site_id ? (int) $a->site_id : null;
                if (!$stationId) {
                    continue;
                }

                $aid = (int) $a->id;
                $gid = $effectiveGroupByAgent[$aid] ?? null;
                $group = $gid !== null ? $groupsById->get($gid) : null;
                $isFlexible = $group && empty($group->horaire_id);

                $planning = null;

                if ($gid !== null && array_key_exists($aid . '|' . $gid, $planningByAgentGroup)) {
                    $planning = $planningByAgentGroup[$aid . '|' . $gid];
                } elseif (!$isFlexible && array_key_exists($aid, $planningAnyByAgent)) {
                    // Legacy fallback: if no planning for the effective group, use any planning for that date.
                    $planning = $planningAnyByAgent[$aid];
                }

                if ($planning && $planning->is_rest_day) {
                    continue;
                }

                // Flexible groups (horaire_id null) are expected only when a concrete work planning exists for the day.
                if ($isFlexible) {
                    if (!$planning || empty($planning->horaire_id)) {
                        continue;
                    }
                }

                $expectedByStationId[$stationId] = ($expectedByStationId[$stationId] ?? 0) + 1;
            }

            foreach ($stations as $s) {
                $sid = (int) $s->id;
                $s->agents_count = (int) ($expectedByStationId[$sid] ?? 0);
            }
        }

        return response()->json([
            'status' => 'success',
            'date' => $dateString,
            'sites' => $stations,
        ]);
    }

    public function importStationsExcel(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            ]);

            $managerStationId = ManagerStationContext::stationId();
            $uploaded = $request->file('file');
            if (!$uploaded) {
                return response()->json(['errors' => ['Fichier manquant.']], 422);
            }

            $path = $uploaded->getPathname();
            $ext = strtolower((string) $uploaded->getClientOriginalExtension());

            if (in_array($ext, ['csv', 'txt'], true)) {
                $reader = new CsvReader();
                $reader->setDelimiter($this->sniffCsvDelimiter($path));
                $reader->setEnclosure('"');
                $reader->setEscapeCharacter('\\');
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }

            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            if (count($rows) < 2) {
                return response()->json(['errors' => ['Fichier Excel vide.']], 422);
            }

            $headerMap = $this->buildImportHeaderMap($rows[1] ?? []);
            $nameCol = $this->findImportHeaderColumn($headerMap, ['NOM', 'NAME', 'STATION']);
            $typeCol = $this->findImportHeaderColumn($headerMap, ['TYPE', 'CATEGORIE', 'CATEGORY']);

            if (!$nameCol || !$typeCol) {
                return response()->json([
                    'errors' => ['Entete invalide. Colonnes requises: NOM, TYPE.'],
                    'found' => array_values($headerMap),
                ], 422);
            }

            $stats = [
                'rows_total' => max(count($rows) - 1, 0),
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'duplicates_in_file' => 0,
            ];

            $errors = [];
            $seenNames = [];

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    continue;
                }

                $name = $this->normalizeImportedCell($row[$nameCol] ?? null, false);
                $type = $this->normalizeImportedCell($row[$typeCol] ?? null, false);

                $isEmptyLine = $name === '' && $type === '';
                if ($isEmptyLine) {
                    continue;
                }

                if ($name === '') {
                    $stats['skipped'] += 1;
                    if (count($errors) < 50) {
                        $errors[] = "Ligne {$rowIndex}: nom manquant.";
                    }
                    continue;
                }

                if ($type === '') {
                    $stats['skipped'] += 1;
                    if (count($errors) < 50) {
                        $errors[] = "Ligne {$rowIndex}: type manquant.";
                    }
                    continue;
                }

                $nameKey = strtoupper(Str::of($name)->ascii()->trim()->toString());
                if (isset($seenNames[$nameKey])) {
                    $stats['duplicates_in_file'] += 1;
                    $stats['skipped'] += 1;
                    continue;
                }
                $seenNames[$nameKey] = true;

                $stats['processed'] += 1;

                $station = Station::query()
                    ->withoutGlobalScopes()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                    ->first();

                if ($managerStationId !== null) {
                    if (!$station || (int) $station->id !== $managerStationId) {
                        $stats['skipped'] += 1;
                        if (count($errors) < 50) {
                            $errors[] = "Ligne {$rowIndex}: import hors station manager non autorise.";
                        }
                        continue;
                    }
                }

                if ($station) {
                    $station->update([
                        'name' => $name,
                        'type' => $type,
                    ]);
                    $stats['updated'] += 1;
                    continue;
                }

                $created = Station::query()->withoutGlobalScopes()->create([
                    'name' => $name,
                    'type' => $type,
                    'code' => $this->generateUniqueStationCode($name),
                    'adresse' => $name,
                    'presence' => 1,
                    'status' => 'actif',
                ]);

                $this->createDefaultSchedules((int) $created->id);
                $stats['created'] += 1;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Import des stations termine.',
                'stats' => $stats,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('importStationsExcel failed', ['error' => $e->getMessage()]);
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
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

    public function importAgentsExcel(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'station_id' => 'required|integer|exists:sites,id',
                'groupe_id' => 'required|integer|exists:agent_groups,id',
                'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            ]);

            $stationId = (int) $data['station_id'];
            $groupId = (int) $data['groupe_id'];
            $managerStationId = ManagerStationContext::stationId();
            if ($managerStationId !== null && $stationId !== $managerStationId) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['Un manager ne peut importer que sur sa propre station.'],
                ], 403);
            }

            $group = AgentGroup::query()
                ->with('horaire:id,site_id,libelle')
                ->find($groupId);
            if (!$group) {
                return response()->json(['errors' => ['Groupe horaire introuvable.']], 422);
            }

            $groupStationId = $group->horaire?->site_id !== null ? (int) $group->horaire?->site_id : null;
            $groupHoraireId = $group->horaire_id !== null ? (int) $group->horaire_id : null;
            if ($groupStationId !== null && $groupStationId !== $stationId) {
                return response()->json([
                    'errors' => ['Le groupe horaire selectionne ne correspond pas a la station choisie.'],
                ], 422);
            }

            $uploaded = $request->file('file');
            if (!$uploaded) {
                return response()->json(['errors' => ['Fichier manquant.']], 422);
            }

            $path = $uploaded->getPathname();
            $ext = strtolower((string) $uploaded->getClientOriginalExtension());

            if (in_array($ext, ['csv', 'txt'], true)) {
                $reader = new CsvReader();
                $reader->setDelimiter($this->sniffCsvDelimiter($path));
                $reader->setEnclosure('"');
                $reader->setEscapeCharacter('\\');
                $reader->setInputEncoding('UTF-8');
                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }

            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray(null, true, true, true);
            if (count($rows) < 2) {
                return response()->json(['errors' => ['Fichier Excel vide.']], 422);
            }

            $headerMap = $this->buildImportHeaderMap($rows[1] ?? []);
            $matriculeCol = $this->findImportHeaderColumn($headerMap, ['MATRICULE', 'MAT', 'MATR', 'EMPLOYEE ID']);
            $fullnameCol = $this->findImportHeaderColumn($headerMap, ['NOM ET POSTNOM', 'NOMS', 'NOM', 'NOMS ET PRENOMS', 'NOM COMPLET', 'FULLNAME', 'AGENT']);
            $fonctionCol = $this->findImportHeaderColumn($headerMap, ['FONCTION', 'POSTE', 'ROLE']);

            if (!$matriculeCol || !$fullnameCol) {
                return response()->json([
                    'errors' => ['Entete invalide. Colonnes requises: matricule, nom et postnom, fonction.'],
                    'found' => array_values($headerMap),
                ], 422);
            }

            $stats = [
                'station_id' => $stationId,
                'groupe_id' => $groupId,
                'rows_total' => max(count($rows) - 1, 0),
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'duplicates_in_file' => 0,
            ];

            $errors = [];
            $seenMatricules = [];

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    continue;
                }

                $matricule = $this->normalizeImportedCell($row[$matriculeCol] ?? null, true);
                $fullname = $this->normalizeImportedCell($row[$fullnameCol] ?? null, false);
                $fonction = $fonctionCol ? $this->normalizeImportedCell($row[$fonctionCol] ?? null, false) : '';

                $isEmptyLine = $matricule === '' && $fullname === '' && $fonction === '';
                if ($isEmptyLine) {
                    continue;
                }

                if ($matricule === '') {
                    $stats['skipped'] += 1;
                    if (count($errors) < 50) {
                        $errors[] = "Ligne {$rowIndex}: matricule manquant.";
                    }
                    continue;
                }

                if ($fullname === '') {
                    $stats['skipped'] += 1;
                    if (count($errors) < 50) {
                        $errors[] = "Ligne {$rowIndex}: noms manquants.";
                    }
                    continue;
                }

                $matriculeKey = strtoupper($matricule);
                if (isset($seenMatricules[$matriculeKey])) {
                    $stats['duplicates_in_file'] += 1;
                    $stats['skipped'] += 1;
                    continue;
                }
                $seenMatricules[$matriculeKey] = true;

                $stats['processed'] += 1;

                $agent = Agent::withoutGlobalScopes()
                    ->where('matricule', $matricule)
                    ->first();

                if ($agent) {
                    $beforeSiteId = $agent->site_id !== null ? (int) $agent->site_id : null;
                    if ($managerStationId !== null && $beforeSiteId !== null && $beforeSiteId !== $managerStationId) {
                        $stats['skipped'] += 1;
                        if (count($errors) < 50) {
                            $errors[] = "Ligne {$rowIndex}: matricule {$matricule} appartient a une autre station.";
                        }
                        continue;
                    }

                    $agent->update([
                        'fullname' => $fullname,
                        'fonction' => $fonction !== '' ? $fonction : null,
                        'site_id' => $stationId,
                        'groupe_id' => $groupId,
                        'horaire_id' => $groupHoraireId,
                    ]);

                    if ($beforeSiteId !== null && $beforeSiteId !== $stationId) {
                        AgentHistory::create([
                            'date' => Carbon::now(),
                            'agent_id' => $agent->id,
                            'site_id' => $stationId,
                            'site_provenance_id' => $beforeSiteId,
                            'status' => 'mutation',
                        ]);
                    }

                    $stats['updated'] += 1;
                    continue;
                }

                Agent::create([
                    'matricule' => $matricule,
                    'fullname' => $fullname,
                    'fonction' => $fonction !== '' ? $fonction : null,
                    'password' => bcrypt('salama123'),
                    'role' => 'agent',
                    'site_id' => $stationId,
                    'groupe_id' => $groupId,
                    'horaire_id' => $groupHoraireId,
                    'status' => 'actif',
                ]);

                $stats['created'] += 1;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Import termine.',
                'stats' => $stats,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('importAgentsExcel failed', ['error' => $e->getMessage()]);
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    private function buildImportHeaderMap(array $row): array
    {
        $map = [];
        foreach ($row as $col => $val) {
            if (!is_string($col)) {
                continue;
            }
            $h = strtoupper(trim((string) $val));
            $h = preg_replace('/\s+/', ' ', $h);
            if ($h === '') {
                continue;
            }
            $map[$col] = $h;
        }

        return $map;
    }

    private function findImportHeaderColumn(array $headerMap, array $needles): ?string
    {
        $normalizedNeedles = [];
        foreach ($needles as $needle) {
            $normalizedNeedles[] = strtoupper(trim((string) $needle));
        }

        foreach ($headerMap as $col => $header) {
            foreach ($normalizedNeedles as $needle) {
                if ($header === $needle) {
                    return (string) $col;
                }
            }
        }

        return null;
    }

    private function normalizeImportedCell(mixed $value, bool $removeAllSpaces = false): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) || is_int($value)) {
            $value = rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.');
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if ($removeAllSpaces) {
            $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;
        }

        return $normalized;
    }

    private function sniffCsvDelimiter(string $path): string
    {
        $firstLine = '';

        try {
            $fh = fopen($path, 'rb');
            if (is_resource($fh)) {
                $firstLine = (string) fgets($fh);
                fclose($fh);
            }
        } catch (\Throwable $_) {
        }

        $candidates = [',', ';', "\t", '|'];
        $best = ';';
        $bestCount = -1;
        foreach ($candidates as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $best;
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
            ->groupBy(fn ($a) => Carbon::parse($a->date_reference)->toDateString());

        $absenceJustifByDay = AttendanceJustification::query()
            ->whereBetween('date_reference', [$fromDate, $toDate])
            ->where('status', 'approved')
            ->where('kind', 'absence')
            ->get(['agent_id', 'date_reference'])
            ->groupBy(fn ($j) => Carbon::parse($j->date_reference)->toDateString());

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

        $maintenanceBase = MaintenanceAgent::query()
            ->whereDate('date_maintenance', '>=', $fromDate)
            ->whereDate('date_maintenance', '<=', $toDate);

        $maintenanceTotal = (clone $maintenanceBase)->count();
        $maintenanceCompleted = (clone $maintenanceBase)->whereNotNull('end_at')->count();
        $maintenanceOngoing = max($maintenanceTotal - $maintenanceCompleted, 0);

        $maintenanceOnStation = 0;
        $maintenanceOffStation = 0;
        foreach ((clone $maintenanceBase)->get(['commentaire']) as $row) {
            $meta = $this->extractMaintenanceMeta((string) $row->commentaire);
            if ($meta['is_on_station'] === true) {
                $maintenanceOnStation += 1;
            } elseif ($meta['is_on_station'] === false) {
                $maintenanceOffStation += 1;
            }
        }

        $latestMaintenances = (clone $maintenanceBase)
            ->with(['agent', 'station'])
            ->orderByDesc('date_maintenance')
            ->orderByDesc('started_at')
            ->limit(10)
            ->get();

        $latestMaintenances->transform(function (MaintenanceAgent $maintenance) {
            $meta = $this->extractMaintenanceMeta((string) $maintenance->commentaire);
            $maintenance->setAttribute('distance_meters', $meta['distance_meters']);
            $maintenance->setAttribute('is_on_station', $meta['is_on_station']);
            $maintenance->setAttribute('distance_label', $meta['distance_label']);
            $maintenance->setAttribute('date_maintenance_iso', $maintenance->getRawOriginal('date_maintenance'));
            $maintenance->setAttribute('started_at_raw', $maintenance->getRawOriginal('started_at'));
            $maintenance->setAttribute('end_at_raw', $maintenance->getRawOriginal('end_at'));
            return $maintenance;
        });

        $maintenanceProgression = $this->buildMaintenanceProgression(
            from: $from->copy()->startOfDay(),
            to: $to->copy()->startOfDay(),
            mode: (string) $request->query('mode', 'custom'),
        );

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
            'maintenances' => [
                'summary' => [
                    'total' => $maintenanceTotal,
                    'completed' => $maintenanceCompleted,
                    'ongoing' => $maintenanceOngoing,
                    'on_station' => $maintenanceOnStation,
                    'off_station' => $maintenanceOffStation,
                ],
                'latest' => $latestMaintenances,
                'progression' => $maintenanceProgression,
            ],
        ]);
    }

    private function buildMaintenanceProgression(Carbon $from, Carbon $to, string $mode = 'custom'): array
    {
        $granularity = $this->resolveMaintenanceGranularity($from, $to, $mode);
        $buckets = $this->buildMaintenanceBuckets($from, $to, $granularity);

        $bucketIndexes = [];
        foreach ($buckets as $index => $bucket) {
            $bucketIndexes[(string) $bucket['key']] = $index;
        }

        $rows = MaintenanceAgent::query()
            ->whereDate('date_maintenance', '>=', $from->toDateString())
            ->whereDate('date_maintenance', '<=', $to->toDateString())
            ->get(['station_id', 'date_maintenance']);

        $stationIds = $rows->pluck('station_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $stationNamesById = [];
        if (!empty($stationIds)) {
            $stationNamesById = Station::query()
                ->whereIn('id', $stationIds)
                ->pluck('name', 'id')
                ->all();
        }

        $seriesByStation = [];
        foreach ($rows as $row) {
            try {
                $date = Carbon::parse((string) $row->date_maintenance);
            } catch (\Throwable $_) {
                continue;
            }

            $bucketKey = $this->resolveMaintenanceBucketKey($date, $granularity);
            if (!array_key_exists($bucketKey, $bucketIndexes)) {
                continue;
            }

            $stationId = $row->station_id !== null ? (int) $row->station_id : null;
            $stationKey = $stationId !== null ? (string) $stationId : 'none';

            if (!isset($seriesByStation[$stationKey])) {
                $stationName = $stationId !== null
                    ? (string) ($stationNamesById[$stationId] ?? ('Station ' . $stationId))
                    : 'Sans station';

                $seriesByStation[$stationKey] = [
                    'station_id' => $stationId,
                    'station_name' => $stationName,
                    'data' => array_fill(0, count($buckets), 0),
                    'total' => 0,
                ];
            }
            $bucketIndex = $bucketIndexes[$bucketKey];
            $seriesByStation[$stationKey]['data'][$bucketIndex] += 1;
            $seriesByStation[$stationKey]['total'] += 1;
        }

        $series = array_values($seriesByStation);
        usort($series, fn ($a, $b) => (int) $b['total'] <=> (int) $a['total']);

        $maxSeries = 6;
        if (count($series) > $maxSeries) {
            $others = array_slice($series, $maxSeries);
            $series = array_slice($series, 0, $maxSeries);

            $othersData = array_fill(0, count($buckets), 0);
            $othersTotal = 0;
            foreach ($others as $item) {
                $othersTotal += (int) ($item['total'] ?? 0);
                foreach (($item['data'] ?? []) as $idx => $value) {
                    $othersData[$idx] += (int) $value;
                }
            }

            if ($othersTotal > 0) {
                $series[] = [
                    'station_id' => null,
                    'station_name' => 'Autres stations',
                    'data' => $othersData,
                    'total' => $othersTotal,
                ];
            }
        }

        return [
            'granularity' => $granularity,
            'labels' => array_map(fn ($b) => (string) $b['label'], $buckets),
            'series' => array_map(function (array $item) {
                return [
                    'station_id' => $item['station_id'],
                    'name' => (string) $item['station_name'],
                    'data' => array_map(fn ($v) => (int) $v, $item['data'] ?? []),
                    'total' => (int) ($item['total'] ?? 0),
                ];
            }, $series),
        ];
    }

    private function resolveMaintenanceGranularity(Carbon $from, Carbon $to, string $mode): string
    {
        if ($mode === 'week' || $mode === 'today') {
            return 'day';
        }

        if ($mode === 'month') {
            return 'week';
        }

        if ($mode === 'year') {
            return 'month';
        }

        $days = max($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1, 1);
        if ($days <= 31) {
            return 'day';
        }
        if ($days <= 120) {
            return 'week';
        }

        return 'month';
    }

    private function buildMaintenanceBuckets(Carbon $from, Carbon $to, string $granularity): array
    {
        $buckets = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $this->resolveMaintenanceBucketKey($cursor, $granularity);
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'key' => $key,
                    'label' => $this->resolveMaintenanceBucketLabel($cursor, $granularity),
                ];
            }

            $cursor->addDay();
        }

        if (empty($buckets)) {
            $fallbackKey = $this->resolveMaintenanceBucketKey($from, $granularity);
            $buckets[$fallbackKey] = [
                'key' => $fallbackKey,
                'label' => $this->resolveMaintenanceBucketLabel($from, $granularity),
            ];
        }

        return array_values($buckets);
    }

    private function resolveMaintenanceBucketKey(Carbon $date, string $granularity): string
    {
        if ($granularity === 'month') {
            return $date->format('Y-m');
        }

        if ($granularity === 'week') {
            return $date->isoWeekYear . '-W' . str_pad((string) $date->isoWeek(), 2, '0', STR_PAD_LEFT);
        }

        return $date->format('Y-m-d');
    }

    private function resolveMaintenanceBucketLabel(Carbon $date, string $granularity): string
    {
        if ($granularity === 'month') {
            return $date->format('m/Y');
        }

        if ($granularity === 'week') {
            return 'S' . $date->isoWeek() . ' ' . $date->isoWeekYear;
        }

        return $date->format('d/m');
    }

    private function extractMaintenanceMeta(?string $commentaire): array
    {
        $text = (string) ($commentaire ?? '');

        $debutDistance = null;
        $finDistance = null;
        $debutOnStation = null;
        $finOnStation = null;

        if (preg_match('/Debut\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $debutDistance = (int) $m[1];
        }

        if (preg_match('/Fin\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $finDistance = (int) $m[1];
        }

        if (preg_match('/Debut\\s+distance:[^\\n]*sur\\s+station:\\s*(oui|non)/i', $text, $m)) {
            $debutOnStation = strtolower($m[1]) === 'oui';
        }

        if (preg_match('/Fin\\s+distance:[^\\n]*sur\\s+station:\\s*(oui|non)/i', $text, $m)) {
            $finOnStation = strtolower($m[1]) === 'oui';
        }

        $distance = $finDistance ?? $debutDistance;
        $onStation = $finOnStation ?? $debutOnStation;

        return [
            'distance_meters' => $distance,
            'is_on_station' => $onStation,
            'distance_label' => $distance !== null ? ($distance . ' m') : 'Distance indisponible',
        ];
    }

    public function generateSiteQrcodes(Request $request)
    {
        $format = $request->query('format', 'a4');
        $cols = (int) $request->query('cols', 3);
        $orientation = $request->query('orientation', 'landscape');
        $stationIds = $request->query('ids');

        $query = Station::query()->orderBy('name');
        if (!empty($stationIds)) {
            $ids = is_array($stationIds) ? $stationIds : explode(',', $stationIds);
            $query->whereIn('id', array_map('intval', $ids));
        }
        $stations = $query->get();

        $horairesByStation = PresenceHoraire::query()
            ->select(['id', 'site_id', 'libelle', 'started_at', 'mid_check', 'ended_at'])
            ->orderBy('site_id')
            ->orderBy('started_at')
            ->get()
            ->groupBy('site_id');
        $data = [];

        $formatTime = static function ($value): ?string {
            if ($value === null || $value === '') {
                return null;
            }

            $time = (string) $value;
            return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
        };

        foreach ($stations as $station) {
            $qrData = json_encode([
                'id' => $station->id,
                'name' => $station->name,
                'type' => 'station_pointage',
            ]);

            $stationHoraires = ($horairesByStation->get($station->id) ?? collect())
                ->map(function (PresenceHoraire $horaire) use ($formatTime) {
                    return [
                        'libelle' => (string) ($horaire->libelle ?? ''),
                        'started_at' => $formatTime($horaire->getRawOriginal('started_at') ?? $horaire->started_at),
                        'mid_check' => $formatTime($horaire->getRawOriginal('mid_check') ?? $horaire->mid_check),
                        'ended_at' => $formatTime($horaire->getRawOriginal('ended_at') ?? $horaire->ended_at),
                    ];
                })
                ->values()
                ->all();

            // Use SVG to avoid requiring the Imagick extension for PNG rendering.
            $qrCode = QrCode::format('svg')->size(200)->generate($qrData);
            // The generated SVG includes an XML declaration which can break HTML parsing in Dompdf.
            $qrCode = preg_replace('/^<\\?xml[^>]*\\?>\\s*/', '', $qrCode) ?? $qrCode;
            $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCode);

            $data[] = [
                'name' => $station->name,
                'code' => $station->code,
                'qrcode' => $qrDataUri,
                'horaires' => $stationHoraires,
            ];
        }

        $pdf = Pdf::loadView('pdf.qrcodes', [
            'areas' => $data,
            'cols' => $cols,
        ])
            ->setPaper($format, $orientation)
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

        $managerStationId = ManagerStationContext::stationId();
        if ($managerStationId !== null) {
            $allowed = $this->canManagerDeleteRow(
                table: (string) $data['table'],
                id: (int) $data['id'],
                stationId: $managerStationId
            );

            if (!$allowed) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['Suppression hors station non autorisee pour ce manager.'],
                ], 403);
            }
        }

        $result = DB::table($data['table'])->where('id', $data['id'])->delete();

        return response()->json(['status' => 'success', 'result' => $result]);
    }

    private function canManagerDeleteRow(string $table, int $id, int $stationId): bool
    {
        return match ($table) {
            'sites' => $id === $stationId,
            'agents' => Agent::withoutGlobalScopes()->whereKey($id)->where('site_id', $stationId)->exists(),
            'presence_horaires' => PresenceHoraire::withoutGlobalScopes()->whereKey($id)->where('site_id', $stationId)->exists(),
            'agent_histories' => AgentHistory::withoutGlobalScopes()->whereKey($id)->where('site_id', $stationId)->exists(),
            'presence_agents' => PresenceAgents::withoutGlobalScopes()->whereKey($id)->where('site_id', $stationId)->exists(),
            'agent_group_plannings' => AgentGroupPlanning::withoutGlobalScopes()
                ->whereKey($id)
                ->whereHas('agent', fn ($q) => $q->withoutGlobalScopes()->where('site_id', $stationId))
                ->exists(),
            'conges' => Conge::withoutGlobalScopes()
                ->whereKey($id)
                ->whereHas('agent', fn ($q) => $q->withoutGlobalScopes()->where('site_id', $stationId))
                ->exists(),
            'attendance_authorizations' => AttendanceAuthorization::withoutGlobalScopes()
                ->whereKey($id)
                ->whereHas('agent', fn ($q) => $q->withoutGlobalScopes()->where('site_id', $stationId))
                ->exists(),
            'attendance_justifications' => AttendanceJustification::withoutGlobalScopes()
                ->whereKey($id)
                ->whereHas('agent', fn ($q) => $q->withoutGlobalScopes()->where('site_id', $stationId))
                ->exists(),
            'agent_groups' => $this->canManagerDeleteGroup($id, $stationId),
            default => false,
        };
    }

    private function canManagerDeleteGroup(int $groupId, int $stationId): bool
    {
        $usedOutside = Agent::withoutGlobalScopes()
            ->where('groupe_id', $groupId)
            ->where(function ($q) use ($stationId) {
                $q->whereNull('site_id')->orWhere('site_id', '!=', $stationId);
            })
            ->exists();

        if ($usedOutside) {
            return false;
        }

        return Agent::withoutGlobalScopes()
            ->where('groupe_id', $groupId)
            ->where('site_id', $stationId)
            ->exists();
    }

    public function fetchAgents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:1000',
            'search' => 'nullable|string',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $perPage = (int) ($data['per_page'] ?? 10);
        $perPage = max(min($perPage, 1000), 1);

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
     * Enroll agent with photo and biometric embedding
     * @return JsonResponse
     */
    public function enrollAgent(Request $request) : JsonResponse{
        try {
            $data = $request->validate([
                "matricule" => "required|string|exists:agents,matricule",
                "embedding" => "nullable|string",
                "model_version" => "nullable|string",
                "quality_score" => "nullable|numeric",
            ]);

            $agent = Agent::where("matricule", $data["matricule"])->first();

            if (!$agent) {
                return response()->json(['errors' => ["Agent non trouvé."]], 404);
            }

            // Mise à jour de la photo si présente
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = uniqid('agent_') . '.' . $file->getClientOriginalExtension();
                $destination = public_path('uploads/agents');
                $file->move($destination, $filename);
                $agent->update([
                    "photo" => url('uploads/agents/' . $filename)
                ]);
            }

            // Mise à jour ou création de l'embedding biométrique
            if (!empty($data['embedding'])) {
                AgentBiometric::updateOrCreate(
                    ['matricule' => $agent->matricule],
                    [
                        'agent_id' => $agent->id,
                        'embedding' => $data['embedding'],
                        'model_version' => $data['model_version'] ?? null,
                        'quality_score' => $data['quality_score'] ?? null,
                        'status' => 'active',
                    ]
                );
            }

            return response()->json([
                "status" => "success",
                "message" => "Enrôlement réussi.",
                "result" => $agent->load('station')
            ]);
        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->validator->errors()->all()], 422);
        }
        catch (\Throwable $e){
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }
}
