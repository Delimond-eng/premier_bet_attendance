<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Agent;
use App\Models\PresenceAgents;
use App\Models\AgentGroupAssignment;
use App\Models\AgentGroupPlanning;
use App\Models\PresenceHoraire;

class AbsenceReportService
{
    const TZ = 'Africa/Kinshasa';
    const DEFAULT_TOLERANCE_MIN = 50;

    /**
     * Retourne un tableau des packs par site :
     * [
     *   ['site' => Site, 'rows' => [ ... agents absents ... ]],
     *   ...
     * ]
     * - Exclut les sites sans absents
     * - Trie les agents par nom dans chaque site
     * - Trie les sites par nom
     */
    public function collectAbsences(Carbon $now = null): array
    {
        $now = $now?->copy()->timezone(self::TZ) ?? Carbon::now(self::TZ);
        $today = $now->toDateString();
        $bySite = [];

        $agents = Agent::with(['site.areas', 'groupe.horaire'])
            ->whereNotNull('site_id')
            ->whereNotNull('groupe_id')
            ->get();

        foreach ($agents as $agent) {
            $site = $agent->site;
            if (!$site) continue;

            $areas = $site->areas;
            if ($areas->isEmpty()) {
                continue;
            }

            // Doit avoir une assignation active
            if (!$this->hasActiveAssignment($agent->id, $now)) continue;

            $groupe  = $agent->groupe;
            $horaire = $this->getHoraire($groupe->horaire);

            $absenceInfo = null;

            if ($groupe->id === 2) {
                // Groupe H24
                $absenceInfo = $this->checkAbsenceH24($agent->id, $horaire, $now);
            } elseif ($groupe->id === 8 || is_null($horaire)) {
                // Groupe Flexible
                $absenceInfo = $this->checkAbsenceFlexible($agent->id, $today, $now);
            } else {
                // Groupe Fixe
                $absenceInfo = $this->checkAbsenceFixed($agent->id, $horaire, $today, $now);
            }

            if ($absenceInfo && $absenceInfo['absent'] === true) {
                if (!isset($bySite[$site->id])) {
                    $bySite[$site->id] = [
                        'site' => $site,
                        'rows' => [],
                    ];
                }

                $bySite[$site->id]['rows'][] = [
                    'matricule'      => $agent->matricule,
                    'fullname'       => $agent->fullname,
                    'groupe'         => $groupe->libelle,
                    'horaire_label'  => $absenceInfo['expected_label'],
                    'expected_start' => $absenceInfo['expected_start'],
                    'deadline'       => $absenceInfo['deadline'],
                    'reason'         => $absenceInfo['reason'],
                ];
            }
        }

        // Trier les agents par nom dans chaque site
        foreach ($bySite as &$pack) {
            usort($pack['rows'], fn($a, $b) => strcmp($a['fullname'], $b['fullname']));
        }
        unset($pack);

        // Exclure sites sans absents et trier les sites
        $packs = array_values(array_filter($bySite, fn($p) => !empty($p['rows'])));
        usort($packs, fn($a, $b) => strcmp($a['site']->name ?? '', $b['site']->name ?? ''));

        return $packs;
    }

    private function hasActiveAssignment(int $agentId, Carbon $now): bool
    {
        return AgentGroupAssignment::where('agent_id', $agentId)
            ->whereDate('start_date', '<=', $now->toDateString())
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $now->toDateString());
            })
            ->exists();
    }

    private function getHoraire(?PresenceHoraire $h): ?array
    {
        if (!$h) return null;

        $start = Carbon::parse($h->started_at)->format('H:i:s');
        $end   = Carbon::parse($h->ended_at)->format('H:i:s');

        $tolerance = null;
        if ($h->tolerence) {
            $t = Carbon::parse($h->tolerence);
            $tolerance = ((int)$t->format('H')) * 60 + (int)$t->format('i');
        }

        return [
            'id'        => $h->id,
            'label'     => $h->libelle,
            'start'     => $start,
            'end'       => $end,
            'tolerance' => $tolerance,
        ];
    }

    private function checkAbsenceFixed(int $agentId, ?array $horaire, string $today, Carbon $now): ?array
    {
        if (!$horaire) return null;

        $tol = $horaire['tolerance'] ?? self::DEFAULT_TOLERANCE_MIN;
        $dateRef = $this->computeDateReference($now, $horaire['start'], $horaire['end']);
        $expectedStart = Carbon::parse($dateRef . ' ' . $horaire['start'], self::TZ);
        $deadline = $expectedStart->copy()->addMinutes($tol);

        if ($now->lessThanOrEqualTo($deadline)) return ['absent' => false];

        $presence = PresenceAgents::where('agent_id', $agentId)
            ->whereDate('date_reference', $dateRef)
            ->first();

        if ($presence) return ['absent' => false];

        return [
            'absent'         => true,
            'expected_label' => $horaire['label'],
            'expected_start' => $expectedStart->format('d/m/Y H:i'),
            'deadline'       => $deadline->format('d/m/Y H:i'),
            'reason'         => 'Aucun check-in détecté après la tolérance',
        ];
    }

    private function checkAbsenceFlexible(int $agentId, string $today, Carbon $now): ?array
    {
        $planning = AgentGroupPlanning::with('horaire')
            ->where('agent_id', $agentId)
            ->whereDate('date', $today)
            ->first();

        if (!$planning || !$planning->horaire) return null;
        if ($planning->is_rest_day) return ['absent' => false];

        $h = $planning->horaire;
        $horaire = [
            'id'        => $h->id,
            'label'     => $h->libelle,
            'start'     => Carbon::parse($h->started_at)->format('H:i:s'),
            'end'       => Carbon::parse($h->ended_at)->format('H:i:s'),
            'tolerance' => $h->tolerence
                ? ((int)Carbon::parse($h->tolerence)->format('H')) * 60 + (int)Carbon::parse($h->tolerence)->format('i')
                : self::DEFAULT_TOLERANCE_MIN,
        ];

        $tol = $horaire['tolerance'] ?? self::DEFAULT_TOLERANCE_MIN;
        $dateRef = $this->computeDateReference($now, $horaire['start'], $horaire['end']);
        $expectedStart = Carbon::parse($dateRef . ' ' . $horaire['start'], self::TZ);
        $deadline = $expectedStart->copy()->addMinutes($tol);

        if ($now->lessThanOrEqualTo($deadline)) return ['absent' => false];

        $presence = PresenceAgents::where('agent_id', $agentId)
            ->whereDate('date_reference', $dateRef)
            ->first();

        if ($presence) return ['absent' => false];

        return [
            'absent'         => true,
            'expected_label' => $horaire['label'],
            'expected_start' => $expectedStart->format('d/m/Y H:i'),
            'deadline'       => $deadline->format('d/m/Y H:i'),
            'reason'         => 'Aucun check-in détecté (planning flexible)',
        ];
    }

    private function checkAbsenceH24(int $agentId, ?array $horaire, Carbon $now): ?array
    {
        if (!$horaire) return null;

        $tz = self::TZ;
        $tolerance = $horaire['tolerance'] ?? self::DEFAULT_TOLERANCE_MIN;

        $anchor = $now->copy()->startOfDay()->setTimeFromTimeString($horaire['start']);
        if ($now->lt($anchor)) $anchor->subDay();

        $deadline = $anchor->copy()->addMinutes($tolerance);

        // Service en cours ?
        $active = PresenceAgents::where('agent_id', $agentId)
            ->whereNull('ended_at')
            ->exists();
        if ($active) return ['absent' => false];

        $windowStart = $anchor->copy();
        $windowEnd   = $anchor->copy()->addDay();

        // Service clôturé dans la fenêtre ?
        $endedToday = PresenceAgents::where('agent_id', $agentId)
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$windowStart, $windowEnd])
            ->exists();
        if ($endedToday) return ['absent' => false];

        // Jour de repos après checkout
        $lastEnded = PresenceAgents::where('agent_id', $agentId)
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at')
            ->first();

        if ($lastEnded) {
            $checkout = Carbon::parse($lastEnded->ended_at, $tz);
            // Si le checkout était hier et que maintenant matin => repos
            if ($checkout->copy()->addHours(12)->gte($now)) {
                return ['absent' => false, 'reason' => 'Jour de repos post-checkout H24'];
            }
        }

        // Tolérance dépassée ?
        if ($now->lte($deadline)) return ['absent' => false];

        // Service démarré dans la fenêtre ?
        $startedInWindow = PresenceAgents::where('agent_id', $agentId)
            ->whereBetween('started_at', [$windowStart, $windowEnd])
            ->exists();
        if ($startedInWindow) return ['absent' => false];

        return [
            'absent'         => true,
            'expected_label' => $horaire['label'] ?? 'H24',
            'expected_start' => $anchor->format('d/m/Y H:i'),
            'deadline'       => $deadline->format('d/m/Y H:i'),
            'reason'         => 'Aucun service en cours ni prévu après jour de repos H24',
        ];
    }

    private function computeDateReference(Carbon $now, string $startHHMM, string $endHHMM): string
    {
        $start = Carbon::parse($now->toDateString() . ' ' . $startHHMM, self::TZ);
        $end   = Carbon::parse($now->toDateString() . ' ' . $endHHMM, self::TZ);
        $crossesMidnight = $end->lessThanOrEqualTo($start);

        if ($crossesMidnight) {
            $end->addDay();
            if ($now->lessThan($end)) {
                return $now->copy()->subDay()->toDateString();
            }
        }

        return $now->toDateString();
    }
}
