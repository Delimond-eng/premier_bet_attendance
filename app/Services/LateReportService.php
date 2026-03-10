<?php

namespace App\Services;

use App\Models\AttendanceJustification;
use App\Models\PresenceAgents;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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

        $rows = PresenceAgents::withoutGlobalScopes()
            ->with([
                'agent' => function ($query) {
                    $query->withoutGlobalScopes()
                        ->with(['station', 'groupe.horaire', 'horaire']);
                },
                'horaire',
                'assignedStation',
                'stationCheckIn',
                'stationCheckOut',
            ])
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('started_at')
            ->where('retard', 'oui')
            ->when($stationId !== null, function (Builder $query) use ($stationId) {
                $this->applyPresenceStationFilter($query, (int) $stationId);
            })
            ->orderByDesc('date_reference')
            ->orderByDesc('started_at')
            ->get();

        $agentIds = $rows->pluck('agent_id')->filter()->unique()->values()->all();
        $presenceIds = $rows->pluck('id')->filter()->unique()->values()->all();

        $justificationsByPresence = collect();
        $justificationsByAgentDate = collect();

        if (!empty($presenceIds) || !empty($agentIds)) {
            $justificationsQuery = AttendanceJustification::withoutGlobalScopes()
                ->where('status', 'approved')
                ->where('kind', 'retard')
                ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()]);

            if (!empty($presenceIds)) {
                $justificationsByPresence = (clone $justificationsQuery)
                    ->whereIn('presence_agent_id', $presenceIds)
                    ->get()
                    ->keyBy('presence_agent_id');
            }

            if (!empty($agentIds)) {
                $justificationsByAgentDate = (clone $justificationsQuery)
                ->whereIn('agent_id', $agentIds)
                    ->get()
                    ->groupBy(fn (AttendanceJustification $j) => $j->agent_id . '|' . Carbon::parse($j->date_reference)->toDateString());
            }
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

            $justif = $justificationsByPresence->get((int) ($presence->id ?? 0));
            if (!$justif) {
                $justif = optional($justificationsByAgentDate->get(($presence->agent_id ?? 0) . '|' . $date))->first();
            }
            $justifText = trim((string) ($justif?->justification ?? ''));
            $justificatif = $justif
                ? ('justifie' . ($justifText !== '' ? (' : ' . $justifText) : ''))
                : 'non justifie';

            $effectiveStationName = $presence->stationCheckIn?->name
                ?: $presence->stationCheckOut?->name
                ?: $presence->assignedStation?->name
                ?: $agent?->station?->name;

            $effectiveStationId = $presence->station_check_in_id
                ?: $presence->station_check_out_id
                ?: $presence->site_id
                ?: $agent?->site_id;

            $output[] = [
                'key' => (string) $presence->id . '|' . $date,
                'date' => $date,
                'agent' => [
                    'id' => $agent?->id,
                    'fullname' => $agent?->fullname,
                    'matricule' => $agent?->matricule,
                    'photo' => $agent?->photo,
                    'station_id' => $effectiveStationId,
                    'station_name' => $effectiveStationName,
                    'assigned_station_id' => $agent?->site_id,
                    'assigned_station_name' => $agent?->station?->name ?: ($presence->assignedStation?->name ?? null),
                    'check_in_station_id' => $presence->station_check_in_id,
                    'check_in_station_name' => $presence->stationCheckIn?->name,
                    'check_out_station_id' => $presence->station_check_out_id,
                    'check_out_station_name' => $presence->stationCheckOut?->name,
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

    private function applyPresenceStationFilter(Builder $query, int $stationId): void
    {
        $query->where(function (Builder $nested) use ($stationId) {
            $nested->where('site_id', $stationId)
                ->orWhere('station_check_in_id', $stationId)
                ->orWhere('station_check_out_id', $stationId);
        });
    }
}
