<?php

namespace App\Console\Commands;

use App\Http\Controllers\AppManagerController;
use Illuminate\Console\Command;

class AutoPlanningCreating extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plannings:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "génère une liste de planning à partir du 21h jusqu'à 5h";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        (new AppManagerController())->autoCreateNightPlannings();
        $this->info("✅ Liste des plannings nocturnes génèrée avec succès.");
        return Command::SUCCESS;
    }
}
