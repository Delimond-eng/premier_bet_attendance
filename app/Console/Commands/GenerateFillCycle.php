<?php

namespace App\Console\Commands;

use App\Models\AgentGroup;
use App\Models\GroupPlanningCycle;
use Illuminate\Console\Command;

class GenerateFillCycle extends Command
{
    /* protected $signature = 'cycle:generate-flexible {groupId}'; */
    protected $signature = 'cycle:generate-flexible';

    protected $description = 'Remplit automatiquement le cycle de planning pour un groupe flexible';

    public function handle()
    {
        $groupId = 8 /* $this->argument('groupId') */;
        $group = AgentGroup::find($groupId);
        if (!$group) {
            $this->error("Aucun groupe trouvé avec l'ID fourni.");
            return 1;
        }
        // Supprimer les anciens cycles pour ce groupe
        GroupPlanningCycle::where('agent_group_id', $groupId)->delete();

        $jours = [
            0 => ['horaire' => 'matin', 'is_rest' => false], // Lundi
            1 => ['horaire' => 'soir', 'is_rest' => false],  // Mardi
            2 => ['horaire' => null, 'is_rest' => true],     // Mercredi
            3 => ['horaire' => 'matin', 'is_rest' => false], // Jeudi
            4 => ['horaire' => 'soir', 'is_rest' => false],  // Vendredi
            5 => ['horaire' => null, 'is_rest' => true],     // Samedi
            6 => ['horaire' => 'matin', 'is_rest' => false], // Dimanche
        ];

        $horaireMap = [
            'matin' => 5, // Remplace 5 par l'ID réel de l'horaire matin dans ta base
            'soir'  => 7, // Remplace 7 par l'ID réel de l'horaire soir dans ta base
        ];

        foreach ($jours as $dayIndex => $info) {
            GroupPlanningCycle::create([
                'agent_group_id' => $groupId,
                'horaire_id'     => $info['horaire'] ? $horaireMap[$info['horaire']] : null,
                'day_index'      => $dayIndex,
                'is_rest_day'    => $info['is_rest'],
            ]);
        }

        $this->info("Cycle hebdomadaire flexible ajouté pour le groupe : {$group->name} (ID: $groupId)");
        return 0;
    }
}
