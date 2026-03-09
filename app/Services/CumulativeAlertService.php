<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\PresenceAgents;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CumulativeAlertService
{
    public function __construct(
        private readonly AttendanceReportService $attendanceService
    ) {
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{period:string,start:Carbon,end:Carbon,label:string}
     */
    public function resolveRange(array $filters): array
    {
        $period = (string) ($filters['period'] ?? 'daily');
        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        $start = !empty($filters['from'])
            ? Carbon::parse((string) $filters['from'])->startOfDay()
            : Carbon::today()->startOfDay();
        $end = !empty($filters['to'])
            ? Carbon::parse((string) $filters['to'])->startOfDay()
            : $start->copy();

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

        $prefix = match ($period) {
            'weekly' => 'Hebdo',
            'monthly' => 'Mensuelle',
            default => 'Journaliere',
        };
        $label = $prefix . ': ' . $start->toDateString() . ' -> ' . $end->toDateString();

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'label' => $label,
        ];
    }

    /**
     * @return array{
     *   absences:array<int,array<string,mixed>>,
     *   retards:array<int,array<string,mixed>>,
     *   departs:array<int,array<string,mixed>>
     * }
     */
    public function buildAlerts(Carbon $start, Carbon $end, ?int $stationId = null, int $threshold = 3): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $threshold = max(1, (int) $threshold);
        $months = $this->monthsInRange($start, $end);

        $absenceAlerts = [];
        $lateAlerts = [];
        $earlyDepartureAlerts = [];

        foreach ($months as $monthStart) {
            $matrix = $this->attendanceService->buildMonthlyMatrix(
                month: (int) $monthStart->month,
                year: (int) $monthStart->year,
                filters: ['station_id' => $stationId],
            );

            $dayKeys = $this->dayKeysWithinWindow($monthStart, $start, $end);
            if (empty($dayKeys)) {
                continue;
            }

            $agentsByKey = $this->mapAgentsByKey($matrix['agents']);

            foreach (($matrix['data'] ?? []) as $agentKey => $days) {
                $absenceCount = 0;
                $lateCount = 0;

                foreach ($dayKeys as $dayKey) {
                    $status = $days[$dayKey]['status'] ?? null;
                    if ($status === 'absent') {
                        $absenceCount += 1;
                    }
                    if ($status === 'retard' || $status === 'retard_justifie') {
                        $lateCount += 1;
                    }
                }

                $agent = $agentsByKey[$agentKey] ?? [
                    'id' => null,
                    'fullname' => $agentKey,
                    'matricule' => '',
                    'photo' => null,
                    'station_id' => null,
                    'station_name' => null,
                    'group_id' => null,
                    'group_name' => null,
                    'schedule_id' => null,
                    'schedule_label' => null,
                ];

                if ($absenceCount >= $threshold) {
                    $absenceAlerts[] = $this->buildAlertRow($agent, $monthStart, $absenceCount, $threshold, 'absences');
                }

                if ($lateCount >= $threshold) {
                    $lateAlerts[] = $this->buildAlertRow($agent, $monthStart, $lateCount, $threshold, 'retards');
                }
            }

            $earlyDepartureStats = $this->buildEarlyDepartureStatsForMonth(
                monthStart: $monthStart,
                globalStart: $start,
                globalEnd: $end,
                stationId: $stationId,
            );

            foreach (($earlyDepartureStats['counts'] ?? []) as $agentId => $count) {
                $count = (int) $count;
                if ($count < $threshold) {
                    continue;
                }

                $agent = $earlyDepartureStats['agents'][$agentId] ?? [
                    'id' => (int) $agentId,
                    'fullname' => 'Agent ' . $agentId,
                    'matricule' => '',
                    'photo' => null,
                    'station_id' => null,
                    'station_name' => null,
                    'group_id' => null,
                    'group_name' => null,
                    'schedule_id' => null,
                    'schedule_label' => null,
                ];

                $earlyDepartureAlerts[] = $this->buildAlertRow(
                    $agent,
                    $monthStart,
                    $count,
                    $threshold,
                    'departs'
                );
            }
        }

        $sortFn = function (array $a, array $b): int {
            $countCompare = (int) ($b['count'] ?? 0) <=> (int) ($a['count'] ?? 0);
            if ($countCompare !== 0) {
                return $countCompare;
            }

            $monthCompare = strcmp((string) ($b['month'] ?? ''), (string) ($a['month'] ?? ''));
            if ($monthCompare !== 0) {
                return $monthCompare;
            }

            return strcmp((string) ($a['agent']['fullname'] ?? ''), (string) ($b['agent']['fullname'] ?? ''));
        };

        usort($absenceAlerts, $sortFn);
        usort($lateAlerts, $sortFn);
        usort($earlyDepartureAlerts, $sortFn);

        return [
            'absences' => $absenceAlerts,
            'retards' => $lateAlerts,
            'departs' => $earlyDepartureAlerts,
        ];
    }

    /**
     * Counts for current month badges in sidebar.
     *
     * @return array{absences:int,retards:int,departs:int}
     */
    public function getSidebarCounts(int $threshold = 3): array
    {
        $start = Carbon::today()->startOfMonth();
        $end = Carbon::today()->endOfMonth()->startOfDay();

        $alerts = $this->buildAlerts($start, $end, null, $threshold);

        return [
            'absences' => count($alerts['absences'] ?? []),
            'retards' => count($alerts['retards'] ?? []),
            'departs' => count($alerts['departs'] ?? []),
        ];
    }

    /**
     * @return array<int,Carbon>
     */
    private function monthsInRange(Carbon $start, Carbon $end): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $months[] = $cursor->copy()->startOfMonth();
            $cursor->addMonth()->startOfMonth();
        }
        return $months;
    }

    /**
     * @return array<int,string>
     */
    private function dayKeysWithinWindow(Carbon $monthStart, Carbon $globalStart, Carbon $globalEnd): array
    {
        $monthFrom = $monthStart->copy()->startOfMonth()->startOfDay();
        $monthTo = $monthStart->copy()->endOfMonth()->startOfDay();

        $from = $globalStart->gt($monthFrom) ? $globalStart->copy() : $monthFrom->copy();
        $to = $globalEnd->lt($monthTo) ? $globalEnd->copy() : $monthTo->copy();

        if ($from->gt($to)) {
            return [];
        }

        $dayKeys = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $dayKeys[] = $cursor->format('d');
            $cursor->addDay();
        }

        return $dayKeys;
    }

    /**
     * @param Collection<int,Agent> $agents
     * @return array<string,array<string,mixed>>
     */
    private function mapAgentsByKey(Collection $agents): array
    {
        $out = [];
        foreach ($agents as $a) {
            $key = $a->fullname . ' (' . $a->matricule . ')';
            $out[$key] = [
                'id' => $a->id,
                'fullname' => $a->fullname,
                'matricule' => $a->matricule,
                'photo' => $a->photo,
                'station_id' => $a->site_id,
                'station_name' => $a->station?->name,
                'group_id' => $a->groupe_id,
                'group_name' => $a->groupe?->libelle,
                'schedule_id' => $a->horaire_id,
                'schedule_label' => $a->horaire?->libelle,
            ];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $agent
     * @return array<string,mixed>
     */
    private function buildAlertRow(array $agent, Carbon $monthStart, int $count, int $threshold, string $type): array
    {
        $actionLabel = "Lettre d'explication requise";
        if ($type === 'departs') {
            $actionLabel = "Lettre d'explication requise (depart anticipe)";
        }

        return [
            'key' => $type . '|' . (string) ($agent['id'] ?? 'unknown') . '|' . $monthStart->format('Y-m'),
            'type' => $type,
            'month' => $monthStart->format('Y-m'),
            'month_label' => $this->monthLabelFr($monthStart) . ' ' . $monthStart->year,
            'count' => $count,
            'threshold' => $threshold,
            'letter_required' => true,
            'action_label' => $actionLabel,
            'agent' => $agent,
        ];
    }

    /**
     * @return array{counts:array<int,int>,agents:array<int,array<string,mixed>>}
     */
    private function buildEarlyDepartureStatsForMonth(Carbon $monthStart, Carbon $globalStart, Carbon $globalEnd, ?int $stationId): array
    {
        $monthFrom = $monthStart->copy()->startOfMonth()->startOfDay();
        $monthTo = $monthStart->copy()->endOfMonth()->startOfDay();

        $from = $globalStart->gt($monthFrom) ? $globalStart->copy() : $monthFrom->copy();
        $to = $globalEnd->lt($monthTo) ? $globalEnd->copy() : $monthTo->copy();

        if ($from->gt($to)) {
            return ['counts' => [], 'agents' => []];
        }

        $rows = PresenceAgents::query()
            ->with(['agent.station', 'agent.groupe.horaire', 'agent.horaire', 'horaire'])
            ->whereBetween('date_reference', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('ended_at')
            ->when($stationId !== null, function ($q) use ($stationId) {
                $q->where(function ($qq) use ($stationId) {
                    $qq->where('site_id', (int) $stationId)
                        ->orWhere('station_check_in_id', (int) $stationId)
                        ->orWhere('station_check_out_id', (int) $stationId);
                });
            })
            ->get();

        $counts = [];
        $agents = [];

        foreach ($rows as $presence) {
            $agent = $presence->agent;
            if (!$agent || !$agent->id) {
                continue;
            }

            $schedule = $presence->horaire ?: ($agent->groupe?->horaire ?: $agent->horaire);
            $expectedEnd = $this->resolveExpectedEndDateTime($presence, $schedule);
            if (!$expectedEnd) {
                continue;
            }

            $actualEndRaw = (string) ($presence->getRawOriginal('ended_at') ?? '');
            if ($actualEndRaw === '') {
                continue;
            }

            $actualEnd = Carbon::parse($actualEndRaw);
            if (!$actualEnd->lt($expectedEnd)) {
                continue;
            }

            $agentId = (int) $agent->id;
            $counts[$agentId] = (int) ($counts[$agentId] ?? 0) + 1;

            if (!isset($agents[$agentId])) {
                $agents[$agentId] = [
                    'id' => $agentId,
                    'fullname' => $agent->fullname,
                    'matricule' => $agent->matricule,
                    'photo' => $agent->photo,
                    'station_id' => $agent->site_id,
                    'station_name' => $agent->station?->name,
                    'group_id' => $agent->groupe?->id,
                    'group_name' => $agent->groupe?->libelle,
                    'schedule_id' => $schedule?->id,
                    'schedule_label' => $schedule?->libelle,
                ];
            }
        }

        return [
            'counts' => $counts,
            'agents' => $agents,
        ];
    }

    private function resolveExpectedEndDateTime(PresenceAgents $presence, ?object $schedule): ?Carbon
    {
        if (!$schedule) {
            return null;
        }

        $rawEnd = (string) ($schedule->getRawOriginal('ended_at') ?? $schedule->ended_at ?? '');
        if ($rawEnd === '') {
            return null;
        }

        $rawDateReference = (string) ($presence->getRawOriginal('date_reference') ?? '');
        if ($rawDateReference === '') {
            return null;
        }

        $dateRef = Carbon::parse($rawDateReference)->startOfDay();
        $expectedEnd = Carbon::parse($dateRef->toDateString() . ' ' . $this->normalizeTimeForDateTime($rawEnd));

        $rawStart = (string) ($schedule->getRawOriginal('started_at') ?? $schedule->started_at ?? '');
        if ($rawStart !== '') {
            try {
                $scheduleStart = Carbon::createFromTimeString($this->normalizeTimeForDateTime($rawStart));
                $scheduleEnd = Carbon::createFromTimeString($this->normalizeTimeForDateTime($rawEnd));
                if ($scheduleEnd->lt($scheduleStart)) {
                    $expectedEnd->addDay();
                }
            } catch (\Throwable $_) {
            }
        }

        return $expectedEnd;
    }

    private function normalizeTimeForDateTime(string $time): string
    {
        $trim = trim($time);
        if ($trim === '') {
            return '00:00:00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $trim)) {
            return $trim . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $trim)) {
            return $trim;
        }

        return substr($trim, 0, 8);
    }

    private function monthLabelFr(Carbon $date): string
    {
        $months = [
            1 => 'Janvier',
            2 => 'Fevrier',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Aout',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Decembre',
        ];

        return $months[(int) $date->month] ?? $date->format('m');
    }
}
