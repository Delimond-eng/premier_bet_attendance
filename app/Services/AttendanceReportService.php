<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\PresenceAgents;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceReportService
{
    /**
     * @return array{data: array, days: array<int,string>, agents: Collection<int,Agent>}
     */
    public function buildMonthlyMatrix(int $month, int $year, array $filters = []): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->buildMatrixForRange(
            start: $start,
            end: $end,
            filters: $filters,
            dayKeyFormat: 'd', // requis par le PDF mensuel
        );
    }

    /**
     * @return array{data: array, days: array<int,string>, agents: Collection<int,Agent>}
     */
    public function buildWeeklyMatrix(Carbon $baseDate, array $filters = []): array
    {
        $start = $baseDate->copy()->startOfWeek();
        $end = $baseDate->copy()->endOfWeek();

        return $this->buildMatrixForRange(
            start: $start,
            end: $end,
            filters: $filters,
            dayKeyFormat: 'Y-m-d',
        );
    }

    /**
     * Construit une matrice de présence sur une période arbitraire.
     *
     * @return array{data: array, days: array<int,string>, agents: Collection<int,Agent>}
     */
    private function buildMatrixForRange(Carbon $start, Carbon $end, array $filters = [], string $dayKeyFormat = 'd'): array
    {
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->format($dayKeyFormat);
            $cursor->addDay();
        }

        $agentsQuery = Agent::query()
            ->with(['station', 'groupe', 'horaire'])
            ->when(
                array_key_exists('station_id', $filters) && $filters['station_id'] !== null && $filters['station_id'] !== '',
                fn ($q) => $q->where('site_id', (int) $filters['station_id'])
            )
            ->when(!empty($filters['group_id']), fn ($q) => $q->where('groupe_id', $filters['group_id']))
            ->when(!empty($filters['agent_id']), fn ($q) => $q->where('id', $filters['agent_id']))
            ->orderBy('fullname');

        /** @var Collection<int,Agent> $agents */
        $agents = $agentsQuery->get();
        $agentIds = $agents->pluck('id')->all();

        $presences = PresenceAgents::query()
            ->with(['horaire'])
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (PresenceAgents $p) => $p->agent_id . '|' . Carbon::parse($p->date_reference)->format('Y-m-d'));

        $conges = Conge::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_fin', '>=', $start->toDateString())
            ->whereDate('date_debut', '<=', $end->toDateString())
            ->get()
            ->groupBy('agent_id');

        $authorizations = AttendanceAuthorization::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (AttendanceAuthorization $a) => $a->agent_id . '|' . Carbon::parse($a->date_reference)->format('Y-m-d'));

        $justifications = AttendanceJustification::query()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (AttendanceJustification $j) => $j->agent_id . '|' . Carbon::parse($j->date_reference)->format('Y-m-d'));

        $matrix = [];

        foreach ($agents as $agent) {
            $row = [];
            $cursor = $start->copy();

            while ($cursor->lte($end)) {
                $dateKey = $cursor->format('Y-m-d');
                $dayKey = $cursor->format($dayKeyFormat);

                /** @var PresenceAgents|null $presence */
                $presence = optional($presences->get($agent->id . '|' . $dateKey))->first();
                /** @var AttendanceAuthorization|null $auth */
                $auth = optional($authorizations->get($agent->id . '|' . $dateKey))->first();
                /** @var AttendanceJustification|null $justif */
                $justif = optional($justifications->get($agent->id . '|' . $dateKey))->first();

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

                $status = 'absent';
                $arrivee = '--:--';
                $depart = '--:--';
                $horaire = $presence?->horaire?->libelle ?? $agent->horaire?->libelle ?? '--';

                if ($presence && $presence->started_at) {
                    $status = ($presence->retard === 'oui') ? 'retard' : 'present';
                    $arrivee = Carbon::parse($presence->started_at)->format('H:i');
                    $depart = $presence->ended_at ? Carbon::parse($presence->ended_at)->format('H:i') : '--:--';
                    if ($status === 'retard' && $justif && $justif->kind === 'retard') {
                        $status = 'retard_justifie';
                    }
                } elseif ($congeForDay) {
                    $status = 'conge';
                    $arrivee = 'CONGÉ';
                    $depart = '';
                } elseif ($auth) {
                    $status = 'autorisation';
                    $arrivee = strtoupper($auth->type);
                    $depart = '';
                } elseif ($justif && $justif->kind === 'absence') {
                    $status = 'absence_justifiee';
                    $arrivee = 'JUSTIF';
                    $depart = '';
                }

                $row[$dayKey] = [
                    'status' => $status,
                    'arrivee' => $arrivee,
                    'depart' => $depart,
                    'horaire' => $horaire,
                ];

                $cursor->addDay();
            }

            $matrix[$agent->fullname . ' (' . $agent->matricule . ')'] = $row;
        }

        return [
            'data' => $matrix,
            'days' => $days,
            'agents' => $agents,
        ];
    }
}
