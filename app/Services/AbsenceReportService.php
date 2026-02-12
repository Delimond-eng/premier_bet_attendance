<?php

namespace App\Services;

use App\Models\Agent;
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
            $d = $cursor->toDateString();
            foreach ($agents as $agent) {
                $k = $agent->id . '|' . $d;
                if (isset($presentKeys[$k])) {
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

