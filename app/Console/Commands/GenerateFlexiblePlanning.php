<?php

namespace App\Console\Commands;

use App\Models\AgentGroupAssignment;
use App\Models\AgentGroupPlanning;
use App\Models\GroupPlanningCycle;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateFlexiblePlanning extends Command
{
    protected $signature = 'planning:generate-horaire
        {--group=8 : Agent group id (default 8)}
        {--days=7 : Number of days to generate}
        {--start= : Start date (YYYY-MM-DD). Default: next Monday}
        {--overwrite : Replace existing plannings}
        {--dry-run : Do not write anything}';

    protected $description = 'Generate agent_group_plannings from group_planning_cycles for assigned agents.';

    public function handle(): int
    {
        $tz = 'Africa/Kinshasa';

        $groupId = (int) $this->option('group');
        $days = max((int) $this->option('days'), 1);
        $overwrite = (bool) $this->option('overwrite');
        $dryRun = (bool) $this->option('dry-run');

        $now = Carbon::now($tz)->startOfDay();
        $start = $this->option('start')
            ? Carbon::parse((string) $this->option('start'), $tz)->startOfDay()
            : $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY);

        $from = $start->toDateString();
        $to = $start->copy()->addDays($days - 1)->toDateString();

        $cycleByDayIndex = GroupPlanningCycle::query()
            ->where('agent_group_id', $groupId)
            ->get()
            ->keyBy('day_index');

        if ($cycleByDayIndex->isEmpty()) {
            $this->warn("No cycle found for group_id={$groupId} in group_planning_cycles.");
            return Command::SUCCESS;
        }

        $assignments = AgentGroupAssignment::query()
            ->with('agent')
            ->where('agent_group_id', $groupId)
            ->whereDate('start_date', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from);
            })
            ->get();

        if ($assignments->isEmpty()) {
            $this->warn("No active assignments found for group_id={$groupId}.");
            return Command::SUCCESS;
        }

        $stats = [
            'agents' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $this->info("Generate planning group={$groupId} from {$from} to {$to} (days={$days})" . ($dryRun ? " [DRY RUN]" : ""));

        $work = function () use ($assignments, $cycleByDayIndex, $groupId, $start, $days, $overwrite, $dryRun, &$stats) {
            foreach ($assignments as $assignment) {
                $agent = $assignment->agent;
                if (!$agent) {
                    continue;
                }

                $stats['agents'] += 1;

                for ($i = 0; $i < $days; $i += 1) {
                    $date = $start->copy()->addDays($i)->toDateString();
                    $dayIndex = (int) Carbon::parse($date)->dayOfWeekIso - 1; // 0..6 (Mon..Sun)

                    $cycle = $cycleByDayIndex->get($dayIndex);
                    $isRestDay = (bool) ($cycle?->is_rest_day ?? true);
                    $horaireId = $isRestDay ? null : ($cycle?->horaire_id ?? null);

                    if ($dryRun) {
                        if ($overwrite) {
                            $stats['updated'] += 1;
                        } else {
                            $stats['created'] += 1;
                        }
                        continue;
                    }

                    if ($overwrite) {
                        AgentGroupPlanning::updateOrCreate(
                            ['agent_id' => $agent->id, 'agent_group_id' => $groupId, 'date' => $date],
                            ['horaire_id' => $horaireId, 'is_rest_day' => $isRestDay]
                        );
                        $stats['updated'] += 1;
                        continue;
                    }

                    $exists = AgentGroupPlanning::query()
                        ->where('agent_id', $agent->id)
                        ->where('agent_group_id', $groupId)
                        ->whereDate('date', $date)
                        ->exists();

                    if ($exists) {
                        $stats['skipped'] += 1;
                        continue;
                    }

                    AgentGroupPlanning::create([
                        'agent_id' => $agent->id,
                        'agent_group_id' => $groupId,
                        'horaire_id' => $horaireId,
                        'date' => $date,
                        'is_rest_day' => $isRestDay,
                    ]);
                    $stats['created'] += 1;
                }
            }
        };

        if ($dryRun) {
            $work();
        } else {
            DB::transaction($work);
        }

        $this->line("Agents: {$stats['agents']}");
        $this->line("Created: {$stats['created']}");
        $this->line("Updated: {$stats['updated']}");
        $this->line("Skipped: {$stats['skipped']}");

        return Command::SUCCESS;
    }
}

