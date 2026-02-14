<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Services\AbsenceReportService;
use App\Services\AttendanceReportService;
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

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where('site_id', (int) $stationId);
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

        $query = PresenceAgents::query()
            ->with(['agent.station', 'horaire', 'stationCheckIn', 'stationCheckOut', 'assignedStation'])
            ->whereDate('date_reference', $date);

        if ($stationId !== null) {
            $query->where('site_id', (int) $stationId);
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

        $pdf = Pdf::loadView('pdf.exports.horaires', [
            'title' => 'Liste des horaires',
            'station' => $station,
            'stationsById' => $stationsById,
            'rows' => $rows,
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

        $headers = ['Designation', 'Station', 'Heure debut', 'Heure fin', 'Tolerance (min)'];
        $table = [];
        foreach ($rows as $h) {
            $table[] = [
                (string) ($h->libelle ?? ''),
                (string) (optional($stations->get((int) $h->site_id))->name ?? ''),
                (string) ($h->started_at ?? ''),
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
            'Duree',
            'Retard',
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
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $stationId]);
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
        ]);

        $month = (int) ($data['month'] ?? Carbon::now()->month);
        $year = (int) ($data['year'] ?? Carbon::now()->year);
        $stationId = $data['station_id'] ?? null;
        $station = $stationId ? Station::find($stationId) : null;

        $matrix = $service->buildMonthlyMatrix($month, $year, ['station_id' => $stationId]);
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
                else if ($s === 'retard') $acc['retard'] += 1;
                else if ($s === 'absent') $acc['absent'] += 1;
                else if ($s === 'conge') $acc['conge'] += 1;
                else if ($s === 'autorisation') $acc['autorisation'] += 1;
                else if ($s === 'retard_justifie') $acc['retard_justifie'] += 1;
                else if ($s === 'absence_justifiee') $acc['absence_justifiee'] += 1;
            }

            $acc['total_preste'] = $acc['present'] + $acc['absence_justifiee'];
            $rows[] = $acc;
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($a['agent']['fullname'] ?? ''), (string) ($b['agent']['fullname'] ?? '')));

        return $rows;
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
}
