<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentGroupAssignment;
use App\Models\AgentGroup;
use App\Models\AgentGroupPlanning;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\PresenceAgents;
use Carbon\Carbon;

class AbsenceReportService
{
    /**
     * @return array<int, array{key:string,date:string,agent:array,justificatif:string}>
     */
    public function buildAbsenceRows(Carbon $start, Carbon $end, ?int $stationId = null): array
    {
        $today = Carbon::now('Africa/Kinshasa')->startOfDay();

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $agents = Agent::query()
            ->with(['station', 'groupe.horaire', 'horaire'])
            ->when($stationId !== null, fn ($q) => $q->where('site_id', (int) $stationId))
            ->orderBy('fullname')
            ->get();

        $agentIds = $agents->pluck('id')->all();
        $agentsById = $agents->keyBy('id');

        $assignments = AgentGroupAssignment::query()
            ->whereIn('agent_id', $agentIds)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start->toDateString());
            })
            ->orderByDesc('start_date')
            ->get(['agent_id', 'agent_group_id', 'start_date', 'end_date'])
            ->groupBy('agent_id');

        $groupIds = collect($agentIds)
            ->flatMap(function ($agentId) use ($agentsById, $assignments) {
                $ids = [];
                foreach (($assignments[$agentId] ?? collect()) as $a) {
                    $ids[] = (int) $a->agent_group_id;
                }
                $fallback = $agentsById->get($agentId)?->groupe_id;
                if ($fallback) {
                    $ids[] = (int) $fallback;
                }
                return $ids;
            })
            ->unique()
            ->values()
            ->all();

        $groupsById = AgentGroup::query()
            ->get(['id', 'horaire_id'])
            ->keyBy('id');

        $groupIdFor = function (int $agentId, string $dateKey) use ($assignments, $agentsById): ?int {
            foreach (($assignments[$agentId] ?? collect()) as $a) {
                $sd = (string) $a->start_date;
                $ed = $a->end_date ? (string) $a->end_date : null;
                if ($dateKey < $sd) {
                    continue;
                }
                if ($ed !== null && $dateKey > $ed) {
                    continue;
                }
                return (int) $a->agent_group_id;
            }

            $fallback = $agentsById->get($agentId)?->groupe_id;
            return $fallback ? (int) $fallback : null;
        };

        $workPlannings = AgentGroupPlanning::query() 
            ->whereIn('agent_id', $agentIds) 
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]) 
            ->where('is_rest_day', false) 
            ->get(['agent_id', 'agent_group_id', 'date', 'horaire_id']); 
 
        $workKeys = []; 
        foreach ($workPlannings as $p) { 
            $d = Carbon::parse($p->date)->toDateString(); 
            $gid = $groupIdFor((int) $p->agent_id, $d); 
            if ($gid !== null && (int) $p->agent_group_id !== $gid) { 
                continue; 
            } 
            $isFlexible = $gid !== null && $groupsById->get($gid) && empty($groupsById->get($gid)->horaire_id); 
            if ($isFlexible && empty($p->horaire_id)) { 
                // Flexible groups require a concrete hour for the day to be "expected". 
                continue; 
            } 
            $workKeys[$p->agent_id . '|' . $d] = true; 
        } 

        $offPlannings = AgentGroupPlanning::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('is_rest_day', true)
            ->get(['agent_id', 'agent_group_id', 'date']);

        $offKeys = [];
        foreach ($offPlannings as $p) {
            $d = Carbon::parse($p->date)->toDateString();
            $gid = $groupIdFor((int) $p->agent_id, $d);
            if ($gid !== null && (int) $p->agent_group_id !== $gid) {
                continue;
            }
            $offKeys[$p->agent_id . '|' . $d] = true;
        }

        $presentKeys = PresenceAgents::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('started_at')
            ->get(['agent_id', 'date_reference'])
            ->map(fn ($p) => $p->agent_id . '|' . Carbon::parse($p->date_reference)->toDateString())
            ->flip();

        $authorizations = AttendanceAuthorization::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (AttendanceAuthorization $a) => $a->agent_id . '|' . Carbon::parse($a->date_reference)->toDateString());

        $justifications = AttendanceJustification::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (AttendanceJustification $j) => $j->agent_id . '|' . Carbon::parse($j->date_reference)->toDateString());

        $conges = Conge::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_fin', '>=', $start->toDateString())
            ->whereDate('date_debut', '<=', $end->toDateString())
            ->get()
            ->groupBy('agent_id');

        $rows = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->copy()->startOfDay()->gt($today)) {
                break; // on ne reporte pas les dates futures
            }
            $d = $cursor->toDateString();
            foreach ($agents as $agent) {
                $k = $agent->id . '|' . $d;
                if (isset($offKeys[$k])) {
                    continue;
                }
                if (isset($presentKeys[$k])) {
                    continue;
                }

                $gid = $groupIdFor((int) $agent->id, $d);
                $isFlexible = $gid !== null && $groupsById->get($gid) && empty($groupsById->get($gid)->horaire_id);
                if ($isFlexible && !isset($workKeys[$k])) {
                    // Flexible agents are "expected" only if a work planning exists for the day.
                    continue;
                }

                $parts = [];

                $congeForDay = null;
                if ($conges->has($agent->id)) {
                    foreach ($conges->get($agent->id) as $c) {
                        $from = Carbon::parse($c->date_debut)->startOfDay();
                        $to = Carbon::parse($c->date_fin)->endOfDay();
                        if ($cursor->betweenIncluded($from, $to)) {
                            $congeForDay = $c;
                            break;
                        }
                    }
                }
                if ($congeForDay) {
                    $parts[] = 'CONGÉ';
                }

                /** @var AttendanceAuthorization|null $auth */
                $auth = optional($authorizations->get($k))->first();
                if ($auth) {
                    $parts[] = 'AUTORISATION';
                }

                $justifText = null;
                /** @var AttendanceJustification|null $justif */
                $justif = optional($justifications->get($k))->first();
                if ($justif) {
                    $kind = strtoupper((string) ($justif->kind ?? ''));
                    $parts[] = $kind ? ("JUSTIF " . $kind) : "JUSTIF";
                    $justifText = trim((string) ($justif->justification ?? ''));
                }

                $justificatif = count($parts) > 0 ? implode(' | ', $parts) : 'aucun';
                if ($justifText) {
                    $justificatif .= ' : ' . $justifText;
                }

                $horaire = $agent->groupe?->horaire ?: $agent->horaire;
                $expectedTime = $horaire?->started_at ? Carbon::parse($horaire->started_at)->format('H:i') : '--:--';

                $rows[] = [
                    'key' => $k,
                    'date' => $d,
                    'agent' => [
                        'id' => $agent->id,
                        'fullname' => $agent->fullname,
                        'matricule' => $agent->matricule,
                        'photo' => $agent->photo,
                        'station_id' => $agent->site_id,
                        'station_name' => $agent->station?->name,
                        'group_id' => $agent->groupe?->id,
                        'group_name' => $agent->groupe?->libelle,
                        'schedule_id' => $horaire?->id,
                        'schedule_label' => $horaire?->libelle,
                        'expected_time' => $expectedTime,
                    ],
                    'justificatif' => $justificatif,
                ];
            }
            $cursor->addDay();
        }

        return $rows;
    }
}
