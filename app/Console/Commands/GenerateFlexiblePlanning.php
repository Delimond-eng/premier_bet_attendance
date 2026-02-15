<?php

namespace App\Console\Commands; 

use App\Models\AgentGroup;
use App\Models\AgentGroupAssignment; 
use App\Models\AgentGroupPlanning; 
use App\Models\GroupPlanningCycle; 
use Carbon\Carbon; 
use Illuminate\Console\Command; 
use Illuminate\Support\Facades\DB; 

class GenerateFlexiblePlanning extends Command 
{ 
    protected $signature = 'planning:generate-horaire 
        {--group= : Agent group id (optional). If omitted, generates for all flexible groups (horaire_id NULL)} 
        {--days=7 : Number of days to generate} 
        {--start= : Start date (YYYY-MM-DD). Default: next Monday} 
        {--overwrite : Replace existing plannings} 
        {--dry-run : Do not write anything}'; 
 
    protected $description = 'Generate agent_group_plannings from group_planning_cycles for flexible groups (horaire_id NULL) and assigned agents.'; 
 
    public function handle(): int 
    { 
        $tz = 'Africa/Kinshasa'; 
 
        $days = max((int) $this->option('days'), 1); 
        $overwrite = (bool) $this->option('overwrite'); 
        $dryRun = (bool) $this->option('dry-run'); 
 
        $now = Carbon::now($tz)->startOfDay(); 
        $start = $this->option('start') 
            ? Carbon::parse((string) $this->option('start'), $tz)->startOfDay() 
            : $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY); 
 
        $from = $start->toDateString(); 
        $to = $start->copy()->addDays($days - 1)->toDateString(); 

        $optGroup = $this->option('group'); 
        if (!empty($optGroup)) { 
            $groupIds = [(int) $optGroup]; 
        } else { 
            // Flexible groups = groups without a default horaire_id. 
            $groupIds = AgentGroup::query() 
                ->whereNull('horaire_id') 
                ->orderBy('id') 
                ->pluck('id') 
                ->map(fn ($v) => (int) $v) 
                ->all(); 
        } 
 
        if (empty($groupIds)) { 
            $this->warn('No flexible group found (agent_groups.horaire_id is NULL).'); 
            return Command::SUCCESS; 
        } 
 
        $total = [ 
            'groups_scanned' => 0, 
            'groups_generated' => 0, 
            'groups_no_cycle' => 0, 
            'groups_no_assignments' => 0, 
            'groups_missing' => 0, 
            'agents' => 0, 
            'created' => 0, 
            'updated' => 0, 
            'skipped' => 0, 
        ]; 
 
        foreach ($groupIds as $groupId) { 
            $total['groups_scanned'] += 1; 
            $group = AgentGroup::query()->find($groupId, ['id', 'libelle', 'horaire_id']); 
            if (!$group) { 
                $this->warn("Group not found: id={$groupId}"); 
                $total['groups_missing'] += 1; 
                continue; 
            } 
 
            // If user passed --group explicitly, allow any group; otherwise, only flexible groups are selected above.
            if (empty($optGroup) && !empty($group->horaire_id)) { 
                $this->warn("Skip non-flexible group id={$groupId} (horaire_id not NULL)."); 
                continue; 
            } 
 
            $cycleByDayIndex = GroupPlanningCycle::query() 
                ->where('agent_group_id', $groupId) 
                ->get() 
                ->keyBy('day_index'); 
 
            if ($cycleByDayIndex->isEmpty()) { 
                $this->warn("No cycle found for group_id={$groupId} in group_planning_cycles."); 
                $total['groups_no_cycle'] += 1; 
                continue; 
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
                $total['groups_no_assignments'] += 1; 
                continue; 
            } 
 
            $stats = [ 
                'agents' => 0, 
                'created' => 0, 
                'updated' => 0, 
                'skipped' => 0, 
            ]; 
 
            $label = trim((string) ($group->libelle ?? '')); 
            $label = $label !== '' ? $label : "id={$groupId}"; 
            $this->info("Generate planning group={$label} from {$from} to {$to} (days={$days})" . ($dryRun ? " [DRY RUN]" : "")); 
 
            $work = function () use ($assignments, $cycleByDayIndex, $groupId, $start, $days, $overwrite, $dryRun, &$stats) { 
                foreach ($assignments as $assignment) { 
                    $agent = $assignment->agent; 
                    if (!$agent) { 
                        continue; 
                    } 
 
                    $stats['agents'] += 1; 
 
                    for ($i = 0; $i < $days; $i += 1) { 
                        $date = $start->copy()->addDays($i)->toDateString(); 
 
                        // Respect each agent assignment range (start_date/end_date) inside the window. 
                        if (!empty($assignment->start_date) && $date < (string) $assignment->start_date) { 
                            $stats['skipped'] += 1; 
                            continue; 
                        } 
                        if (!empty($assignment->end_date) && $date > (string) $assignment->end_date) { 
                            $stats['skipped'] += 1; 
                            continue; 
                        } 
 
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
 
            $total['groups_generated'] += 1; 
            $total['agents'] += $stats['agents']; 
            $total['created'] += $stats['created']; 
            $total['updated'] += $stats['updated']; 
            $total['skipped'] += $stats['skipped']; 
        } 
 
        $this->info( 
            "Done. " 
            . "GroupsScanned={$total['groups_scanned']} " 
            . "GroupsGenerated={$total['groups_generated']} " 
            . "NoCycle={$total['groups_no_cycle']} " 
            . "NoAssignments={$total['groups_no_assignments']} " 
            . "Missing={$total['groups_missing']} " 
            . "Agents={$total['agents']} " 
            . "Created={$total['created']} " 
            . "Updated={$total['updated']} " 
            . "Skipped={$total['skipped']}" 
        ); 
        return Command::SUCCESS; 
    } 
} 
