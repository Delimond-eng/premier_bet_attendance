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

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->format($dayKeyFormat);
            $cursor->addDay();
        }

        $agentsQuery = Agent::query()
            ->with(['station', 'groupe', 'horaire'])
            ->when(!empty($filters['station_id']), function ($q) use ($filters, $start, $end) {
                $sid = (int) $filters['station_id'];
                $q->where(function ($sub) use ($sid, $start, $end) {
                    $sub->where('site_id', $sid)
                        ->orWhereHas('plannings', fn($pq) => $pq->where('site_id', $sid)->whereBetween('date', [$start->toDateString(), $end->toDateString()]));
                });
            })
            ->when(!empty($filters['matricule_prefix']), fn($q) => $q->where('matricule', 'like', $filters['matricule_prefix'] . '%'))
            ->orderBy('fullname');

        $agents = $agentsQuery->get();
        $agentIds = $agents->pluck('id')->all();

        // Chargement des données avec clés de dates normalisées
        $presences = PresenceAgents::query()
            ->with('horaire')
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_reference', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($p) => $p->agent_id . '|' . Carbon::parse($p->date_reference)->toDateString());

        $plannings = AgentGroupPlanning::query()
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($p) => $p->agent_id . '|' . Carbon::parse($p->date)->toDateString());

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
            ->groupBy(fn($a) => $a->agent_id . '|' . Carbon::parse($a->date_reference)->toDateString());

        $matrix = [];
        foreach ($agents as $agent) {
            $row = [];
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $isoDate = $cursor->toDateString();
                $dayLabel = $cursor->format($dayKeyFormat);

                $p = optional($presences->get($agent->id . '|' . $isoDate))->first();

                if ($p && $p->started_at) {
                    $status = ($p->retard === 'oui') ? 'retard' : 'present';
                    $row[$dayLabel] = [
                        'status' => $status,
                        'arrivee' => Carbon::parse($p->started_at)->format('H:i'),
                        'depart' => $p->ended_at ? Carbon::parse($p->ended_at)->format('H:i') : '--:--',
                        'horaire' => $p->horaire?->libelle ?? '--',
                        'overtime_minutes' => $this->calculateOvertime($p, $p->horaire),
                        'duration_minutes' => $p->ended_at ? Carbon::parse($p->getRawOriginal('started_at') ?? $p->started_at)->diffInMinutes(Carbon::parse($p->getRawOriginal('ended_at') ?? $p->ended_at)) : 0
                    ];
                } else {
                    if ($isElectrocool && $cursor->isSunday()) {
                        $row[$dayLabel] = ['status' => 'off', 'arrivee' => 'REPOS', 'depart' => '', 'horaire' => 'REPOS', 'overtime_minutes' => 0, 'duration_minutes' => 0];
                    } elseif ($cursor->gt($today)) {
                        $row[$dayLabel] = ['status' => 'future', 'arrivee' => '--:--', 'depart' => '', 'horaire' => '--', 'overtime_minutes' => 0, 'duration_minutes' => 0];
                    } else {
                        $plan = optional($plannings->get($agent->id . '|' . $isoDate))->first();

                        // Check Congé
                        $hasConge = false;
                        if (isset($conges[$agent->id])) {
                            foreach ($conges[$agent->id] as $c) {
                                if ($cursor->betweenIncluded(Carbon::parse($c->date_debut)->startOfDay(), Carbon::parse($c->date_fin)->endOfDay())) {
                                    $hasConge = true; break;
                                }
                            }
                        }

                        if ($hasConge) {
                            $row[$dayLabel] = ['status' => 'conge', 'arrivee' => 'CONGÉ', 'depart' => '', 'horaire' => '--', 'overtime_minutes' => 0, 'duration_minutes' => 0];
                        } elseif ($auth = optional($authorizations->get($agent->id . '|' . $isoDate))->first()) {
                            $row[$dayLabel] = ['status' => 'autorisation', 'arrivee' => strtoupper($auth->type), 'depart' => '', 'horaire' => '--', 'overtime_minutes' => 0, 'duration_minutes' => 0];
                        } elseif ($plan && $plan->is_rest_day) {
                            $row[$dayLabel] = ['status' => 'off', 'arrivee' => 'OFF', 'depart' => '', 'horaire' => 'OFF', 'overtime_minutes' => 0, 'duration_minutes' => 0];
                        } else {
                            $row[$dayLabel] = ['status' => 'absent', 'arrivee' => 'ABS', 'depart' => '', 'horaire' => '--', 'overtime_minutes' => 0, 'duration_minutes' => 0];
                        }
                    }
                }
                $cursor->addDay();
            }
            $matrix[$agent->fullname . ' (' . $agent->matricule . ')'] = $row;
        }

        return ['data' => $matrix, 'days' => $days, 'agents' => $agents];
    }

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

        $triggerThreshold = $schedEnd->copy()->addHour();
        return $actualEnd->gt($triggerThreshold) ? (int) $schedEnd->diffInMinutes($actualEnd) : 0;
    }

    public function formatOvertime(int $minutes): string
    {
        if ($minutes <= 0) return '0h';
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return $mins === 0 ? "{$hours}h" : "{$hours}h {$mins}m";
    }

    public function calculateNormalHours(PresenceAgents $presence, int $overtimeMinutes): int
    {
        if (!$presence->started_at || !$presence->ended_at) return 0;
        $start = Carbon::parse($presence->getRawOriginal('started_at') ?? $presence->started_at);
        $end = Carbon::parse($presence->getRawOriginal('ended_at') ?? $presence->ended_at);
        return max(0, $start->diffInMinutes($end) - $overtimeMinutes);
    }
}
