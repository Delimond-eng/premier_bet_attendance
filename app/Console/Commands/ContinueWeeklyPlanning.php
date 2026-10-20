<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ContinueWeeklyPlanning extends Command
{
    protected $signature = 'planning:continue-week
        {--week= : A date in the target week (YYYY-MM-DD). Defaults to the current week}
        {--dry-run : Preview the changes without writing them}';

    protected $description = 'Copy last week\'s planning configuration into the target week.';

    public function handle(): int
    {
        $timezone = 'Africa/Kinshasa';
        $weekOption = $this->option('week');
        $weekStart = $weekOption
            ? Carbon::parse((string) $weekOption, $timezone)->startOfWeek(Carbon::MONDAY)
            : Carbon::now($timezone)->startOfWeek(Carbon::MONDAY);
        $previousWeekStart = $weekStart->copy()->subWeek();
        $previousWeekEnd = $previousWeekStart->copy()->addDays(6);

        $plannings = DB::table('agent_group_plannings')
            ->whereBetween('date', [
                $previousWeekStart->toDateString(),
                $previousWeekEnd->toDateString(),
            ])
            ->orderBy('id')
            ->get([
                'agent_id',
                'agent_group_id',
                'horaire_id',
                'site_id',
                'date',
                'is_rest_day',
            ]);

        if ($plannings->isEmpty()) {
            $this->error('No planning found in the previous week.');

            return Command::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $timestamp = Carbon::now($timezone)->toDateTimeString();
        $stats = ['created' => 0, 'updated' => 0];

        $continuePlanning = function () use ($plannings, $dryRun, $timestamp, &$stats): void {
            foreach ($plannings as $planning) {
                $targetDate = Carbon::parse((string) $planning->date)
                    ->addWeek()
                    ->toDateString();
                $targetQuery = DB::table('agent_group_plannings')
                    ->where('agent_id', $planning->agent_id)
                    ->whereDate('date', $targetDate);

                if ($planning->site_id === null) {
                    $targetQuery->whereNull('site_id');
                } else {
                    $targetQuery->where('site_id', $planning->site_id);
                }

                $existingId = $targetQuery->value('id');
                $stats[$existingId === null ? 'created' : 'updated']++;

                if ($dryRun) {
                    continue;
                }

                $values = [
                    'agent_group_id' => $planning->agent_group_id,
                    'horaire_id' => $planning->horaire_id,
                    'is_rest_day' => $planning->is_rest_day,
                    'updated_at' => $timestamp,
                ];

                if ($existingId !== null) {
                    DB::table('agent_group_plannings')
                        ->where('id', $existingId)
                        ->update($values);
                    continue;
                }

                DB::table('agent_group_plannings')->insert([
                    'agent_id' => $planning->agent_id,
                    'agent_group_id' => $planning->agent_group_id,
                    'horaire_id' => $planning->horaire_id,
                    'site_id' => $planning->site_id,
                    'date' => $targetDate,
                    'is_rest_day' => $planning->is_rest_day,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        };

        if ($dryRun) {
            $continuePlanning();
        } else {
            DB::transaction($continuePlanning);
        }

        $this->info(sprintf(
            '%s planning for %s to %s. Created: %d, updated: %d%s',
            $dryRun ? 'Previewed' : 'Continued',
            $weekStart->toDateString(),
            $weekStart->copy()->addDays(6)->toDateString(),
            $stats['created'],
            $stats['updated'],
            $dryRun ? ' (dry run)' : ''
        ));

        return Command::SUCCESS;
    }
}
