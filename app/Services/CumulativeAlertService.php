<?php

namespace App\Services;

use App\Models\Agent;
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
     *   retards:array<int,array<string,mixed>>
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

        return [
            'absences' => $absenceAlerts,
            'retards' => $lateAlerts,
        ];
    }

    /**
     * Counts for current month badges in sidebar.
     *
     * @return array{absences:int,retards:int}
     */
    public function getSidebarCounts(int $threshold = 3): array
    {
        $start = Carbon::today()->startOfMonth();
        $end = Carbon::today()->endOfMonth()->startOfDay();

        $alerts = $this->buildAlerts($start, $end, null, $threshold);

        return [
            'absences' => count($alerts['absences'] ?? []),
            'retards' => count($alerts['retards'] ?? []),
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
        return [
            'key' => $type . '|' . (string) ($agent['id'] ?? 'unknown') . '|' . $monthStart->format('Y-m'),
            'type' => $type,
            'month' => $monthStart->format('Y-m'),
            'month_label' => $this->monthLabelFr($monthStart) . ' ' . $monthStart->year,
            'count' => $count,
            'threshold' => $threshold,
            'letter_required' => true,
            'action_label' => "Lettre d'explication requise",
            'agent' => $agent,
        ];
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
