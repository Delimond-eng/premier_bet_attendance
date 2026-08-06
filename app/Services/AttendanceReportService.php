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
use App\Support\ManagerStationContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceReportService
{
    public function buildDailyMatrix(Carbon $date, array $filters = []): array
    {
        return $this->buildMatrixForRange(
            start: $date->copy()->startOfDay(),
            end: $date->copy()->startOfDay(),
            filters: $filters,
            dayKeyFormat: 'Y-m-d'
        );
    }

    public function buildMonthlyMatrix(int $month, int $year, array $filters = []): array
    {
        $isRange = !empty($filters['from']) && !empty($filters['to']);

        if ($isRange) {
            $start = Carbon::parse($filters['from'])->startOfDay();
            $end = Carbon::parse($filters['to'])->endOfDay();
            $format = 'd/m';
        } else {
            $m = $month > 0 ? $month : Carbon::now()->month;
            $y = $year > 0 ? $year : Carbon::now()->year;
            $start = Carbon::createFromDate($y, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $format = 'd';
        }

        return $this->buildMatrixForRange($start, $end, $filters, $format);
    }

    public function buildWeeklyMatrix(Carbon $baseDate, array $filters = []): array
    {
        $start = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);
        return $this->buildMatrixForRange($start, $end, $filters, 'd/m');
    }

    private function buildMatrixForRange(Carbon $start, Carbon $end, array $filters = [], string $dayKeyFormat = 'd/m'): array
    {
        $today = Carbon::now('Africa/Kinshasa')->startOfDay();
        $host = request()->getHost();
        $isElectrocool = str_contains($host, 'electrocool') || $host === '127.0.0.1';
        $managerStationId = ManagerStationContext::stationId();
        $userPrefix = ManagerStationContext::matriculePrefix();

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->format($dayKeyFormat);
            $cursor->addDay();
        }

        $agentsQuery = Agent::withoutGlobalScopes()
            ->with([
                'station' => fn($q) => $q->withoutGlobalScopes(),
                'groupe' => fn($q) => $q->withoutGlobalScopes(),
                'horaire' => fn($q) => $q->withoutGlobalScopes()
            ])
            ->when($userPrefix !== null, function($q) use ($userPrefix) {
                $q->where('matricule', 'like', $userPrefix . '%');
            })
            ->when(!empty($filters['station_id']), function ($q) use ($filters, $start, $end, $managerStationId) {
                $sid = (int) $filters['station_id'];
                if ($managerStationId !== null) { $sid = $managerStationId; }

                $q->where(function ($sub) use ($sid, $start, $end) {
                    $sub->where('site_id', $sid)
                        ->orWhereHas('plannings', fn($pq) => $pq->withoutGlobalScopes()->where('site_id', $sid)->whereBetween('date', [$start->toDateString(), $end->toDateString()]));
                });
            })
            ->when(empty($filters['station_id']) && $managerStationId !== null, function($q) use ($managerStationId, $start, $end) {
                $q->where(function ($sub) use ($managerStationId, $start, $end) {
                    $sub->where('site_id', $managerStationId)
                        ->orWhereHas('plannings', fn($pq) => $pq->withoutGlobalScopes()->where('site_id', $managerStationId)->whereBetween('date', [$start->toDateString(), $end->toDateString()]));
                });
            })
            ->when(!empty($filters['matricule_prefix']), function($q) use ($filters) {
                $prefix = trim((string)$filters['matricule_prefix']);
                $q->where('matricule', 'like', $prefix . '%');
            })
            ->orderBy('fullname');

        $agents = $agentsQuery->get();
        $agentIds = $agents->pluck('id')->all();

        $presences = PresenceAgents::withoutGlobalScopes()
            ->with(['horaire' => fn($q) => $q->withoutGlobalScopes()])
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($p) => $p->agent_id . '|' . Carbon::parse($p->date_reference)->toDateString());

        $plannings = AgentGroupPlanning::withoutGlobalScopes()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($p) => $p->agent_id . '|' . Carbon::parse($p->date)->toDateString());

        $conges = Conge::withoutGlobalScopes()
            ->with(['congeType' => fn($q) => $q->withoutGlobalScopes()])
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereDate('date_fin', '>=', $start->toDateString())
            ->whereDate('date_debut', '<=', $end->toDateString())
            ->get()
            ->groupBy('agent_id');

        $authorizations = AttendanceAuthorization::withoutGlobalScopes()
            ->whereIn('agent_id', $agentIds)
            ->where('status', 'approved')
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($a) => $a->agent_id . '|' . Carbon::parse($a->date_reference)->toDateString());

        // Helper pour les codes courts de congé/absence
        $getShortCode = function(?string $label) {
            if (!$label) return null;
            $label = strtolower($label);
            if (str_contains($label, 'maladie')) return 'M';
            if (str_contains($label, 'circonstance')) return 'CC';
            if (str_contains($label, 'annuel')) return 'CA';
            if (str_contains($label, 'maternité') || str_contains($label, 'maternite')) return 'CM';
            return null;
        };

        $matrix = [];
        foreach ($agents as $agent) {
            $row = [];
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $isoDate = $cursor->toDateString();
                $dayLabel = $cursor->format($dayKeyFormat);

                $dayPresences = $presences->get($agent->id . '|' . $isoDate, collect())->sortBy(fn ($item) => $item->started_at ? Carbon::parse($item->started_at)->format('H:i:s') : '23:59:59');
                $p = $dayPresences->first();

                if ($p && $p->started_at) {
                    $presenceCount = $dayPresences->count();
                    $isDoubleShift = $presenceCount > 1;

                    if ($isDoubleShift) {
                        $firstPresence = $dayPresences->first();
                        $lateFirstCheckIn = strtolower((string) ($firstPresence->retard ?? 'non')) === 'oui';
                        $totalOvertime = $dayPresences->sum(fn ($presence) => $this->calculateOvertime($presence, $presence->horaire));
                        $totalDuration = $dayPresences->sum(fn ($presence) => $this->calculateNormalHours($presence, $presence->horaire, $this->calculateOvertime($presence, $presence->horaire)));
                        $lateMinutes = $lateFirstCheckIn ? $this->calculateLateMinutes($firstPresence, $firstPresence->horaire) : 0;
                        $lastPresence = $dayPresences->last();

                        $row[$dayLabel] = [
                            'status' => 'double_shift',
                            'arrivee' => $lateFirstCheckIn ? '2 C-1' : '2',
                            'depart' => $lastPresence && $lastPresence->ended_at ? Carbon::parse($lastPresence->ended_at)->format('H:i') : '--:--',
                            'horaire' => $firstPresence->horaire?->libelle ?? '--',
                            'overtime_minutes' => $totalOvertime,
                            'late_minutes' => $lateMinutes,
                            'duration_minutes' => $totalDuration,
                            'presence_count' => $presenceCount,
                            'late_first_checkin' => $lateFirstCheckIn,
                            'type' => 'Double Shift',
                        ];
                    } else {
                        $auth = optional($authorizations->get($agent->id . '|' . $isoDate))->first();
                        $hasExitAuth = $auth && in_array(strtolower($auth->type), ['depart', 'sortie']);
                        $isMissingExit = empty($p->ended_at) && $cursor->lt($today);

                        if ($isMissingExit && !$hasExitAuth) {
                            $status = 'absent';
                            $depart = 'AN';
                            $overtime = 0;
                            $duration = 0;
                        } else {
                            $status = ($p->retard === 'oui') ? 'retard' : 'present';
                            $depart = $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : ($hasExitAuth ? 'AUTH' : '--:--');
                            $overtime = $this->calculateOvertime($p, $p->horaire);
                            $duration = $this->calculateNormalHours($p, $p->horaire, $overtime);
                        }

                        $row[$dayLabel] = [
                            'status' => $status,
                            'arrivee' => Carbon::parse($p->started_at)->format('H:i'),
                            'depart' => $depart,
                            'horaire' => $p->horaire?->libelle ?? '--',
                            'overtime_minutes' => $overtime,
                            'late_minutes' => $this->calculateLateMinutes($p, $p->horaire),
                            'duration_minutes' => $duration
                        ];
                    }
                } else {
                    if ($isElectrocool && $cursor->isSunday()) {
                        $row[$dayLabel] = ['status' => 'off', 'arrivee' => 'REPOS', 'depart' => '', 'horaire' => 'REPOS', 'overtime_minutes' => 0, 'late_minutes' => 0, 'duration_minutes' => 0];
                    } elseif ($cursor->gt($today)) {
                        $row[$dayLabel] = ['status' => 'future', 'arrivee' => '--:--', 'depart' => '', 'horaire' => '--', 'overtime_minutes' => 0, 'late_minutes' => 0, 'duration_minutes' => 0];
                    } else {
                        $plan = optional($plannings->get($agent->id . '|' . $isoDate))->first();
                        $hasConge = false;
                        if (isset($conges[$agent->id])) {
                            foreach ($conges[$agent->id] as $c) {
                                if ($cursor->betweenIncluded(Carbon::parse($c->date_debut)->startOfDay(), Carbon::parse($c->date_fin)->endOfDay())) {
                                    $hasConge = true; break;
                                }
                            }
                        }

                        if ($hasConge) {
                            $congeInfo = null;
                            if (isset($conges[$agent->id])) {
                                foreach ($conges[$agent->id] as $c) {
                                    if ($cursor->betweenIncluded(Carbon::parse($c->date_debut)->startOfDay(), Carbon::parse($c->date_fin)->endOfDay())) {
                                        $congeInfo = $c;
                                        break;
                                    }
                                }
                            }

                            $typeLabel = $congeInfo->congeType?->libelle ?? $congeInfo->type ?? 'Congé';
                            $shortCode = $getShortCode($typeLabel) ?? 'C';

                            $row[$dayLabel] = [
                                'status' => 'conge',
                                'arrivee' => $shortCode,
                                'depart' => '',
                                'horaire' => '--',
                                'overtime_minutes' => 0,
                                'late_minutes' => 0,
                                'duration_minutes' => 0,
                                'type' => $typeLabel,
                                'motif' => $congeInfo?->motif ?? 'Congé',
                                'date_debut' => $congeInfo?->date_debut ? Carbon::parse($congeInfo->date_debut)->toDateString() : $isoDate,
                                'date_fin' => $congeInfo?->date_fin ? Carbon::parse($congeInfo->date_fin)->toDateString() : $isoDate,
                            ];
                        } elseif ($auth = optional($authorizations->get($agent->id . '|' . $isoDate))->first()) {
                            $authType = (string)$auth->type;
                            $shortCode = $getShortCode($authType) ?? strtoupper(substr($authType, 0, 1));
                            $status = (strtolower($authType) === 'maladie') ? 'maladie' : 'autorisation';

                            $row[$dayLabel] = [
                                'status' => $status,
                                'arrivee' => $shortCode,
                                'depart' => '',
                                'horaire' => '--',
                                'overtime_minutes' => 0,
                                'late_minutes' => 0,
                                'duration_minutes' => 0,
                                'type' => strtoupper($authType),
                                'motif' => $auth->reason ?? 'Autorisation',
                                'date_debut' => $auth->date_reference ? Carbon::parse($auth->date_reference)->toDateString() : $isoDate,
                                'date_fin' => $auth->date_reference ? Carbon::parse($auth->date_reference)->toDateString() : $isoDate,
                                'started_at' => $auth->started_at,
                                'ended_at' => $auth->ended_at,
                            ];
                        } elseif ($plan && $plan->is_rest_day) {
                            $row[$dayLabel] = ['status' => 'off', 'arrivee' => 'OFF', 'depart' => '', 'horaire' => 'OFF', 'overtime_minutes' => 0, 'late_minutes' => 0, 'duration_minutes' => 0, 'type' => 'Repos', 'motif' => 'Repos'];
                        } else {
                            $row[$dayLabel] = ['status' => 'absent', 'arrivee' => 'ABS', 'depart' => '', 'horaire' => '--', 'overtime_minutes' => 0, 'late_minutes' => 0, 'duration_minutes' => 0, 'type' => 'Absence', 'motif' => 'Absence non justifiée'];
                        }
                    }
                }
                $cursor->addDay();
            }
            $matrix[$agent->fullname . ' (' . $agent->matricule . ')'] = $row;
        }

        return ['data' => $matrix, 'days' => $days, 'agents' => $agents];
    }

    /**
     * Calcule les heures supplémentaires.
     * Le comptage commence 1 heure APRES la fin prévue de l'horaire.
     */
    public function calculateOvertime(PresenceAgents $presence, ?PresenceHoraire $horaire): int
    {
        if (!$presence->ended_at || !$horaire) return 0;
        $actualEnd = Carbon::parse($presence->getRawOriginal('ended_at') ?? $presence->ended_at);
        $scheduledEndStr = (string)($horaire->getRawOriginal('ended_at') ?? $horaire->ended_at);
        $scheduledStartStr = (string)($horaire->getRawOriginal('started_at') ?? $horaire->started_at);
        if (!$scheduledEndStr || !$scheduledStartStr) return 0;

        $refDate = Carbon::parse($presence->getRawOriginal('date_reference') ?? $presence->date_reference);
        $schedStart = $refDate->copy()->setTimeFromTimeString($scheduledStartStr);
        $schedEnd = $refDate->copy()->setTimeFromTimeString($scheduledEndStr);

        if ($schedEnd->lt($schedStart)) $schedEnd->addDay();

        // Le comptage commence 1h après la fin prévue
        $overtimeStartPoint = $schedEnd->copy()->addHour();

        if ($actualEnd->lte($overtimeStartPoint)) return 0;

        $overtimeMinutes = (int) $overtimeStartPoint->diffInMinutes($actualEnd);

        // CAP de sécurité : pas plus de 12 heures supp par jour
        return min($overtimeMinutes, 720);
    }

    public function calculateLateMinutes(PresenceAgents $presence, ?PresenceHoraire $horaire): int
    {
        if ($presence->retard !== 'oui' || !$horaire) return 0;

        $startedAt = Carbon::parse($presence->getRawOriginal('started_at') ?? $presence->started_at);
        $scheduledStartStr = (string)($horaire->getRawOriginal('started_at') ?? $horaire->started_at);
        if (!$scheduledStartStr) return 0;

        $refDate = Carbon::parse($presence->getRawOriginal('date_reference') ?? $presence->date_reference);
        $schedStart = $refDate->copy()->setTimeFromTimeString($scheduledStartStr);

        return (int) max(0, $schedStart->diffInMinutes($startedAt, false));
    }

    public function formatOvertime(int $minutes): string
    {
        if ($minutes <= 0) return '0h';
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return $mins === 0 ? "{$hours}h" : "{$hours}h {$mins}m";
    }

    /**
     * Calcule les heures normales travaillées, strictement limitées à la plage horaire prévue.
     */
    public function calculateNormalHours(PresenceAgents $presence, ?PresenceHoraire $horaire, int $overtimeMinutes = 0): int
    {
        if (!$presence->started_at || !$presence->ended_at) return 0;

        $actualStart = Carbon::parse($presence->getRawOriginal('started_at') ?? $presence->started_at);
        $actualEnd = Carbon::parse($presence->getRawOriginal('ended_at') ?? $presence->ended_at);

        if (!$horaire) {
            return (int) max(0, $actualStart->diffInMinutes($actualEnd) - $overtimeMinutes);
        }

        $scheduledStartStr = (string)($horaire->getRawOriginal('started_at') ?? $horaire->started_at);
        $scheduledEndStr = (string)($horaire->getRawOriginal('ended_at') ?? $horaire->ended_at);

        if (!$scheduledStartStr || !$scheduledEndStr) {
            return (int) max(0, $actualStart->diffInMinutes($actualEnd) - $overtimeMinutes);
        }

        $refDate = Carbon::parse($presence->getRawOriginal('date_reference') ?? $presence->date_reference);
        $schedStart = $refDate->copy()->setTimeFromTimeString($scheduledStartStr);
        $schedEnd = $refDate->copy()->setTimeFromTimeString($scheduledEndStr);
        if ($schedEnd->lt($schedStart)) $schedEnd->addDay();

        $effectiveStart = $actualStart->gt($schedStart) ? $actualStart : $schedStart;
        $effectiveEnd = $actualEnd->lt($schedEnd) ? $actualEnd : $schedEnd;

        if ($effectiveStart->gt($effectiveEnd)) return 0;

        return (int) max(0, $effectiveStart->diffInMinutes($effectiveEnd, false));
    }
}
