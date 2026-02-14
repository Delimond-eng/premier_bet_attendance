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

/**
 * Class PlanningController
 * Gère la génération des plannings de rotation pour les agents.
 */
class PlanningController extends Controller
{
    /**
     * Import d'un planning hebdomadaire depuis Excel (vue par agent: MATRICULE + LUNDI..DIMANCHE).
     *
     * Règles importantes:
     * - La semaine DOIT commencer le lundi (forcée).
     * - Une cellule vide = OFF (repos).
     * - Valeurs supportées: OFF/REPOS/PAUSE, "08:00-15:00", "08:00 - 15:00", "H0800_1500".
     */
    public function importWeeklyPlanning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
            'group_id' => 'nullable|integer|exists:agent_groups,id',
            'start_date' => 'nullable|date', // date dans la semaine importée (idéalement lundi)
            'sheet' => 'nullable|string', // nom de la feuille optionnel
            'csv_delimiter' => 'nullable|string|size:1',
            'csv_enclosure' => 'nullable|string|size:1',
            'csv_escape' => 'nullable|string|size:1',
            'csv_input_encoding' => 'nullable|string|max:50',
        ]);

        $tz = 'Africa/Kinshasa';

        // ✅ Si group_id n'est pas fourni, on essaye de prendre un groupe "flexible" (horaire_id NULL).
        // IMPORTANT: si tu as plusieurs groupes avec horaire_id NULL, évite "le premier" => passe group_id explicitement.
        $groupId = (int) ($data['group_id']
            ?? AgentGroup::query()->whereNull('horaire_id')->orderBy('id')->value('id')
        );

        if (!$groupId) {
            return response()->json([
                'errors' => ['Groupe introuvable. Fournis group_id ou crée un groupe flexible (horaire_id = NULL).'],
            ], 422);
        }

        $base = !empty($data['start_date'])
            ? Carbon::parse($data['start_date'], $tz)
            : Carbon::now($tz);

        // ✅ Force semaine LUNDI -> DIMANCHE (stable partout)
        $startOfWeek = $base->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        try {
            $uploaded = $request->file('file');
            $path = $uploaded->getPathname();
            $ext = strtolower((string) $uploaded->getClientOriginalExtension());

            if (in_array($ext, ['csv', 'txt'], true)) {
                $reader = new CsvReader();

                $delimiter = $data['csv_delimiter'] ?? null;
                $enclosure = $data['csv_enclosure'] ?? '"';
                $escape = $data['csv_escape'] ?? '\\';

                if (!$delimiter) {
                    // Sniff delimiter from first line (common: ; , tab |)
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
                    foreach ($candidates as $cand) {
                        $count = substr_count($firstLine, $cand);
                        if ($count > $bestCount) {
                            $bestCount = $count;
                            $best = $cand;
                        }
                    }
                    $delimiter = $best;
                }

                $reader->setDelimiter($delimiter);
                $reader->setEnclosure($enclosure);
                $reader->setEscapeCharacter($escape);
                $reader->setInputEncoding($data['csv_input_encoding'] ?? 'UTF-8');

                $spreadsheet = $reader->load($path);
            } else {
                $spreadsheet = IOFactory::load($path);
            }

            $activeSheet = !empty($data['sheet'])
                ? ($spreadsheet->getSheetByName($data['sheet']) ?? $spreadsheet->getActiveSheet())
                : $spreadsheet->getActiveSheet();

            $rows = $activeSheet->toArray(null, true, true, true);
            if (count($rows) < 2) {
                return response()->json(['errors' => ['Fichier Excel vide.']], 422);
            }

            $headerMap = $this->buildHeaderMap($rows[1] ?? []);

            $matriculeCol = $this->findHeaderColumn($headerMap, ['MATRICULE', 'MAT', 'MATR']);

            // ✅ Colonnes jours obligatoires
            $daysCols = [
                'LUNDI' => $this->findHeaderColumn($headerMap, ['LUNDI']),
                'MARDI' => $this->findHeaderColumn($headerMap, ['MARDI']),
                'MERCREDI' => $this->findHeaderColumn($headerMap, ['MERCREDI']),
                'JEUDI' => $this->findHeaderColumn($headerMap, ['JEUDI']),
                'VENDREDI' => $this->findHeaderColumn($headerMap, ['VENDREDI']),
                'SAMEDI' => $this->findHeaderColumn($headerMap, ['SAMEDI']),
                'DIMANCHE' => $this->findHeaderColumn($headerMap, ['DIMANCHE']),
            ];

            if (!$matriculeCol || in_array(null, $daysCols, true)) {
                return response()->json([
                    'errors' => ['Entete invalide: MATRICULE + LUNDI..DIMANCHE requis.'],
                    'found' => array_values($headerMap),
                ], 422);
            }

            $errors = [];
            $stats = [
                'week_from' => $startOfWeek->toDateString(),
                'week_to' => $endOfWeek->toDateString(),
                'group_id' => $groupId,
                'rows_total' => max(count($rows) - 1, 0),
                'agents_found' => 0,
                'agents_missing' => 0,
                'plannings_created' => 0,
                'horaires_created' => 0,
                'cells_as_off' => 0,
            ];

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    continue; // header
                }

                $matriculeRaw = (string) ($row[$matriculeCol] ?? '');
                $matricule = preg_replace('/\s+/', '', trim($matriculeRaw));

                if ($matricule === '') {
                    continue;
                }

                $agent = Agent::query()->where('matricule', $matricule)->first();
                if (!$agent) {
                    $stats['agents_missing'] += 1;
                    continue;
                }

                $stats['agents_found'] += 1;

                // Assignation au groupe (historique)
                $alreadyAssigned = AgentGroupAssignment::query()
                    ->where('agent_id', $agent->id)
                    ->where('agent_group_id', $groupId)
                    ->exists();

                if (!$alreadyAssigned) {
                    AgentGroupAssignment::create([
                        'agent_id' => $agent->id,
                        'agent_group_id' => $groupId,
                        'start_date' => $startOfWeek->toDateString(),
                        'end_date' => null,
                    ]);
                }

                // Groupe courant sur agent
                if ((int) $agent->groupe_id !== $groupId) {
                    $agent->update(['groupe_id' => $groupId]);
                }

                // Nettoyage planning existant de CETTE semaine / CE groupe
                AgentGroupPlanning::query()
                    ->where('agent_id', $agent->id)
                    ->where('agent_group_id', $groupId)
                    ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                    ->delete();

                $dayIndex = 0;
                foreach ($daysCols as $dayName => $col) {
                    $raw = (string) ($row[$col] ?? '');
                    $parsed = $this->parsePlanningCell($raw);
                    $date = $startOfWeek->copy()->addDays($dayIndex)->toDateString();

                    if ($parsed['type'] === 'invalid') {
                        if (count($errors) < 30) {
                            $errors[] = "Invalid cell (row {$rowIndex}, {$dayName}): " . trim((string) $raw);
                        }
                        $dayIndex++;
                        continue;
                    }

                    if ($parsed['type'] === 'off') {
                        AgentGroupPlanning::create([
                            'agent_id' => $agent->id,
                            'agent_group_id' => $groupId,
                            'horaire_id' => null,
                            'date' => $date,
                            'is_rest_day' => true,
                        ]);
                        $stats['plannings_created'] += 1;
                        $stats['cells_as_off'] += 1;
                        $dayIndex++;
                        continue;
                    }

                    // ✅ Résolution horaire + compteur created (sans passage par référence fragile)
                    [$horaire, $created] = $this->resolveHoraireForAgent(
                        agent: $agent,
                        startedAt: $parsed['started_at'],
                        endedAt: $parsed['ended_at'],
                    );
                    if ($created) {
                        $stats['horaires_created'] += 1;
                    }

                    AgentGroupPlanning::create([
                        'agent_id' => $agent->id,
                        'agent_group_id' => $groupId,
                        'horaire_id' => $horaire?->id,
                        'date' => $date,
                        'is_rest_day' => $horaire ? false : true,
                    ]);

                    $stats['plannings_created'] += 1;
                    $dayIndex++;
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'errors' => $errors,
                    'stats' => $stats,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Planning hebdomadaire importe avec succes.',
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('importWeeklyPlanning failed', ['error' => $e->getMessage()]);
            return response()->json(['errors' => ['Import failed: ' . $e->getMessage()]], 500);
        }
    }

    /**
     * Génère le planning pour un groupe d'agents sur un mois donné.
     * NOTE: rotation_type est validé mais la logique "alternating" n'est pas implémentée ici (placeholder).
     */
    public function generateMonthlyPlanning(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'group_id' => 'required|exists:agent_groups,id',
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer',
                'rotation_type' => 'required|in:fixed,alternating',
            ]);

            $tz = 'Africa/Kinshasa';

            $group = AgentGroup::with('horaire')->findOrFail($data['group_id']);
            $agents = Agent::query()->where('groupe_id', $group->id)->get();

            $startDate = Carbon::createFromDate($data['year'], $data['month'], 1, $tz)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            DB::beginTransaction();

            foreach ($agents as $agent) {
                AgentGroupPlanning::query()
                    ->where('agent_id', $agent->id)
                    ->where('agent_group_id', $group->id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->delete();

                $currentDate = $startDate->copy();

                while ($currentDate->lte($endDate)) {
                    // Placeholder: fixe (horaire par défaut) + repos weekend.
                    // Pour un vrai "flexible", utilise plutôt importWeeklyPlanning / pattern hebdo.
                    AgentGroupPlanning::create([
                        'agent_id' => $agent->id,
                        'agent_group_id' => $group->id,
                        'horaire_id' => $group->horaire_id,
                        'date' => $currentDate->toDateString(),
                        'is_rest_day' => $currentDate->isWeekend(),
                    ]);

                    $currentDate->addDay();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Planning généré avec succès pour ' . count($agents) . ' agents.',
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json(['errors' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Récupère le planning d'une station pour une semaine (Lundi -> Dimanche).
     */
    public function getStationWeeklyPlanning(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
            'date' => 'nullable|date',
            'exists_only' => 'nullable|boolean',
        ]);

        $tz = 'Africa/Kinshasa';

        $base = !empty($data['date'])
            ? Carbon::parse($data['date'], $tz)
            : Carbon::now($tz);

        // ✅ Force lundi
        $startOfWeek = $base->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        $existsOnly = (bool) ($data['exists_only'] ?? false);

        $baseQuery = AgentGroupPlanning::query()
            ->when(
                !empty($data['station_id']),
                fn ($q) => $q->whereHas('agent', fn ($sub) => $sub->where('site_id', (int) $data['station_id']))
            )
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);

        if ($existsOnly) {
            return response()->json([
                'status' => 'success',
                'from' => $startOfWeek->toDateString(),
                'to' => $endOfWeek->toDateString(),
                'exists' => $baseQuery->exists(),
            ]);
        }

        $plannings = (clone $baseQuery)
            ->with(['agent.station', 'horaire'])
            ->get();

        $agents = $plannings->pluck('agent')->filter()->unique('id')->values();

        $days = [];
        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            $label = ucfirst((string) $cursor->copy()->locale('fr')->translatedFormat('l'));
            $days[] = [
                'date' => $cursor->toDateString(),
                'label' => $label,
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
                $entry = $plannings->first(fn ($p) =>
                    (int) $p->agent_id === (int) $agent->id && $p->date === $day['date']
                );

                if (!$entry) {
                    $row['days'][$day['date']] = ['status' => 'unknown', 'label' => '--'];
                    continue;
                }

                if ($entry->is_rest_day) {
                    $row['days'][$day['date']] = ['status' => 'off', 'label' => 'OFF'];
                    continue;
                }

                $label = '--';
                if ($entry->horaire) {
                    $rawStart = (string) ($entry->horaire->getRawOriginal('started_at') ?? $entry->horaire->started_at);
                    $rawEnd = (string) ($entry->horaire->getRawOriginal('ended_at') ?? $entry->horaire->ended_at);
                    $start = substr($rawStart, 0, 5);
                    $end = substr($rawEnd, 0, 5);
                    $label = $start . ' - ' . $end;
                }

                $row['days'][$day['date']] = ['status' => 'work', 'label' => $label];
            }

            $matrix[] = $row;
        }

        $stationGroups = collect($matrix)
            ->groupBy(function ($row) {
                $stationId = $row['agent']?->site_id;
                return $stationId ? ('station:' . $stationId) : 'station:none';
            })
            ->map(function ($rows, $key) {
                $agent = $rows->first()['agent'] ?? null;
                $station = $agent?->station;
                $stationName = $station?->name ?? 'Sans station';
                $stationId = $station?->id ?? null;

                $sorted = $rows->sortBy(fn ($r) => (string) ($r['agent']?->fullname ?? $r['agent']?->matricule ?? ''))->values()->all();

                return [
                    'key' => $key,
                    'station' => $station ? [
                        'id' => $stationId,
                        'name' => $stationName,
                        'code' => $station->code,
                    ] : null,
                    'station_name' => $stationName,
                    'rows' => $sorted,
                ];
            })
            ->values()
            ->sortBy('station_name')
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'from' => $startOfWeek->toDateString(),
            'to' => $endOfWeek->toDateString(),
            'days' => $days,
            'data' => $matrix,
            'stations' => $stationGroups,
        ]);
    }

    /**
     * Header helpers
     */
    private function buildHeaderMap(array $row): array
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

    private function findHeaderColumn(array $headerMap, array $needles): ?string
    {
        $needles = array_map(fn ($s) => strtoupper(trim((string) $s)), $needles);
        foreach ($headerMap as $col => $h) {
            foreach ($needles as $n) {
                if ($h === $n) {
                    return $col;
                }
            }
        }
        return null;
    }

    /**
     * Supported values:
     * - OFF (or empty)
     * - 08:30-16:30
     * - 08:30 - 16:30
     * - H0800_1500
     *
     * @return array{type:string, started_at?:string, ended_at?:string}
     */
    private function parsePlanningCell(string $raw): array
    {
        $trimmed = trim((string) $raw);
        if ($trimmed === '') {
            return ['type' => 'off'];
        }

        $u = strtoupper($trimmed);
        if (in_array($u, ['OFF', 'REPOS', 'REST', 'PAUSE'], true)) {
            return ['type' => 'off'];
        }

        if (preg_match('/^H(\d{2})(\d{2})_(\d{2})(\d{2})$/', $u, $m)) {
            return [
                'type' => 'range',
                'started_at' => sprintf('%02d:%02d', (int) $m[1], (int) $m[2]),
                'ended_at' => sprintf('%02d:%02d', (int) $m[3], (int) $m[4]),
            ];
        }

        if (preg_match('/^(\d{1,2})\s*[:Hh]\s*(\d{2})\s*-\s*(\d{1,2})\s*[:Hh]\s*(\d{2})$/', $trimmed, $m)) {
            $sh = (int) $m[1];
            $sm = (int) $m[2];
            $eh = (int) $m[3];
            $em = (int) $m[4];

            if ($sh < 0 || $sh > 23 || $eh < 0 || $eh > 23 || $sm < 0 || $sm > 59 || $em < 0 || $em > 59) {
                return ['type' => 'invalid'];
            }

            return [
                'type' => 'range',
                'started_at' => sprintf('%02d:%02d', $sh, $sm),
                'ended_at' => sprintf('%02d:%02d', $eh, $em),
            ];
        }

        return ['type' => 'invalid'];
    }

    /**
     * Retourne [PresenceHoraire|null, bool $created]
     */
    private function resolveHoraireForAgent(Agent $agent, string $startedAt, string $endedAt): array
    {
        $siteId = $agent->site_id ? (int) $agent->site_id : null;

        $baseQuery = PresenceHoraire::query()
            ->where('started_at', $startedAt)
            ->where('ended_at', $endedAt);

        // Si tu veux éviter la multiplication des horaires par site, commente cette partie et garde uniquement site_id NULL.
        if ($siteId !== null) {
            $horaire = (clone $baseQuery)->where('site_id', $siteId)->orderBy('id')->first();
            if ($horaire) {
                return [$horaire, false];
            }
        }

        $horaire = (clone $baseQuery)->whereNull('site_id')->orderBy('id')->first();
        if ($horaire) {
            return [$horaire, false];
        }

        $horaire = PresenceHoraire::create([
            'libelle' => 'Imported ' . $startedAt . '-' . $endedAt,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'tolerence_minutes' => 15,
            'site_id' => $siteId, // mets null si tu veux mutualiser les horaires
        ]);

        return [$horaire, true];
    }
}
