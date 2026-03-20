<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupPlanning;
use App\Models\AgentGroupAssignment;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceReportService
{
    /**
     * @return array{data: array, days: array<int,string>, agents: Collection<int,Agent>}
     */
    public function buildDailyMatrix(Carbon $date, array $filters = []): array
    {
        $start = $date->copy()->startOfDay();
        $end = $start->copy()->startOfDay();

        return $this->buildMatrixForRange(
            start: $start,
            end: $end,
            filters: $filters,
            dayKeyFormat: 'Y-m-d',
        );
    }

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
        // Align with planning rotations: week starts on Monday.
        $start = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);

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
        $today = Carbon::now('Africa/Kinshasa')->startOfDay();

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
                function ($q) use ($filters, $start, $end) {
                    $sid = (int) $filters['station_id'];
                    $q->where(function ($sub) use ($sid, $start, $end) {
                        $sub->where('site_id', $sid)
                            ->orWhereHas('plannings', function ($pq) use ($sid, $start, $end) {
                                $pq->where('site_id', $sid)
                                   ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
                            });
                    });
                }
            )
            ->when(!empty($filters['group_id']), fn ($q) => $q->where('groupe_id', $filters['group_id']))
            ->when(!empty($filters['agent_id']), fn ($q) => $q->where('id', $filters['agent_id']))
            ->when(!empty($filters['matricule_prefix']), function ($q) use ($filters) {
                $q->where('matricule', 'like', $filters['matricule_prefix'] . '%');
            })
            ->orderBy('fullname');

        /** @var Collection<int,Agent> $agents */
        $agents = $agentsQuery->get();
        $agentIds = $agents->pluck('id')->all();

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
            ->flatMap(function ($agentId) use ($agents, $assignments) {
                $ids = [];
                foreach (($assignments[$agentId] ?? collect()) as $a) {
                    $ids[] = (int) $a->agent_group_id;
                }
                $fallback = $agents->firstWhere('id', $agentId)?->groupe_id;
                if ($fallback) {
                    $ids[] = (int) $fallback;
                }
                return $ids;
            })
            ->unique()
            ->values()
            ->all();

        $groupsById = AgentGroup::query()
            ->with('horaire')
            ->whereIn('id', $groupIds)
            ->get()
            ->keyBy('id');

        $groupIdFor = function (int $agentId, string $dateKey) use ($assignments): ?int {
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
            return null;
        };

        $plannings = AgentGroupPlanning::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['agent_id', 'agent_group_id', 'date', 'is_rest_day', 'horaire_id', 'site_id'])
            ->groupBy(fn ($p) => $p->agent_id . '|' . Carbon::parse($p->date)->format('Y-m-d') . '|' . (int) $p->agent_group_id);

        $planningsAny = $plannings
            ->flatten(1)
            ->groupBy(fn ($p) => $p->agent_id . '|' . Carbon::parse($p->date)->format('Y-m-d'));

        $planningHoraireIds = $planningsAny
            ->flatten(1)
            ->pluck('horaire_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $planningHorairesById = PresenceHoraire::query()
            ->whereIn('id', $planningHoraireIds)
            ->get()
            ->keyBy('id');

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

                // Ne pas compter les jours futurs comme "absent" (rapport mensuel/hebdo).
                if ($cursor->copy()->startOfDay()->gt($today)) {
                    $row[$dayKey] = [
                        'status' => 'future',
                        'arrivee' => '--:--',
                        'depart' => '--:--',
                        'horaire' => '--',
                        'overtime_minutes' => 0,
                        'duration_minutes' => 0,
                    ];
                    $cursor->addDay();
                    continue;
                }

                /** @var PresenceAgents|null $presence */
                $presence = optional($presences->get($agent->id . '|' . $dateKey))->first();
                /** @var AttendanceAuthorization|null $auth */
                $auth = optional($authorizations->get($agent->id . '|' . $dateKey))->first();
                /** @var AttendanceJustification|null $justif */
                $justif = optional($justifications->get($agent->id . '|' . $dateKey))->first();

                $gid = $groupIdFor((int) $agent->id, $dateKey) ?? ($agent->groupe_id ? (int) $agent->groupe_id : null);
                $group = $gid !== null ? $groupsById->get($gid) : null;
                $planning = $gid !== null
                    ? optional($plannings->get($agent->id . '|' . $dateKey . '|' . $gid))->first()
                    : null;

                if (!$planning) {
                    // Fallback if no assignment match: take any planning for that date (legacy behavior).
                    $planning = optional($planningsAny->get($agent->id . '|' . $dateKey))->first();
                }

                $isFlexible = $group && empty($group->horaire_id);

                if ($planning && $planning->is_rest_day) {
                    $row[$dayKey] = [
                        'status' => 'off',
                        'arrivee' => 'OFF',
                        'depart' => '',
                        'horaire' => 'OFF',
                        'overtime_minutes' => 0,
                        'duration_minutes' => 0,
                    ];
                    $cursor->addDay();
                    continue;
                }

                // Flexible groups (horaire_id null) are "expected" only if a work planning with horaire_id exists.
                if ($isFlexible && (!$planning || empty($planning->horaire_id))) {
                    $row[$dayKey] = [
                        'status' => 'unplanned',
                        'arrivee' => '--:--',
                        'depart' => '--:--',
                        'horaire' => '--',
                        'overtime_minutes' => 0,
                        'duration_minutes' => 0,
                    ];
                    $cursor->addDay();
                    continue;
                }

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

                /** @var PresenceHoraire|null $currentHoraireObj */
                $currentHoraireObj = $presence?->horaire;
                if (!$currentHoraireObj && $planning && $planning->horaire_id) {
                    $currentHoraireObj = $planningHorairesById->get((int) $planning->horaire_id);
                }
                if (!$currentHoraireObj && $agent->horaire) {
                    $currentHoraireObj = $agent->horaire;
                }

                if (!$presence && $planning && $planning->horaire_id) {
                    if ($currentHoraireObj) {
                        $rawStart = (string) ($currentHoraireObj->getRawOriginal('started_at') ?? $currentHoraireObj->started_at);
                        $rawEnd = (string) ($currentHoraireObj->getRawOriginal('ended_at') ?? $currentHoraireObj->ended_at);
                        $horaire = substr($rawStart, 0, 5) . ' - ' . substr($rawEnd, 0, 5);
                    }
                }

                $overtimeMinutes = 0;
                $durationMinutes = 0;
                if ($presence && $presence->started_at) {
                    $status = ($presence->retard === 'oui') ? 'retard' : 'present';
                    $arrivee = Carbon::parse($presence->started_at)->format('H:i');
                    $depart = $presence->ended_at ? Carbon::parse($presence->ended_at)->format('H:i') : '--:--';
                    if ($status === 'retard' && $justif && $justif->kind === 'retard') {
                        $status = 'retard_justifie';
                    }

                    $overtimeMinutes = $this->calculateOvertime($presence, $currentHoraireObj);

                    if ($presence->ended_at) {
                        $durationMinutes = Carbon::parse($presence->getRawOriginal('started_at') ?? $presence->started_at)
                            ->diffInMinutes(Carbon::parse($presence->getRawOriginal('ended_at') ?? $presence->ended_at));
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
                    'overtime_minutes' => $overtimeMinutes,
                    'duration_minutes' => $durationMinutes,
                    'planned_site_id' => $planning?->site_id,
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

    /**
     * Calcule les heures supplémentaires selon la règle :
     * - Se déclenche si la sortie est > 1 heure après la fin prévue.
     * - Si déclenché, le calcul commence dès la fin prévue.
     */
    public function calculateOvertime(PresenceAgents $presence, ?PresenceHoraire $horaire): int
    {
        if (!$presence->ended_at || !$horaire) {
            return 0;
        }

        $actualEnd = Carbon::parse($presence->getRawOriginal('ended_at') ?? $presence->ended_at);
        $scheduledEndStr = (string)($horaire->getRawOriginal('ended_at') ?? $horaire->ended_at);
        $scheduledStartStr = (string)($horaire->getRawOriginal('started_at') ?? $horaire->started_at);

        if (!$scheduledEndStr || !$scheduledStartStr) {
            return 0;
        }

        $refDate = Carbon::parse($presence->getRawOriginal('date_reference') ?? $presence->date_reference);
        $schedStart = $refDate->copy()->setTimeFromTimeString($scheduledStartStr);
        $schedEnd = $refDate->copy()->setTimeFromTimeString($scheduledEndStr);

        // Ajustement pour les shifts de nuit
        if ($schedEnd->lt($schedStart)) {
            $schedEnd->addDay();
        }

        // Seuil de déclenchement : 1 heure après la fin prévue
        $triggerThreshold = $schedEnd->copy()->addHour();

        if ($actualEnd->gt($triggerThreshold)) {
            // Si déclenché, on calcule depuis l'heure de fin PRÉVUE
            return (int) $schedEnd->diffInMinutes($actualEnd);
        }

        return 0;
    }

    public function formatOvertime(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0h';
        }
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        if ($mins === 0) {
            return $hours . 'h';
        }
        return $hours . 'h ' . $mins . 'm';
    }

    public function calculateNormalHours(PresenceAgents $presence, int $overtimeMinutes): int
    {
        if (!$presence->started_at || !$presence->ended_at) {
            return 0;
        }

        $start = Carbon::parse($presence->getRawOriginal('started_at') ?? $presence->started_at);
        $end = Carbon::parse($presence->getRawOriginal('ended_at') ?? $presence->ended_at);
        $totalMinutes = $start->diffInMinutes($end);

        // Les heures normales sont le total moins les heures sup
        $normalMinutes = $totalMinutes - $overtimeMinutes;
        return max(0, $normalMinutes);
    }
}
