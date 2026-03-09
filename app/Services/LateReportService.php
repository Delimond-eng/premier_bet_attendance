<?php

namespace App\Services;

use App\Models\AttendanceJustification;
use App\Models\PresenceAgents;
use Carbon\Carbon;

class LateReportService
{
    /**
     * @return array<int, array{
     *   key:string,
     *   date:string,
     *   agent:array,
     *   arrival_time:string,
     *   expected_time:string,
     *   late_minutes:int,
     *   justificatif:string
     * }>
     */
    public function buildLateRows(Carbon $start, Carbon $end, ?int $stationId = null): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $rows = PresenceAgents::query()
            ->with([
                'agent.station',
                'agent.groupe.horaire',
                'agent.horaire',
                'horaire',
                'assignedStation',
            ])
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('started_at')
            ->where('retard', 'oui')
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderByDesc('date_reference')
            ->orderByDesc('started_at')
            ->get();

        $agentIds = $rows->pluck('agent_id')->filter()->unique()->values()->all();

        $justifications = collect();
        if (!empty($agentIds)) {
            $justifications = AttendanceJustification::query()
                ->whereIn('agent_id', $agentIds)
                ->where('status', 'approved')
                ->where('kind', 'retard')
                ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->groupBy(fn (AttendanceJustification $j) => $j->agent_id . '|' . Carbon::parse($j->date_reference)->toDateString());
        }

        $output = [];
        foreach ($rows as $presence) {
            $date = Carbon::parse($presence->date_reference)->toDateString();
            $agent = $presence->agent;
            $horaire = $presence->horaire ?: ($agent?->groupe?->horaire ?: $agent?->horaire);

            $arrivalTime = $presence->started_at
                ? Carbon::parse($presence->started_at)->format('H:i')
                : '--:--';

            $rawExpected = (string) ($horaire?->getRawOriginal('started_at') ?? $horaire?->started_at ?? '');
            $expectedTime = $rawExpected !== '' ? substr($rawExpected, 0, 5) : '--:--';

            $lateMinutes = 0;
            if ($arrivalTime !== '--:--' && $expectedTime !== '--:--') {
                $expectedAt = Carbon::parse($date . ' ' . $expectedTime);
                $arrivedAt = Carbon::parse($date . ' ' . $arrivalTime);
                if ($arrivedAt->greaterThan($expectedAt)) {
                    $lateMinutes = $expectedAt->diffInMinutes($arrivedAt);
                }
            }

            $justif = optional($justifications->get(($presence->agent_id ?? 0) . '|' . $date))->first();
            $justifText = trim((string) ($justif?->justification ?? ''));
            $justificatif = $justif
                ? ('justifie' . ($justifText !== '' ? (' : ' . $justifText) : ''))
                : 'non justifie';

            $output[] = [
                'key' => (string) $presence->id . '|' . $date,
                'date' => $date,
                'agent' => [
                    'id' => $agent?->id,
                    'fullname' => $agent?->fullname,
                    'matricule' => $agent?->matricule,
                    'photo' => $agent?->photo,
                    'station_id' => $agent?->site_id,
                    'station_name' => $agent?->station?->name ?: ($presence->assignedStation?->name ?? null),
                    'group_id' => $agent?->groupe?->id,
                    'group_name' => $agent?->groupe?->libelle,
                    'schedule_id' => $horaire?->id,
                    'schedule_label' => $horaire?->libelle,
                ],
                'arrival_time' => $arrivalTime,
                'expected_time' => $expectedTime,
                'late_minutes' => (int) $lateMinutes,
                'justificatif' => $justificatif,
            ];
        }

        return $output;
    }
}
