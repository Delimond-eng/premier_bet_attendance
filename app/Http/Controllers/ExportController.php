<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\MaintenanceAgent;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Services\AbsenceReportService;
use App\Services\AttendanceReportService;
use App\Services\CumulativeAlertService;
use App\Services\LateReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function attendancesPdf(Request $request, AttendanceReportService $service): Response
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $query = PresenceAgents::withoutGlobalScopes()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where(function ($q) use ($stationId) {
                $q->where('site_id', (int) $stationId)
                    ->orWhere('station_check_in_id', (int) $stationId)
                    ->orWhere('station_check_out_id', (int) $stationId);
            });
        }

        $rows = $query
            ->orderByDesc('started_at')
            ->get();

        foreach ($rows as $row) {
            $ot = $service->calculateOvertime($row, $row->horaire);
            $norm = $service->calculateNormalHours($row, $ot);
            $row->setAttribute('overtime_minutes', $ot);
            $row->setAttribute('overtime_display', $service->formatOvertime($ot));
            $row->setAttribute('normal_hours_display', $service->formatOvertime($norm));
        }

        $pdf = Pdf::loadView('pdf.exports.attendances', [
            'title' => 'Journal de pointage',
            'date' => $date,
            'station' => $station,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        $suffix = $station ? ('_' . $station->id) : '';
        return $pdf->download('journal_pointage_' . str_replace('-', '', $date) . $suffix . '.pdf');
    }

    public function attendancesExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $query = PresenceAgents::withoutGlobalScopes()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where(function ($q) use ($stationId) {
                $q->where('site_id', (int) $stationId)
                    ->orWhere('station_check_in_id', (int) $stationId)
                    ->orWhere('station_check_out_id', (int) $stationId);
            });
        }

        $rows = $query
            ->orderByDesc('started_at')
            ->get();

        $headers = [
            'Matricule',
            'Nom complet',
            'Station affectation',
            'Check-in',
            'Check-out',
            'Date',
            'Heure entree',
            'Heure sortie',
            'Heures Normales',
            'Heures Sup',
            'Controle intermediaire',
            'Duree Totale',
            'Retard',
        ];

        $table = [];
        foreach ($rows as $p) {
            $ot = $service->calculateOvertime($p, $p->horaire);
            $norm = $service->calculateNormalHours($p, $ot);
            $table[] = [
                (string) ($p->agent?->matricule ?? ''),
                (string) ($p->agent?->fullname ?? ''),
                (string) ($p->assignedStation?->name ?? ''),
                (string) ($p->stationCheckIn?->name ?? ''),
                (string) ($p->stationCheckOut?->name ?? ''),
                Carbon::parse($p->date_reference)->toDateString(),
                $p->started_at ? Carbon::parse($p->started_at)->format('H:i') : '',
                $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : '',
                $service->formatOvertime($norm),
                $service->formatOvertime($ot),
                $p->mid_check ? Carbon::parse($p->mid_check)->format('H:i') : '',
                (string) ($p->duree ?? ''),
                (string) ($p->retard ?? ''),
            ];
        }

        $meta = [
            'Date: ' . $date,
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'journal_pointage_' . str_replace('-', '', $date) . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Pointages',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function agentsPdf(Request $request): Response
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $agents = Agent::query()
            ->with('station')
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('fullname')
            ->get();

        $pdf = Pdf::loadView('pdf.exports.agents', [
            'title' => 'Liste des agents',
            'station' => $station,
            'rows' => $agents,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('agents' . ($station ? ('_station_' . $station->id) : '') . '.pdf');
    }

    public function agentsExcel(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $agents = Agent::query()
            ->with('station')
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('fullname')
            ->get();

        $headers = ['Matricule', 'Nom complet', 'Station', 'Statut', 'Cree le'];
        $table = [];
        foreach ($agents as $a) {
            $table[] = [
                (string) ($a->matricule ?? ''),
                (string) ($a->fullname ?? ''),
                (string) ($a->station?->name ?? ''),
                (string) ($a->status ?? ''),
                $a->created_at ? Carbon::parse($a->created_at)->format('Y-m-d H:i') : '',
            ];
        }

        $meta = [
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'agents' . ($station ? ('_station_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Agents',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function agentAttendancesPdf(Request $request, AttendanceReportService $service): Response
    {
        $payload = $this->buildAgentAttendancesExportPayload(
            $this->validateAgentAttendancesExportRequest($request),
            $service
        );

        $pdf = Pdf::loadView('pdf.exports.agent_attendances', [
            'title' => $payload['title'],
            'metaLines' => $payload['meta'],
            'headers' => $payload['headers'],
            'rows' => $payload['table'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function agentAttendancesExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $payload = $this->buildAgentAttendancesExportPayload(
            $this->validateAgentAttendancesExportRequest($request),
            $service
        );

        return $this->downloadXlsx(
            filename: $payload['filename_base'] . '.xlsx',
            sheetTitle: $payload['sheet_title'],
            metaLines: $payload['meta'],
            headers: $payload['headers'],
            rows: $payload['table'],
        );
    }

    public function horairesPdf(Request $request): Response
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;
        $stationsById = Station::query()->select(['id', 'name'])->get()->keyBy('id');

        $rows = PresenceHoraire::query()
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('libelle')
            ->get();

        $grouped = $rows->groupBy(function ($h) {
            return $h->site_id ?? 'none';
        })->map(function ($items, $key) use ($stationsById) {
            $stationName = 'Station non affectee';
            if ($key !== 'none') {
                $stationName = (string) (optional($stationsById->get((int) $key))->name ?? ('Station ' . $key));
            }

            return [
                'key' => $key,
                'station_name' => $stationName,
                'rows' => $items,
            ];
        })->sortBy('station_name')->values();

        $pdf = Pdf::loadView('pdf.exports.horaires', [
            'title' => 'Liste des horaires',
            'station' => $station,
            'stationsById' => $stationsById,
            'rows' => $rows,
            'grouped' => $grouped,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('horaires' . ($station ? ('_station_' . $station->id) : '') . '.pdf');
    }

    public function horairesExcel(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;
        $stationsById = Station::query()->select(['id', 'name'])->get()->keyBy('id');

        $rows = PresenceHoraire::query()
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('libelle')
            ->get();

        $headers = ['Designation', 'Station', 'Heure debut', 'Controle', 'Heure fin', 'Tolerance (min)'];
        $table = [];
        foreach ($rows as $h) {
            $table[] = [
                (string) ($h->libelle ?? ''),
                (string) (optional($stationsById->get((int) $h->site_id))->name ?? 'Station ' . $h->site_id),
                (string) ($h->started_at ?? ''),
                (string) ($h->mid_check ?? ''),
                (string) ($h->ended_at ?? ''),
                (int) ($h->tolerence_minutes ?? 0),
            ];
        }

        $meta = [
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'horaires' . ($station ? ('_station_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Horaires',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function timesheetMonthlyPdf(Request $request, AttendanceReportService $service): Response
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $stationId = $data['station_id'] ?? null;

        $stations = $stationId
            ? Station::query()->where('id', (int) $stationId)->orderBy('name')->get()
            : Station::query()->orderBy('name')->get();

        $rows = [];
        foreach ($stations as $s) {
            $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $s->id]);
            $rows[] = $this->summarizeStationFromMatrix($s, $matrix['data'], $matrix['agents']);
        }

        $pdf = Pdf::loadView('pdf.exports.timesheet_monthly', [
            'title' => 'Pointage mensuel (RH)',
            'month' => $month,
            'year' => $year,
            'station' => $stationId ? Station::find($stationId) : null,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('timesheet_' . sprintf('%02d', $month) . '_' . $year . ($stationId ? ('_' . $stationId) : '') . '.pdf');
    }

    public function timesheetMonthlyExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $stations = $stationId
            ? Station::query()->where('id', (int) $stationId)->orderBy('name')->get()
            : Station::query()->orderBy('name')->get();

        $headers = ['Station', 'Agents', 'Present', 'Retard', 'Absent', 'Conge', 'Autorisation', 'H. Sup'];
        $table = [];
        foreach ($stations as $s) {
            $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $s->id]);
            $r = $this->summarizeStationFromMatrix($s, $matrix['data'], $matrix['agents']);
            $table[] = [
                (string) $r['station_name'],
                (int) $r['agent_count'],
                (int) $r['total_present'],
                (int) $r['total_retard'],
                (int) $r['total_absent'],
                (int) $r['total_conge'],
                (int) $r['total_autorisation'],
                (string) $r['total_overtime_display'],
            ];
        }

        $meta = [
            'Mois: ' . sprintf('%02d/%d', $month, $year),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'timesheet_' . sprintf('%02d', $month) . '_' . $year . ($stationId ? ('_' . $stationId) : '') . '.xlsx',
            sheetTitle: 'Timesheet Mensuel',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function monthlyPresenceSummaryPdf(Request $request, AttendanceReportService $service): Response
    {
        $payload = $this->buildMonthlySummaryPayload($request, $service);

        if ($request->query('tab') === 'details') {
            $pdf = Pdf::loadView('pdf.exports.presences_monthly_detailed', [
                'title' => $payload['title'],
                'month' => $payload['month'] ?? null,
                'year' => $payload['year'] ?? null,
                'from' => $payload['from'] ?? null,
                'to' => $payload['to'] ?? null,
                'station' => $payload['station'],
                'rows' => $payload['table_data'],
                'days' => $payload['days'],
            ])->setPaper('a3', 'landscape');
        } else {
            $pdf = Pdf::loadView('pdf.exports.presences_monthly_summary', [
                'title' => $payload['title'],
                'month' => $payload['month'] ?? null,
                'year' => $payload['year'] ?? null,
                'from' => $payload['from'] ?? null,
                'to' => $payload['to'] ?? null,
                'station' => $payload['station'],
                'headers' => $payload['headers'],
                'rows' => $payload['table_data'],
            ])->setPaper('a4', 'landscape');
        }

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function monthlyPresenceSummaryExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $payload = $this->buildMonthlySummaryPayload($request, $service);
        return $this->downloadXlsx(
            filename: $payload['filename_base'] . '.xlsx',
            sheetTitle: $payload['sheet_title'],
            metaLines: $payload['meta'],
            headers: $payload['headers'],
            rows: $payload['table'],
        );
    }

    public function weeklyPresenceSummaryPdf(Request $request, AttendanceReportService $service): Response
    {
        $payload = $this->buildWeeklySummaryPayload($request, $service);
        $pdf = Pdf::loadView('pdf.exports.presences_weekly_summary', [
            'title' => $payload['title'],
            'from' => $payload['from'],
            'to' => $payload['to'],
            'station' => $payload['station'],
            'headers' => $payload['headers'],
            'rows' => $payload['table_data'],
            'days' => $payload['days'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function weeklyPresenceSummaryExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $payload = $this->buildWeeklySummaryPayload($request, $service);
        return $this->downloadXlsx(
            filename: $payload['filename_base'] . '.xlsx',
            sheetTitle: $payload['sheet_title'],
            metaLines: $payload['meta'],
            headers: $payload['headers'],
            rows: $payload['table'],
        );
    }

    private function buildWeeklySummaryPayload(Request $request, AttendanceReportService $service): array
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $baseDate = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildWeeklyMatrix($baseDate, ['station_id' => $stationId]);
        $summarized = $this->summarizeMatrix($matrix['data'], $matrix['agents'], 'brut');

        $headers = ['Matricule', 'Nom complet', 'Station'];
        foreach ($matrix['days'] as $day) {
            $headers[] = Carbon::parse($day)->format('D d/m');
        }
        $headers = array_merge($headers, ['Pres.', 'Abs.', 'Ret.', 'Aut.', 'Congé', 'H.Sup']);

        $table = [];
        foreach ($summarized as $r) {
            $row = [
                (string) ($r['agent']['matricule'] ?? ''),
                (string) ($r['agent']['fullname'] ?? ''),
                (string) ($r['agent']['station_name'] ?? ''),
            ];
            foreach ($matrix['days'] as $day) {
                $row[] = (string) ($r['days'][$day] ?? '--');
            }
            $row[] = (int) $r['total_presences'];
            $row[] = (int) $r['total_absences'];
            $row[] = (int) $r['total_retards'];
            $row[] = (int) $r['total_autorisations'];
            $row[] = (int) $r['total_conges'];
            $row[] = (string) $r['overtime_display'];
            $table[] = $row;
        }

        $meta = [
            'Période: ' . $start->toDateString() . ' au ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Agents: ' . count($table),
        ];

        return [
            'title' => 'Résumé hebdomadaire des présences',
            'sheet_title' => 'Résumé Hebdo',
            'filename_base' => 'resume_hebdo_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()),
            'meta' => $meta,
            'headers' => $headers,
            'table' => $table,
            'table_data' => $summarized,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
            'days' => $matrix['days'],
        ];
    }

    private function buildMonthlySummaryPayload(Request $request, AttendanceReportService $service): array
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'tab' => 'nullable|string',
            'matricule_prefix' => 'nullable|string',
        ]);

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;
        $tab = $data['tab'] ?? 'brut';
        $prefix = $data['matricule_prefix'] ?? null;

        $filters = ['station_id' => $stationId];
        if ($prefix) {
            $filters['matricule_prefix'] = $prefix;
        }

        if (!empty($data['from']) && !empty($data['to'])) {
            $filters['from'] = $data['from'];
            $filters['to'] = $data['to'];
            $matrix = $service->buildMonthlyMatrix(0, 0, $filters);
            $start = Carbon::parse($data['from'])->startOfDay();
            $end = Carbon::parse($data['to'])->endOfDay();
            $periodLabel = $start->toDateString() . ' au ' . $end->toDateString();
            $month = null; $year = null;
        } else {
            $month = (int) ($data['month'] ?? Carbon::now()->month);
            $year = (int) ($data['year'] ?? Carbon::now()->year);
            $matrix = $service->buildMonthlyMatrix($month, $year, $filters);
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $periodLabel = $start->translatedFormat('F Y');
        }

        $summarized = $this->summarizeMatrix($matrix['data'], $matrix['agents'], $tab);
        $days = $matrix['days'];

        if ($tab === 'details') {
            $headers = ['Matricule', 'Nom complet', 'Station'];
            foreach ($days as $d) {
                $headers[] = $d;
            }
            $headers = array_merge($headers, ['Total', 'Pres.', 'Abs.', 'Ret.', 'Aut.', 'Congé', 'H.Sup', 'OFF']);

            $table = [];
            foreach ($summarized as $r) {
                $row = [
                    (string) ($r['agent']['matricule'] ?? ''),
                    (string) ($r['agent']['fullname'] ?? ''),
                    (string) ($r['agent']['station_name'] ?? ''),
                ];
                foreach ($days as $d) {
                    $row[] = (string) ($r['days'][$d] ?? '--');
                }
                $row[] = (int) $r['total_count'];
                $row[] = (int) $r['total_presences'];
                $row[] = (int) $r['total_absences'];
                $row[] = (int) $r['total_retards'];
                $row[] = (int) $r['total_autorisations'];
                $row[] = (int) $r['total_conges'];
                $row[] = (string) $r['overtime_display'];
                $row[] = (int) $r['total_off'];
                $table[] = $row;
            }

            $title = 'Rapport détaillé des présences';
            $sheetTitle = 'Détails Période';
            $filenameBase = 'details_presences_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($stationId ? ('_' . $stationId) : '') . ($prefix ? ('_' . $prefix) : '');
        } else {
            $headers = ['Matricule', 'Nom complet', 'Station', 'Present', 'Retard', 'Absent', 'Conge', 'Autorisation', 'Retard Justifie', 'Absence Justifiee', 'H. Norm', 'H. Sup', 'Total Preste'];
            $table = [];
            foreach ($summarized as $r) {
                $table[] = [
                    (string) ($r['agent']['matricule'] ?? ''),
                    (string) ($r['agent']['fullname'] ?? ''),
                    (string) ($r['agent']['station_name'] ?? ''),
                    (int) $r['present'],
                    (int) $r['retard'],
                    (int) $r['absent'],
                    (int) $r['conge'],
                    (int) $r['autorisation'],
                    (int) $r['retard_justifie'],
                    (int) $r['absence_justifiee'],
                    (string) $r['normal_hours_display'],
                    (string) $r['overtime_display'],
                    (int) $r['total_preste'],
                ];
            }
            $title = 'Résumé des présences';
            $sheetTitle = 'Résumé Période';
            $filenameBase = 'resume_presences_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($stationId ? ('_' . $stationId) : '') . ($prefix ? ('_' . $prefix) : '');
        }

        $meta = [
            'Période: ' . $periodLabel,
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Branche (Matricule): ' . ($prefix ?: 'Toutes'),
            'Agents: ' . count($table),
        ];

        return [
            'title' => $title,
            'sheet_title' => $sheetTitle,
            'filename_base' => $filenameBase,
            'meta' => $meta,
            'headers' => $headers,
            'table' => $table,
            'table_data' => $summarized,
            'month' => $month,
            'year' => $year,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
            'days' => $days
        ];
    }

    public function dailyPresencesPdf(Request $request, AttendanceReportService $service): Response
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $agentIds = Agent::query()
                ->where('site_id', (int) $stationId)
                ->pluck('id')
                ->all();
            $query->whereIn('agent_id', $agentIds);
        }

        $rows = $query
            ->orderBy('site_id')
            ->orderBy('started_at')
            ->get();

        $this->attachPresenceMotifs($rows, $date);
        foreach ($rows as $row) {
            $ot = $service->calculateOvertime($row, $row->horaire);
            $norm = $service->calculateNormalHours($row, $ot);
            $row->setAttribute('overtime_minutes', $ot);
            $row->setAttribute('overtime_display', $service->formatOvertime($ot));
            $row->setAttribute('normal_hours_display', $service->formatOvertime($norm));
        }
        $groups = $this->groupPresenceRowsByStation($rows);

        $pdf = Pdf::loadView('pdf.exports.presences_daily', [
            'title' => 'Rapport des presences (journalier)',
            'date' => $date,
            'station' => $station,
            'groups' => $groups,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('presences_journalier_' . str_replace('-', '', $date) . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function dailyPresencesExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $date = Carbon::parse($data['date'] ?? Carbon::today()->toDateString())->toDateString();
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $agentIds = Agent::query()
                ->where('site_id', (int) $stationId)
                ->pluck('id')
                ->all();
            $query->whereIn('agent_id', $agentIds);
        }

        $rows = $query
            ->orderBy('site_id')
            ->orderBy('started_at')
            ->get();

        $this->attachPresenceMotifs($rows, $date);
        $headers = [
            'Station',
            'Matricule',
            'Nom complet',
            'Affectation',
            'Check-in',
            'Check-out',
            'Date',
            'Heure entree',
            'Heure sortie',
            'H. Normales',
            'Heures Sup',
            'Controle intermediaire',
            'Duree Totale',
            'Retard',
            'Motif',
        ];

        $table = [];
        foreach ($rows as $p) {
            $st = $p->assignedStation ?: ($p->stationCheckIn ?: $p->stationCheckOut);
            $ot = $service->calculateOvertime($p, $p->horaire);
            $norm = $service->calculateNormalHours($p, $ot);
            $table[] = [
                (string) ($st?->name ?? 'Sans station'),
                (string) ($p->agent?->matricule ?? ''),
                (string) ($p->agent?->fullname ?? ''),
                (string) ($p->assignedStation?->name ?? ''),
                (string) ($p->stationCheckIn?->name ?? ''),
                (string) ($p->stationCheckOut?->name ?? ''),
                Carbon::parse($p->date_reference)->toDateString(),
                $p->started_at ? Carbon::parse($p->started_at)->format('H:i') : '',
                $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : '',
                $service->formatOvertime($norm),
                $service->formatOvertime($ot),
                $p->mid_check ? Carbon::parse($p->mid_check)->format('H:i') : '',
                (string) ($p->duree ?? ''),
                (string) ($p->retard ?? ''),
                (string) ($p->motif ?? ''),
            ];
        }

        $meta = [
            'Date: ' . $date,
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'presences_journalier_' . str_replace('-', '', $date) . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Journalier',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function absencesDailyPdf(Request $request, AbsenceReportService $service): Response
    {
        $payload = $this->buildAbsenceReportPayload($request, $service);
        $pdf = Pdf::loadView('pdf.exports.absences_daily', [
            'title' => $payload['title'],
            'from' => $payload['from'],
            'to' => $payload['to'],
            'station' => $payload['station'],
            'rows' => $payload['table_data'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function absencesDailyExcel(Request $request, AbsenceReportService $service): StreamedResponse
    {
        $payload = $this->buildAbsenceReportPayload($request, $service);
        return $this->downloadXlsx(
            filename: $payload['filename_base'] . '.xlsx',
            sheetTitle: $payload['sheet_title'],
            metaLines: $payload['meta'],
            headers: $payload['headers'],
            rows: $payload['table'],
        );
    }

    private function buildAbsenceReportPayload(Request $request, AbsenceReportService $service): array
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $base->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $base->copy()->endOfDay();
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $rows = $service->buildAbsenceRows($start, $end, $stationId);

        $headers = ['Date', 'Matricule', 'Nom complet', 'Station', 'Horaire', 'Heure prevue', 'Justificatif'];
        $table = [];
        foreach ($rows as $r) {
            $table[] = [
                $r['date'],
                (string) ($r['agent']['matricule'] ?? ''),
                (string) ($r['agent']['fullname'] ?? ''),
                (string) ($r['agent']['station_name'] ?? ''),
                (string) ($r['agent']['schedule_label'] ?? ''),
                (string) ($r['agent']['expected_time'] ?? ''),
                (string) $r['justificatif'],
            ];
        }

        $meta = [
            'Periode: ' . $start->toDateString() . ' au ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return [
            'title' => 'Rapport des absences',
            'sheet_title' => 'Absences',
            'filename_base' => 'absences_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()),
            'meta' => $meta,
            'headers' => $headers,
            'table' => $table,
            'table_data' => $rows,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
        ];
    }

    public function latesDailyPdf(Request $request, LateReportService $service): Response
    {
        $payload = $this->buildLateReportPayload($request, $service);
        $pdf = Pdf::loadView('pdf.exports.retards_daily', [
            'title' => $payload['title'],
            'from' => $payload['from'],
            'to' => $payload['to'],
            'station' => $payload['station'],
            'rows' => $payload['table_data'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function latesDailyExcel(Request $request, LateReportService $service): StreamedResponse
    {
        $payload = $this->buildLateReportPayload($request, $service);
        return $this->downloadXlsx(
            filename: $payload['filename_base'] . '.xlsx',
            sheetTitle: $payload['sheet_title'],
            metaLines: $payload['meta'],
            headers: $payload['headers'],
            rows: $payload['table'],
        );
    }

    private function buildLateReportPayload(Request $request, LateReportService $service): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : Carbon::today()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $start->copy();
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $rows = $service->buildLateRows($start, $end, $stationId);

        $headers = ['Date', 'Matricule', 'Nom complet', 'Station', 'Horaire', 'Heure prevue', 'Heure arrivee', 'Retard (min)', 'Justificatif'];
        $table = [];
        foreach ($rows as $r) {
            $table[] = [
                $r['date'],
                (string) ($r['agent']['matricule'] ?? ''),
                (string) ($r['agent']['fullname'] ?? ''),
                (string) ($r['agent']['station_name'] ?? ''),
                (string) ($r['agent']['schedule_label'] ?? ''),
                (string) ($r['expected_time'] ?? ''),
                (string) ($r['arrival_time'] ?? ''),
                (int) ($r['late_minutes'] ?? 0),
                (string) ($r['justificatif'] ?? ''),
            ];
        }

        $meta = [
            'Periode: ' . $start->toDateString() . ' au ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return [
            'title' => 'Rapport des retards',
            'sheet_title' => 'Retards',
            'filename_base' => 'retards_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()),
            'meta' => $meta,
            'headers' => $headers,
            'table' => $table,
            'table_data' => $rows,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
        ];
    }

    public function cumulativeAlertsPdf(Request $request, CumulativeAlertService $service): Response
    {
        $data = $request->validate([
            'type' => 'nullable|string|in:absences,retards,departs',
            'period' => 'nullable|string|in:daily,weekly,monthly',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'threshold' => 'nullable|integer|min:1|max:31',
        ]);

        $type = (string) ($data['type'] ?? 'absences');
        $threshold = (int) ($data['threshold'] ?? 3);
        $displayThreshold = $type === 'departs' ? null : $threshold;
        $stationId = isset($data['station_id']) ? (int) $data['station_id'] : null;
        $station = $stationId ? Station::find($stationId) : null;

        if ($type === 'absences' && !optional($request->user())->can('rapport_absences.export')) {
            abort(403, 'Acces refuse.');
        }
        if ($type === 'retards' && !optional($request->user())->can('rapport_retards.export')) {
            abort(403, 'Acces refuse.');
        }
        if ($type === 'departs' && !optional($request->user())->can('rapport_presences.export')) {
            abort(403, 'Acces refuse.');
        }

        $range = $service->resolveRange($data);
        $alerts = $service->buildAlerts(
            start: $range['start'],
            end: $range['end'],
            stationId: $stationId,
            threshold: $threshold,
        );
        $rows = $type === 'retards'
            ? ($alerts['retards'] ?? [])
            : ($type === 'departs' ? ($alerts['departs'] ?? []) : ($alerts['absences'] ?? []));
        $typeLabel = $type === 'retards'
            ? 'Alertes retards'
            : ($type === 'departs' ? 'Alertes departs anticipes' : 'Alertes absences');

        $pdf = Pdf::loadView('pdf.exports.alerts_cumulative', [
            'title' => $typeLabel,
            'typeLabel' => $typeLabel,
            'from' => $range['start']->toDateString(),
            'to' => $range['end']->toDateString(),
            'station' => $station,
            'threshold' => $displayThreshold,
            'type' => $type,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('alertes_' . $type . '_' . str_replace('-', '', $range['start']->toDateString()) . '_' . str_replace('-', '', $range['end']->toDateString()) . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function cumulativeAlertsExcel(Request $request, CumulativeAlertService $service): StreamedResponse
    {
        $data = $request->validate([
            'type' => 'nullable|string|in:absences,retards,departs',
            'period' => 'nullable|string|in:daily,weekly,monthly',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'threshold' => 'nullable|integer|min:1|max:31',
        ]);

        $type = (string) ($data['type'] ?? 'absences');
        $threshold = (int) ($data['threshold'] ?? 3);
        $displayThreshold = $type === 'departs' ? null : $threshold;
        $stationId = isset($data['station_id']) ? (int) $data['station_id'] : null;
        $station = $stationId ? Station::find($stationId) : null;

        if ($type === 'absences' && !optional($request->user())->can('rapport_absences.export')) {
            abort(403, 'Acces refuse.');
        }
        if ($type === 'retards' && !optional($request->user())->can('rapport_retards.export')) {
            abort(403, 'Acces refuse.');
        }
        if ($type === 'departs' && !optional($request->user())->can('rapport_presences.export')) {
            abort(403, 'Acces refuse.');
        }

        $range = $service->resolveRange($data);
        $alerts = $service->buildAlerts(
            start: $range['start'],
            end: $range['end'],
            stationId: $stationId,
            threshold: $threshold,
        );
        $rows = $type === 'retards'
            ? ($alerts['retards'] ?? [])
            : ($type === 'departs' ? ($alerts['departs'] ?? []) : ($alerts['absences'] ?? []));
        $typeLabel = $type === 'retards'
            ? 'Alertes retards'
            : ($type === 'departs' ? 'Alertes departs anticipes' : 'Alertes absences');

        $headers = $type === 'departs'
            ? ['Mois', 'Matricule', 'Nom complet', 'Station', 'Groupe', 'Cumul', 'Date', 'Heure prevue', 'Heure depart', 'Regle', 'Action']
            : ['Mois', 'Matricule', 'Nom complet', 'Station', 'Groupe', 'Cumul', 'Regle', 'Action'];
        $table = [];
        foreach ($rows as $r) {
            $a = $r['agent'] ?? [];
            $ruleLabel = $type === 'departs'
                ? 'Sans seuil'
                : ('>= ' . (int) ($r['threshold'] ?? $threshold));
            if ($type === 'departs') {
                $table[] = [
                    (string) ($r['month_label'] ?? ''),
                    (string) ($a['matricule'] ?? ''),
                    (string) ($a['fullname'] ?? ''),
                    (string) ($a['station_name'] ?? ''),
                    (string) ($a['group_name'] ?? ''),
                    (int) ($r['count'] ?? 0),
                    (string) ($r['departure_date'] ?? ''),
                    (string) ($r['expected_departure_time'] ?? ''),
                    (string) ($r['actual_departure_time'] ?? ''),
                    $ruleLabel,
                    (string) ($r['action_label'] ?? "Lettre d'explication requise"),
                ];
                continue;
            }

            $table[] = [
                (string) ($r['month_label'] ?? ''),
                (string) ($a['matricule'] ?? ''),
                (string) ($a['fullname'] ?? ''),
                (string) ($a['station_name'] ?? ''),
                (string) ($a['group_name'] ?? ''),
                (int) ($r['count'] ?? 0),
                $ruleLabel,
                (string) ($r['action_label'] ?? "Lettre d'explication requise"),
            ];
        }

        $meta = [
            'Type: ' . $typeLabel,
            'Periode: ' . $range['start']->toDateString() . ' -> ' . $range['end']->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Regle: ' . ($type === 'departs' ? 'Sans seuil' : ('Seuil >= ' . $displayThreshold)),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'alertes_' . $type . '_' . str_replace('-', '', $range['start']->toDateString()) . '_' . str_replace('-', '', $range['end']->toDateString()) . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Alertes ' . ($type === 'retards' ? 'retards' : ($type === 'departs' ? 'departs' : 'absences')),
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function maintenanceReportPdf(Request $request): Response
    {
        $payload = $this->buildMaintenanceReportPayload($request);
        $pdf = Pdf::loadView('pdf.exports.maintenances_report', [
            'title' => $payload['title'],
            'metaLines' => $payload['meta'],
            'headers' => $payload['headers'],
            'rows' => $payload['table'],
            'summary' => $payload['summary'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function maintenanceReportExcel(Request $request): StreamedResponse
    {
        $payload = $this->buildMaintenanceReportPayload($request);
        return $this->downloadXlsx(
            filename: $payload['filename_base'] . '.xlsx',
            sheetTitle: $payload['sheet_title'],
            metaLines: $payload['meta'],
            headers: $payload['headers'],
            rows: $payload['table'],
        );
    }

    private function buildMaintenanceReportPayload(Request $request): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'agent_id' => 'nullable|integer|exists:agents,id',
        ]);

        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : Carbon::today()->startOfMonth();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->endOfDay() : Carbon::today()->endOfDay();
        $stationId = $data['station_id'] ?? null;
        $agentId = $data['agent_id'] ?? null;

        $query = MaintenanceAgent::query()
            ->with(['agent.station', 'station'])
            ->whereDate('date_maintenance', '>=', $start->toDateString())
            ->whereDate('date_maintenance', '<=', $end->toDateString())
            ->when($stationId, fn($q) => $q->where('station_id', (int) $stationId))
            ->when($agentId, fn($q) => $q->where('agent_id', (int) $agentId));

        $rows = $query->orderByDesc('date_maintenance')->orderByDesc('started_at')->get();
        $station = $stationId ? Station::find($stationId) : null;
        $agent = $agentId ? Agent::find($agentId) : null;

        $total = $rows->count();
        $completed = $rows->whereNotNull('end_at')->count();
        $ongoing = $total - $completed;

        $headers = ['Agent', 'Matricule', 'Station', 'Date', 'Debut', 'Fin', 'Statut', 'Distance', 'Commentaire'];
        $table = [];
        foreach ($rows as $m) {
            $table[] = [
                (string) ($m->agent?->fullname ?? ''),
                (string) ($m->agent?->matricule ?? ''),
                (string) ($m->station?->name ?? ''),
                $m->getRawOriginal('date_maintenance'),
                $m->started_at ? Carbon::parse($m->started_at)->format('H:i') : '',
                $m->end_at ? Carbon::parse($m->end_at)->format('H:i') : '',
                $m->end_at ? 'Cloturee' : 'En cours',
                $this->extractMaintenanceDistanceLabel((string) $m->commentaire),
                (string) ($m->commentaire ?? ''),
            ];
        }

        $meta = [
            'Periode: ' . $start->toDateString() . ' au ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Agent: ' . ($agent?->fullname ?? 'Tous'),
            'Interventions: ' . $total . ' (Terminees: ' . $completed . ', En cours: ' . $ongoing . ')',
        ];

        return [
            'title' => 'Rapport des Maintenances',
            'sheet_title' => 'Maintenances',
            'filename_base' => 'rapport_maintenances_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()),
            'meta' => $meta,
            'headers' => $headers,
            'table' => $table,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'ongoing' => $ongoing,
            ]
        ];
    }

    private function summarizeMatrix(array $matrix, $agentsCollection, string $tab = 'brut'): array
    {
        $agentsByKey = [];
        foreach ($agentsCollection as $a) {
            $key = $a->fullname . ' (' . $a->matricule . ')';
            $agentsByKey[$key] = [
                'id' => $a->id,
                'fullname' => $a->fullname,
                'matricule' => $a->matricule,
                'photo' => $a->photo,
                'station_id' => $a->site_id,
                'station_name' => $a->station?->name,
            ];
        }

        $service = app(AttendanceReportService::class);
        $rows = [];
        foreach ($matrix as $agentKey => $days) {
            $acc = [
                'agent_key' => $agentKey,
                'agent' => $agentsByKey[$agentKey] ?? ['fullname' => $agentKey, 'matricule' => '', 'station_name' => null],
                'present' => 0,
                'retard' => 0,
                'absent' => 0,
                'conge' => 0,
                'autorisation' => 0,
                'retard_justifie' => 0,
                'absence_justifiee' => 0,
                'total_preste' => 0,
                'total_overtime_minutes' => 0,
                'total_normal_minutes' => 0,
                // Fields for details
                'days' => [],
                'total_count' => 0,
                'total_presences' => 0,
                'total_absences' => 0,
                'total_retards' => 0,
                'total_autorisations' => 0,
                'total_conges' => 0,
                'total_off' => 0,
                'total_others' => 0,
            ];

            foreach (($days ?? []) as $dayKey => $cell) {
                $s = $cell['status'] ?? null;
                $mapped = $this->mapStatusToCode($s);
                $acc['days'][$dayKey] = $mapped['code'];

                if ($s === 'present') $acc['present'] += 1;
                else if ($s === 'retard') {
                    $acc['present'] += 1;
                    $acc['retard'] += 1;
                }
                else if ($s === 'retard_justifie') {
                    $acc['present'] += 1;
                    $acc['retard'] += 1;
                    $acc['retard_justifie'] += 1;
                }
                else if ($s === 'absent') $acc['absent'] += 1;
                else if ($s === 'conge') $acc['conge'] += 1;
                else if ($s === 'autorisation') $acc['autorisation'] += 1;
                else if ($s === 'absence_justifiee') $acc['absence_justifiee'] += 1;

                if (isset($cell['overtime_minutes'])) {
                    $acc['total_overtime_minutes'] += (int) $cell['overtime_minutes'];
                }
                if (isset($cell['duration_minutes'])) {
                    $acc['total_normal_minutes'] += (max(0, (int)$cell['duration_minutes'] - (int)($cell['overtime_minutes'] ?? 0)));
                }

                if ($mapped['bucket']) {
                    $acc['total_count'] += 1;
                    if ($mapped['bucket'] === 'presence') $acc['total_presences'] += 1;
                    elseif ($mapped['bucket'] === 'retard') { $acc['total_presences'] += 1; $acc['total_retards'] += 1; }
                    elseif ($mapped['bucket'] === 'absence') $acc['total_absences'] += 1;
                    elseif ($mapped['bucket'] === 'autorisation') $acc['total_autorisations'] += 1;
                    elseif ($mapped['bucket'] === 'conge') $acc['total_conges'] += 1;
                    elseif ($mapped['bucket'] === 'off') $acc['total_off'] += 1;
                    else $acc['total_others'] += 1;
                }
            }

            $acc['total_preste'] = $acc['present'] + $acc['absence_justifiee'];
            $acc['overtime_display'] = $service->formatOvertime($acc['total_overtime_minutes']);
            $acc['normal_hours_display'] = $service->formatOvertime($acc['total_normal_minutes']);
            $rows[] = $acc;
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($a['agent']['fullname'] ?? ''), (string) ($b['agent']['fullname'] ?? '')));

        return $rows;
    }

    private function mapStatusToCode(?string $status): array
    {
        switch ($status) {
            case "present":
                return ['code' => '1', 'bucket' => 'presence'];
            case "retard":
            case "retard_justifie":
                return ['code' => '1-R', 'bucket' => 'retard'];
            case "absent":
            case "absence_justifiee":
                return ['code' => 'A', 'bucket' => 'absence'];
            case "off":
                return ['code' => 'OFF', 'bucket' => 'off'];
            case "conge":
                return ['code' => 'C', 'bucket' => 'conge'];
            case "autorisation":
                return ['code' => 'AS', 'bucket' => 'autorisation'];
            case "future":
                return ['code' => '--', 'bucket' => null];
            case "unplanned":
                return ['code' => 'AUT', 'bucket' => 'other'];
            default:
                return ['code' => 'AUT', 'bucket' => 'other'];
        }
    }

    private function groupPresenceRowsByStation(Collection $rows): array
    {
        $map = [];
        foreach ($rows as $r) {
            $station = $r->assignedStation ?: ($r->stationCheckIn ?: ($r->stationCheckOut ?: null));
            $stationId = $station?->id;
            $stationName = $station?->name ?? 'Sans station';
            $key = $stationId ? ('station:' . $stationId) : ('name:' . $stationName);

            if (!isset($map[$key])) {
                $map[$key] = [
                    'key' => $key,
                    'station_id' => $stationId,
                    'station_name' => $stationName,
                    'rows' => collect(),
                ];
            }
            $map[$key]['rows']->push($r);
        }

        $groups = array_values($map);
        usort($groups, fn ($a, $b) => strcmp((string) $a['station_name'], (string) $b['station_name']));
        return $groups;
    }

    private function attachPresenceMotifs(Collection $rows, string $date): void
    {
        $agentIds = $rows->pluck('agent_id')->filter()->unique()->values()->all();
        if (empty($agentIds)) {
            return;
        }

        $authorizations = AttendanceAuthorization::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_reference', $date)
            ->get()
            ->groupBy('agent_id');

        $justifications = AttendanceJustification::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_reference', $date)
            ->get()
            ->groupBy('agent_id');

        $conges = Conge::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date)
            ->get()
            ->groupBy('agent_id');

        foreach ($rows as $p) {
            $motifs = [];
            $auth = optional($authorizations->get($p->agent_id ?? null))->first();
            if ($auth) {
                $label = 'Autorisation';
                if (!empty($auth->reason)) {
                    $label .= ': ' . $auth->reason;
                } elseif (!empty($auth->type)) {
                    $label .= ': ' . strtoupper((string) $auth->type);
                }
                $motifs[] = $label;
            }

            $justif = optional($justifications->get($p->agent_id ?? null))->first();
            if ($justif) {
                $label = $justif->kind === 'retard' ? 'Retard justifie' : 'Absence justifiee';
                if (!empty($justif->justification)) {
                    $label .= ': ' . $justif->justification;
                }
                $motifs[] = $label;
            }

            $conge = optional($conges->get($p->agent_id ?? null))->first();
            if ($conge) {
                $label = 'Conge';
                if (!empty($conge->motif)) {
                    $label .= ': ' . $conge->motif;
                }
                $motifs[] = $label;
            }

            if (($p->retard ?? '') === 'oui' && !$justif) {
                $motifs[] = 'Retard';
            }

            $p->setAttribute('motif', implode(' | ', $motifs));
        }
    }

    private function validateAgentAttendancesExportRequest(Request $request): array
    {
        return $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'dataset' => 'required|string|in:presences,maintenances',
            'scope' => 'nullable|string|in:global,filtered',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
            'status' => 'nullable|string|in:present,absent,late',
        ]);
    }

    private function buildAgentAttendancesExportPayload(array $data, AttendanceReportService $service): array
    {
        $agent = Agent::query()->with('station')->findOrFail((int) $data['agent_id']);
        $dataset = (string) $data['dataset'];
        $scope = (string) ($data['scope'] ?? 'filtered');
        $applyFilters = $scope === 'filtered';
        $stationId = isset($data['station_id']) ? (int) $data['station_id'] : null;
        $station = $stationId ? Station::find($stationId) : null;

        if ($dataset === 'presences') {
            $query = PresenceAgents::query()
                ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
                ->where('agent_id', (int) $agent->id);

            if ($applyFilters) {
                if (!empty($data['from'])) {
                    $query->whereDate('date_reference', '>=', $data['from']);
                }
                if (!empty($data['to'])) {
                    $query->whereDate('date_reference', '<=', $data['to']);
                }
                if ($stationId !== null) {
                    $query->where(function ($q) use ($stationId) {
                        $q->where('site_id', $stationId)
                            ->orWhere('station_check_in_id', $stationId)
                            ->orWhere('station_check_out_id', $stationId);
                    });
                }

                $status = (string) ($data['status'] ?? '');
                if ($status === 'present') {
                    $query->whereNotNull('started_at');
                } elseif ($status === 'absent') {
                    $query->whereNull('started_at');
                } elseif ($status === 'late') {
                    $query->where('retard', 'oui');
                }
            }

            $rows = $query
                ->orderByDesc('date_reference')
                ->orderByDesc('started_at')
                ->get();

            $headers = [
                'Date',
                'Station affectation',
                'Check-in',
                'Check-out',
                'Heure entree',
                'Controle intermediaire',
                'Heure sortie',
                'H. Normales',
                'Heures Sup',
                'Retard',
                'Duree Totale',
            ];

            $table = [];
            foreach ($rows as $p) {
                $ot = $service->calculateOvertime($p, $p->horaire);
                $norm = $service->calculateNormalHours($p, $ot);
                $table[] = [
                    (string) ($p->getRawOriginal('date_reference') ?? ''),
                    (string) ($p->assignedStation?->name ?? ''),
                    (string) ($p->stationCheckIn?->name ?? ''),
                    (string) ($p->stationCheckOut?->name ?? ''),
                    $p->started_at ? Carbon::parse($p->started_at)->format('H:i') : '',
                    $p->mid_check ? Carbon::parse($p->mid_check)->format('H:i') : '',
                    $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : '',
                    $service->formatOvertime($norm),
                    $service->formatOvertime($ot),
                    (string) ($p->retard ?? 'non'),
                    (string) ($p->duree ?? ''),
                ];
            }

            $meta = [
                'Agent: ' . $agent->fullname . ' (' . $agent->matricule . ')',
                'Jeu: Presences',
                'Portee: ' . ($applyFilters ? 'Filtres actifs' : 'Globale'),
                'Lignes: ' . count($table),
            ];

            return [
                'title' => 'Historique agent - Presences',
                'sheet_title' => 'Presences agent',
                'filename_base' => 'agent_' . ($agent->matricule ?: $agent->id) . '_presences_' . $scope,
                'dataset' => 'presences',
                'meta' => $meta,
                'headers' => $headers,
                'table' => $table,
                'rows' => $rows,
            ];
        }

        $query = MaintenanceAgent::query()
            ->with(['agent.station', 'station'])
            ->where('agent_id', (int) $agent->id);

        if ($applyFilters) {
            if (!empty($data['from'])) {
                $query->whereDate('date_maintenance', '>=', $data['from']);
            }
            if (!empty($data['to'])) {
                $query->whereDate('date_maintenance', '<=', $data['to']);
            }
            if ($stationId !== null) {
                $query->where('station_id', $stationId);
            }
        }

        $rows = $query
            ->orderByDesc('date_maintenance')
            ->orderByDesc('started_at')
            ->get();

        $headers = [
            'Date',
            'Station',
            'Heure debut',
            'Heure fin',
            'Distance',
            'Photo debut',
            'Photo fin',
            'Statut',
            'Commentaire',
        ];

        $table = [];
        foreach ($rows as $m) {
            $table[] = [
                (string) ($m->getRawOriginal('date_maintenance') ?? ''),
                (string) ($m->station?->name ?? ''),
                $m->started_at ? Carbon::parse($m->started_at)->format('H:i') : '',
                $m->end_at ? Carbon::parse($m->end_at)->format('H:i') : '',
                $this->extractMaintenanceDistanceLabel((string) ($m->commentaire ?? '')),
                (string) ($m->photo_debut ?? ''),
                (string) ($m->photo_fin ?? ''),
                $m->end_at ? 'Cloturee' : 'En cours',
                (string) ($m->commentaire ?? ''),
            ];
        }

        $meta = [
            'Agent: ' . $agent->fullname . ' (' . $agent->matricule . ')',
            'Jeu: Maintenances',
            'Lignes: ' . count($table),
        ];

        return [
            'title' => 'Historique agent - Maintenances',
            'sheet_title' => 'Maintenances agent',
            'filename_base' => 'agent_' . ($agent->matricule ?: $agent->id) . '_maintenances_' . $scope,
            'dataset' => 'maintenances',
            'meta' => $meta,
            'headers' => $headers,
            'table' => $table,
            'rows' => $rows,
        ];
    }

    private function summarizeStationFromMatrix(Station $station, array $data, $agents): array
    {
        $res = [
            'station_id' => $station->id,
            'station_name' => $station->name,
            'agent_count' => count($agents),
            'total_present' => 0,
            'total_absent' => 0,
            'total_retard' => 0,
            'total_conge' => 0,
            'total_autorisation' => 0,
            'total_overtime_minutes' => 0,
        ];

        foreach ($data as $agentKey => $days) {
            foreach ($days as $cell) {
                $status = $cell['status'] ?? '';
                if ($status === 'present') $res['total_present']++;
                elseif ($status === 'absent') $res['total_absent']++;
                elseif ($status === 'retard' || $status === 'retard_justifie') {
                    $res['total_present']++;
                    $res['total_retard']++;
                }
                elseif ($status === 'conge') $res['total_conge']++;
                elseif ($status === 'autorisation') $res['total_autorisation']++;

                $res['total_overtime_minutes'] += ($cell['overtime_minutes'] ?? 0);
            }
        }
        $service = app(AttendanceReportService::class);
        $res['total_overtime_display'] = $service->formatOvertime($res['total_overtime_minutes']);

        return $res;
    }

    private function downloadXlsx(string $filename, string $sheetTitle, array $metaLines, array $headers, array $rows): StreamedResponse
    {
        return new StreamedResponse(function () use ($sheetTitle, $metaLines, $headers, $rows) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($sheetTitle, 0, 31));

            $colCount = max(count($headers), 1);
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

            $r = 1;
            $sheet->setCellValue("A{$r}", $sheetTitle);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
            $r += 1;

            foreach ($metaLines as $line) {
                $sheet->setCellValue("A{$r}", (string) $line);
                $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
                $r += 1;
            }

            $r += 1;
            $headerRow = $r;
            foreach ($headers as $i => $h) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValue("{$col}{$headerRow}", $h);
            }

            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
                ->getFont()->setBold(true);
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEFEFEF');

            $r += 1;
            foreach ($rows as $row) {
                foreach ($row as $i => $val) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->setCellValue("{$col}{$r}", $val);
                }
                $r += 1;
            }

            $sheet->freezePane('A' . ($headerRow + 1));
            $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$headerRow}");

            for ($col = 1; $col <= count($headers); $col += 1) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }

            $lastColumn = $sheet->getHighestColumn();
            $lastRow = $sheet->getHighestRow();
            $dataRange = "A{$headerRow}:{$lastColumn}{$lastRow}";
            $sheet->getStyle($dataRange)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()
                ->setARGB('FFE5E7EB');

            $sheet->getStyle($dataRange)
                ->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function extractMaintenanceDistanceLabel(?string $commentaire): string
    {
        $text = (string) ($commentaire ?? '');
        $debutDistance = null;
        $finDistance = null;

        if (preg_match('/Debut\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $debutDistance = (int) $m[1];
        }

        if (preg_match('/Fin\\s+distance:\\s*(\\d+)\\s*m/i', $text, $m)) {
            $finDistance = (int) $m[1];
        }

        $distance = $finDistance ?? $debutDistance;
        return $distance !== null ? ($distance . ' m') : 'Distance indisponible';
    }
}
