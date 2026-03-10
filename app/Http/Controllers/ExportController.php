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
    public function attendancesPdf(Request $request): Response
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

        $pdf = Pdf::loadView('pdf.exports.attendances', [
            'title' => 'Journal de pointage',
            'date' => $date,
            'station' => $station,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        $suffix = $station ? ('_' . $station->id) : '';
        return $pdf->download('journal_pointage_' . str_replace('-', '', $date) . $suffix . '.pdf');
    }

    public function attendancesExcel(Request $request): StreamedResponse
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
            'Controle intermediaire',
            'Duree',
            'Retard',
        ];

        $table = [];
        foreach ($rows as $p) {
            $table[] = [
                (string) ($p->agent?->matricule ?? ''),
                (string) ($p->agent?->fullname ?? ''),
                (string) ($p->assignedStation?->name ?? ''),
                (string) ($p->stationCheckIn?->name ?? ''),
                (string) ($p->stationCheckOut?->name ?? ''),
                Carbon::parse($p->date_reference)->toDateString(),
                $p->started_at ? Carbon::parse($p->started_at)->format('H:i') : '',
                $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : '',
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

    public function agentAttendancesPdf(Request $request): Response
    {
        $payload = $this->buildAgentAttendancesExportPayload(
            $this->validateAgentAttendancesExportRequest($request)
        );

        $pdf = Pdf::loadView('pdf.exports.agent_attendances', [
            'title' => $payload['title'],
            'metaLines' => $payload['meta'],
            'headers' => $payload['headers'],
            'rows' => $payload['table'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($payload['filename_base'] . '.pdf');
    }

    public function agentAttendancesExcel(Request $request): StreamedResponse
    {
        $payload = $this->buildAgentAttendancesExportPayload(
            $this->validateAgentAttendancesExportRequest($request)
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
        $stations = Station::query()->select(['id', 'name'])->get()->keyBy('id');

        $rows = PresenceHoraire::query()
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('libelle')
            ->get();

        $headers = ['Designation', 'Station', 'Heure debut', 'Controle intermediaire', 'Heure fin', 'Tolerance (min)'];
        $table = [];
        $grouped = $rows->groupBy(function ($h) {
            return $h->site_id ?? 'none';
        })->map(function ($items, $key) use ($stations) {
            $stationName = 'Station non affectee';
            if ($key !== 'none') {
                $stationName = (string) (optional($stations->get((int) $key))->name ?? ('Station ' . $key));
            }

            return [
                'key' => $key,
                'station_name' => $stationName,
                'rows' => $items,
            ];
        })->sortBy('station_name')->values();

        foreach ($grouped as $group) {
            $table[] = [
                'Station: ' . $group['station_name'],
                '',
                '',
                '',
                '',
                '',
            ];
            foreach ($group['rows'] as $h) {
                $table[] = [
                    (string) ($h->libelle ?? ''),
                    (string) (optional($stations->get((int) $h->site_id))->name ?? ''),
                    (string) ($h->started_at ?? ''),
                    (string) ($h->mid_check ?? ''),
                    (string) ($h->ended_at ?? ''),
                    (int) ($h->tolerence_minutes ?? 0),
                ];
            }
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

        $headers = ['Station', 'Agents', 'Present', 'Retard', 'Absent', 'Conge', 'Autorisation'];
        $table = [];
        foreach ($stations as $s) {
            $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $s->id]);
            $sum = $this->summarizeStationFromMatrix($s, $matrix['data'], $matrix['agents']);
            $table[] = [
                (string) ($sum['station'] ?? ''),
                (int) ($sum['agents'] ?? 0),
                (int) ($sum['present'] ?? 0),
                (int) ($sum['retard'] ?? 0),
                (int) ($sum['absent'] ?? 0),
                (int) ($sum['conge'] ?? 0),
                (int) ($sum['autorisation'] ?? 0),
            ];
        }

        $meta = [
            'Mois: ' . sprintf('%02d', $month) . '/' . $year,
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'timesheet_' . sprintf('%02d', $month) . '_' . $year . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Timesheet',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function dailyPresencesPdf(Request $request): Response
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
        $groups = $this->groupPresenceRowsByStation($rows);

        $pdf = Pdf::loadView('pdf.exports.presences_daily', [
            'title' => 'Rapport des presences (journalier)',
            'date' => $date,
            'station' => $station,
            'groups' => $groups,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('presences_journalier_' . str_replace('-', '', $date) . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function dailyPresencesExcel(Request $request): StreamedResponse
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
            'Controle intermediaire',
            'Duree',
            'Retard',
            'Motif',
        ];

        $table = [];
        foreach ($rows as $p) {
            $st = $p->assignedStation ?: ($p->stationCheckIn ?: $p->stationCheckOut);
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
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $base->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $base->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $rows = $service->buildAbsenceRows($start, $end, $stationId);

        $pdf = Pdf::loadView('pdf.exports.absences_daily', [
            'title' => 'Rapport des absences',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('absences_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function absencesDailyExcel(Request $request, AbsenceReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $base->copy()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $base->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $rows = $service->buildAbsenceRows($start, $end, $stationId);

        $headers = ['Date', 'Matricule', 'Nom complet', 'Station', 'Groupe', 'Horaire', 'Heure attendue', 'Justificatif'];
        $table = [];
        foreach ($rows as $r) {
            $a = $r['agent'] ?? [];
            $table[] = [
                (string) ($r['date'] ?? ''),
                (string) ($a['matricule'] ?? ''),
                (string) ($a['fullname'] ?? ''),
                (string) ($a['station_name'] ?? ''),
                (string) ($a['group_name'] ?? ''),
                (string) ($a['schedule_label'] ?? ''),
                (string) ($a['expected_time'] ?? ''),
                (string) ($r['justificatif'] ?? ''),
            ];
        }

        $meta = [
            'Periode: ' . $start->toDateString() . ' -> ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'absences_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Absences',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function latesDailyPdf(Request $request, LateReportService $service): Response
    {
        $data = $request->validate([
            'period' => 'nullable|string|in:daily,weekly,monthly',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $period = (string) ($data['period'] ?? 'daily');
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : Carbon::today()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $start->copy();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }
        if ($period === 'weekly') {
            $start = $start->copy()->startOfWeek(Carbon::MONDAY);
            $end = $end->copy()->endOfWeek(Carbon::MONDAY)->startOfDay();
        } elseif ($period === 'monthly') {
            $start = $start->copy()->startOfMonth();
            $end = $end->copy()->endOfMonth()->startOfDay();
        }

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $rows = $service->buildLateRows($start, $end, $stationId ? (int) $stationId : null);

        $pdf = Pdf::loadView('pdf.exports.retards_daily', [
            'title' => 'Rapport des retards',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('retards_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function latesDailyExcel(Request $request, LateReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'period' => 'nullable|string|in:daily,weekly,monthly',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $period = (string) ($data['period'] ?? 'daily');
        $start = !empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : Carbon::today()->startOfDay();
        $end = !empty($data['to']) ? Carbon::parse($data['to'])->startOfDay() : $start->copy();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }
        if ($period === 'weekly') {
            $start = $start->copy()->startOfWeek(Carbon::MONDAY);
            $end = $end->copy()->endOfWeek(Carbon::MONDAY)->startOfDay();
        } elseif ($period === 'monthly') {
            $start = $start->copy()->startOfMonth();
            $end = $end->copy()->endOfMonth()->startOfDay();
        }

        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $rows = $service->buildLateRows($start, $end, $stationId ? (int) $stationId : null);

        $headers = ['Date', 'Matricule', 'Nom complet', 'Station', 'Groupe', 'Horaire', 'Heure prevue', 'Heure arrivee', 'Retard (min)', 'Justificatif'];
        $table = [];
        foreach ($rows as $r) {
            $a = $r['agent'] ?? [];
            $table[] = [
                (string) ($r['date'] ?? ''),
                (string) ($a['matricule'] ?? ''),
                (string) ($a['fullname'] ?? ''),
                (string) ($a['station_name'] ?? ''),
                (string) ($a['group_name'] ?? ''),
                (string) ($a['schedule_label'] ?? ''),
                (string) ($r['expected_time'] ?? ''),
                (string) ($r['arrival_time'] ?? ''),
                (int) ($r['late_minutes'] ?? 0),
                (string) ($r['justificatif'] ?? ''),
            ];
        }

        $meta = [
            'Periode: ' . $start->toDateString() . ' -> ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'retards_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Retards',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
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

    public function weeklyPresenceSummaryPdf(Request $request, AttendanceReportService $service): Response
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $base->copy()->startOfWeek();
        $end = $base->copy()->endOfWeek();
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildWeeklyMatrix($base, ['station_id' => $stationId]);
        $rows = $this->summarizeMatrix($matrix['data'], $matrix['agents']);

        $pdf = Pdf::loadView('pdf.exports.presences_weekly_summary', [
            'title' => 'Rapport des presences (hebdomadaire)',
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'station' => $station,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('presences_hebdo_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function weeklyPresenceSummaryExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'station_id' => 'nullable|integer|exists:sites,id',
        ]);

        $base = Carbon::parse($data['date'] ?? Carbon::today()->toDateString());
        $start = $base->copy()->startOfWeek();
        $end = $base->copy()->endOfWeek();
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildWeeklyMatrix($base, ['station_id' => $stationId]);
        $rows = $this->summarizeMatrix($matrix['data'], $matrix['agents']);

        $headers = ['Agent', 'Matricule', 'Station', 'Present', 'Retard', 'Absent', 'Conge', 'Autorisation', 'Justif retard', 'Justif absence', 'Total preste'];
        $table = [];
        foreach ($rows as $r) {
            $a = $r['agent'] ?? [];
            $table[] = [
                (string) ($a['fullname'] ?? ''),
                (string) ($a['matricule'] ?? ''),
                (string) ($a['station_name'] ?? ''),
                (int) ($r['present'] ?? 0),
                (int) ($r['retard'] ?? 0),
                (int) ($r['absent'] ?? 0),
                (int) ($r['conge'] ?? 0),
                (int) ($r['autorisation'] ?? 0),
                (int) ($r['retard_justifie'] ?? 0),
                (int) ($r['absence_justifiee'] ?? 0),
                (int) ($r['total_preste'] ?? 0),
            ];
        }

        $meta = [
            'Semaine: ' . $start->toDateString() . ' -> ' . $end->toDateString(),
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'presences_hebdo_' . str_replace('-', '', $start->toDateString()) . '_' . str_replace('-', '', $end->toDateString()) . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Hebdo',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    public function monthlyPresenceSummaryPdf(Request $request, AttendanceReportService $service): Response
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
            'tab' => 'nullable|string|in:brut,details',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $stationId = $data['station_id'] ?? null;
        $tab = (string) ($data['tab'] ?? 'brut');
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $stationId]);
        if ($tab === 'details') {
            $rows = $this->summarizeMonthlyDetailsMatrix(
                $matrix['data'],
                $matrix['agents'],
                $matrix['days'] ?? []
            );

            $pdf = Pdf::loadView('pdf.exports.presences_monthly_details', [
                'title' => 'Rapport des presences (mensuel - details)',
                'month' => $month,
                'year' => $year,
                'station' => $station,
                'days' => $matrix['days'] ?? [],
                'rows' => $rows,
            ])->setPaper('a3', 'landscape');

            return $pdf->download('presences_mensuel_details_' . sprintf('%02d', $month) . '_' . $year . ($station ? ('_' . $station->id) : '') . '.pdf');
        }

        $rows = $this->summarizeMatrix($matrix['data'], $matrix['agents']);

        $pdf = Pdf::loadView('pdf.exports.presences_monthly_summary', [
            'title' => 'Rapport des presences (mensuel)',
            'month' => $month,
            'year' => $year,
            'station' => $station,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('presences_mensuel_' . sprintf('%02d', $month) . '_' . $year . ($station ? ('_' . $station->id) : '') . '.pdf');
    }

    public function monthlyPresenceSummaryExcel(Request $request, AttendanceReportService $service): StreamedResponse
    {
        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'station_id' => 'nullable|integer|exists:sites,id',
            'tab' => 'nullable|string|in:brut,details',
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $stationId = $data['station_id'] ?? null;
        $tab = (string) ($data['tab'] ?? 'brut');
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $stationId]);
        if ($tab === 'details') {
            $days = $matrix['days'] ?? [];
            $rows = $this->summarizeMonthlyDetailsMatrix(
                $matrix['data'],
                $matrix['agents'],
                $days
            );

            $headers = array_merge(
                ['Matricule', 'Nom complet agent', 'Station'],
                $days,
                ['Total', 'Tot presences', 'Tot absences', 'Tot retard', 'Tot autorisation', 'Tot conge', 'Tot OFF', 'Tot autres']
            );

            $table = [];
            foreach ($rows as $r) {
                $a = $r['agent'] ?? [];
                $line = [
                    (string) ($a['matricule'] ?? ''),
                    (string) ($a['fullname'] ?? ''),
                    (string) ($a['station_name'] ?? ''),
                ];

                foreach ($days as $day) {
                    $line[] = (string) ($r['day_codes'][$day] ?? '--');
                }

                $line[] = (int) ($r['total_count'] ?? 0);
                $line[] = (int) ($r['total_presences'] ?? 0);
                $line[] = (int) ($r['total_absences'] ?? 0);
                $line[] = (int) ($r['total_retards'] ?? 0);
                $line[] = (int) ($r['total_autorisations'] ?? 0);
                $line[] = (int) ($r['total_conges'] ?? 0);
                $line[] = (int) ($r['total_off'] ?? 0);
                $line[] = (int) ($r['total_others'] ?? 0);
                $table[] = $line;
            }

            $meta = [
                'Mois: ' . sprintf('%02d', $month) . '/' . $year,
                'Station: ' . ($station?->name ?? 'Toutes'),
                'Format: Details',
                'Lignes: ' . count($table),
            ];

            return $this->downloadXlsx(
                filename: 'presences_mensuel_details_' . sprintf('%02d', $month) . '_' . $year . ($station ? ('_' . $station->id) : '') . '.xlsx',
                sheetTitle: 'Mensuel details',
                metaLines: $meta,
                headers: $headers,
                rows: $table,
            );
        }

        $rows = $this->summarizeMatrix($matrix['data'], $matrix['agents']);

        $headers = ['Agent', 'Matricule', 'Station', 'Present', 'Retard', 'Absent', 'Conge', 'Autorisation', 'Justif retard', 'Justif absence', 'Total preste'];
        $table = [];
        foreach ($rows as $r) {
            $a = $r['agent'] ?? [];
            $table[] = [
                (string) ($a['fullname'] ?? ''),
                (string) ($a['matricule'] ?? ''),
                (string) ($a['station_name'] ?? ''),
                (int) ($r['present'] ?? 0),
                (int) ($r['retard'] ?? 0),
                (int) ($r['absent'] ?? 0),
                (int) ($r['conge'] ?? 0),
                (int) ($r['autorisation'] ?? 0),
                (int) ($r['retard_justifie'] ?? 0),
                (int) ($r['absence_justifiee'] ?? 0),
                (int) ($r['total_preste'] ?? 0),
            ];
        }

        $meta = [
            'Mois: ' . sprintf('%02d', $month) . '/' . $year,
            'Station: ' . ($station?->name ?? 'Toutes'),
            'Lignes: ' . count($table),
        ];

        return $this->downloadXlsx(
            filename: 'presences_mensuel_' . sprintf('%02d', $month) . '_' . $year . ($station ? ('_' . $station->id) : '') . '.xlsx',
            sheetTitle: 'Mensuel',
            metaLines: $meta,
            headers: $headers,
            rows: $table,
        );
    }

    private function summarizeMatrix(array $matrix, $agentsCollection): array
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
            ];

            foreach (($days ?? []) as $d => $cell) {
                $s = $cell['status'] ?? null;
                if ($s === 'present') $acc['present'] += 1;
                else if ($s === 'retard') {
                    $acc['present'] += 1; // retard = présence
                    $acc['retard'] += 1;
                }
                else if ($s === 'absent') $acc['absent'] += 1;
                else if ($s === 'conge') $acc['conge'] += 1;
                else if ($s === 'autorisation') $acc['autorisation'] += 1;
                else if ($s === 'retard_justifie') {
                    $acc['present'] += 1; // retard justifié = présence
                    $acc['retard'] += 1;
                    $acc['retard_justifie'] += 1;
                }
                else if ($s === 'absence_justifiee') $acc['absence_justifiee'] += 1;
            }

            $acc['total_preste'] = $acc['present'] + $acc['absence_justifiee'];
            $rows[] = $acc;
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($a['agent']['fullname'] ?? ''), (string) ($b['agent']['fullname'] ?? '')));

        return $rows;
    }

    private function summarizeMonthlyDetailsMatrix(array $matrix, $agentsCollection, array $dayKeys): array
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

        $rows = [];
        foreach ($matrix as $agentKey => $days) {
            $acc = [
                'agent_key' => $agentKey,
                'agent' => $agentsByKey[$agentKey] ?? ['fullname' => $agentKey, 'matricule' => '', 'station_name' => null],
                'day_codes' => [],
                'day_buckets' => [],
                'total_count' => 0,
                'total_presences' => 0,
                'total_absences' => 0,
                'total_retards' => 0,
                'total_autorisations' => 0,
                'total_conges' => 0,
                'total_off' => 0,
                'total_others' => 0,
            ];

            foreach ($dayKeys as $day) {
                $status = $days[$day]['status'] ?? 'future';
                $mapped = $this->mapMonthlyDetailStatus($status);
                $bucket = $mapped['bucket'];

                $acc['day_codes'][$day] = $mapped['code'];
                $acc['day_buckets'][$day] = $bucket;

                if ($bucket === null) {
                    continue;
                }

                $acc['total_count'] += 1;

                if ($bucket === 'presence') {
                    $acc['total_presences'] += 1;
                } elseif ($bucket === 'retard') {
                    $acc['total_presences'] += 1;
                    $acc['total_retards'] += 1;
                } elseif ($bucket === 'absence') {
                    $acc['total_absences'] += 1;
                } elseif ($bucket === 'autorisation') {
                    $acc['total_autorisations'] += 1;
                } elseif ($bucket === 'conge') {
                    $acc['total_conges'] += 1;
                } elseif ($bucket === 'off') {
                    $acc['total_off'] += 1;
                } else {
                    $acc['total_others'] += 1;
                }
            }

            $rows[] = $acc;
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($a['agent']['fullname'] ?? ''), (string) ($b['agent']['fullname'] ?? '')));

        return $rows;
    }

    private function mapMonthlyDetailStatus(?string $status): array
    {
        return match ($status) {
            'present' => ['code' => '1', 'bucket' => 'presence'],
            'retard', 'retard_justifie' => ['code' => '1-R', 'bucket' => 'retard'],
            'absent', 'absence_justifiee' => ['code' => 'A', 'bucket' => 'absence'],
            'off' => ['code' => 'OFF', 'bucket' => 'off'],
            'conge' => ['code' => 'C', 'bucket' => 'conge'],
            'autorisation' => ['code' => 'AS', 'bucket' => 'autorisation'],
            'future' => ['code' => '--', 'bucket' => null],
            default => ['code' => 'AUT', 'bucket' => 'other'],
        };
    }

    private function summarizeStationFromMatrix(Station $station, array $matrix, Collection $agents): array
    {
        $agentKeys = [];
        foreach ($agents as $a) {
            $agentKeys[$a->fullname . ' (' . $a->matricule . ')'] = true;
        }

        $acc = [
            'station_id' => $station->id,
            'station' => $station->name,
            'agents' => count($agentKeys),
            'present' => 0,
            'retard' => 0,
            'absent' => 0,
            'conge' => 0,
            'autorisation' => 0,
        ];

        foreach ($matrix as $agentKey => $days) {
            if (!isset($agentKeys[$agentKey])) {
                continue;
            }
            foreach (($days ?? []) as $cell) {
                $s = $cell['status'] ?? null;
                if ($s === 'present') $acc['present'] += 1;
                else if ($s === 'retard' || $s === 'retard_justifie') $acc['present'] += 1;
                else if ($s === 'retard' || $s === 'retard_justifie') $acc['retard'] += 1;
                else if ($s === 'absent') $acc['absent'] += 1;
                else if ($s === 'conge') $acc['conge'] += 1;
                else if ($s === 'autorisation') $acc['autorisation'] += 1;
            }
        }

        return $acc;
    }

    /**
     * @return array<int, array{key:string,station_id:int|null,station_name:string,rows:\Illuminate\Support\Collection}>
     */
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

    private function buildAgentAttendancesExportPayload(array $data): array
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
                'Retard',
                'Total heures',
                'Photo debut',
                'Photo fin',
            ];

            $table = [];
            foreach ($rows as $p) {
                $table[] = [
                    (string) ($p->getRawOriginal('date_reference') ?? ''),
                    (string) ($p->assignedStation?->name ?? ''),
                    (string) ($p->stationCheckIn?->name ?? ''),
                    (string) ($p->stationCheckOut?->name ?? ''),
                    $p->started_at ? Carbon::parse($p->started_at)->format('H:i') : '',
                    $p->mid_check ? Carbon::parse($p->mid_check)->format('H:i') : '',
                    $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : '',
                    (string) ($p->retard ?? 'non'),
                    (string) ($p->duree ?? ''),
                    (string) ($p->photos_debut ?? ''),
                    (string) ($p->photos_fin ?? ''),
                ];
            }

            $statusMap = [
                'present' => 'Present',
                'absent' => 'Absent',
                'late' => 'Retard',
            ];
            $statusCode = (string) ($data['status'] ?? '');
            $statusLabel = $statusMap[$statusCode] ?? 'Tous';

            $meta = [
                'Agent: ' . $agent->fullname . ' (' . $agent->matricule . ')',
                'Jeu de donnees: Presences',
                'Portee: ' . ($applyFilters ? 'Filtres actifs' : 'Globale'),
                'Station filtre: ' . ($applyFilters ? ($station?->name ?? 'Toutes') : 'N/A'),
                'Periode: ' . ($applyFilters ? (($data['from'] ?? '...') . ' -> ' . ($data['to'] ?? '...')) : 'N/A'),
                'Statut filtre: ' . ($applyFilters ? $statusLabel : 'N/A'),
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
            'Jeu de donnees: Maintenances',
            'Portee: ' . ($applyFilters ? 'Filtres actifs' : 'Globale'),
            'Station filtre: ' . ($applyFilters ? ($station?->name ?? 'Toutes') : 'N/A'),
            'Periode: ' . ($applyFilters ? (($data['from'] ?? '...') . ' -> ' . ($data['to'] ?? '...')) : 'N/A'),
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
