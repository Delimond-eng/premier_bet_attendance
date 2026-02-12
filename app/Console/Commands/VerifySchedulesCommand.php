<?php

namespace App\Console\Commands;

use App\Services\ScheduleService;
use Illuminate\Console\Command;

class VerifySchedulesCommand extends Command
{
    protected $signature = 'schedules:verify';
    protected $description = 'Vérifie les plannings et met à jour leur statut';

    protected ScheduleService $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        parent::__construct();
        $this->scheduleService = $scheduleService;
    }

    public function handle()
    {
        $this->scheduleService->verifySchedules();
        $this->info('Vérification des plannings terminée.');
    }
}
