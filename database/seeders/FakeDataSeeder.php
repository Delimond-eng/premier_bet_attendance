<?php

namespace Database\Seeders;

use App\Models\Agencie;
use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\Conge;
use App\Models\CongeType;
use App\Models\AttendanceAuthorization;
use App\Models\MaintenanceAgent;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        // 1. Créer une Agence
        $agency = Agencie::create([
            'name' => 'SALAMA PROTECTION HQ',
            'adresse' => 'Kinshasa, RDC',
            'phone' => '+243000000000',
            'email' => 'contact@salama.cd'
        ]);

        // 2. Créer des Stations (Sites)
        $stations = [];
        $stationNames = ['BCC', 'EQUATEUR', 'GOMBE', 'LIMETE', 'NGALIEMA'];
        foreach ($stationNames as $name) {
            $stations[] = Station::create([
                'name' => $name,
                'code' => strtoupper($name),
                'adresse' => $faker->address,
                'latlng' => '-4.322447,15.307045',
                'phone' => $faker->phoneNumber,
                'presence' => rand(5, 15),
                'status' => 'actif',
            ]);
        }

        // 3. Créer des Horaires
        $horaires = [];
        $horairesData = [
            ['libelle' => 'SHIFT JOUR', 'start' => '07:00', 'end' => '19:00'],
            ['libelle' => 'SHIFT NUIT', 'start' => '19:00', 'end' => '07:00'],
            ['libelle' => 'ADMINISTRATIF', 'start' => '08:00', 'end' => '16:00'],
        ];

        foreach ($horairesData as $h) {
            foreach ($stations as $s) {
                $horaires[] = PresenceHoraire::create([
                    'libelle' => $h['libelle'] . " - " . $s->name,
                    'started_at' => $h['start'],
                    'ended_at' => $h['end'],
                    'tolerence_minutes' => 15,
                    'site_id' => $s->id,
                ]);
            }
        }

        // 4. Créer des Types de Congés
        $congeTypes = [
            ['libelle' => 'Maladie', 'description' => 'Congé pour raison de santé'],
            ['libelle' => 'Congé Annuel', 'description' => 'Repos annuel légal'],
            ['libelle' => 'Circonstance', 'description' => 'Mariage, naissance, décès'],
            ['libelle' => 'Maternité', 'description' => 'Repos maternité'],
        ];
        $createdCongeTypes = [];
        foreach ($congeTypes as $type) {
            $createdCongeTypes[] = CongeType::create($type + ['status' => 'actif']);
        }

        // 5. Créer un Groupe d'Agents
        $group = AgentGroup::create([
            'libelle' => 'AGENTS DE SECURITE',
            'horaire_id' => $horaires[0]->id,
            'cycle_days' => 7,
            'status' => 'actif',
        ]);

        // 6. Créer des Agents (25 agents)
        $agents = [];
        for ($i = 1; $i <= 25; $i++) {
            $station = $faker->randomElement($stations);
            $agents[] = Agent::create([
                'matricule' => 'AGT' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'fullname' => $faker->name,
                'fonction' => $faker->jobTitle,
                'password' => Hash::make('password'),
                'role' => 'agent',
                'agency_id' => $agency->id,
                'site_id' => $station->id,
                'groupe_id' => $group->id,
                'horaire_id' => $faker->randomElement($horaires)->id,
                'status' => 'actif',
            ]);
        }

        $admin = User::where('role', 'admin')->first() ?? User::first();

        // 7. Créer des Congés (10 congés)
        for ($i = 0; $i < 10; $i++) {
            $agent = $faker->randomElement($agents);
            $startDate = Carbon::now()->subDays(rand(1, 30));
            $endDate = (copy($startDate))->addDays(rand(2, 10));

            Conge::create([
                'agent_id' => $agent->id,
                'conge_type_id' => $faker->randomElement($createdCongeTypes)->id,
                'date_debut' => $startDate,
                'date_fin' => $endDate,
                'motif' => $faker->sentence,
                'status' => $faker->randomElement(['approuvé', 'en attente', 'terminé']),
            ]);
        }

        // 8. Créer des Autorisations Spéciales (15 autorisations)
        $authTypes = ['Permission Sortie', 'Retard Autorisé', 'Départ Anticipé'];
        for ($i = 0; $i < 15; $i++) {
            $agent = $faker->randomElement($agents);
            $date = Carbon::now()->subDays(rand(0, 5));

            AttendanceAuthorization::create([
                'agent_id' => $agent->id,
                'date_reference' => $date->toDateString(),
                'type' => $faker->randomElement($authTypes),
                'started_at' => '10:00',
                'ended_at' => '12:00',
                'minutes' => 120,
                'reason' => $faker->sentence,
                'status' => 'approuvé',
                'approved_by' => $admin?->id,
            ]);
        }

        // 9. Créer des Présences (40 pointages)
        for ($i = 0; $i < 40; $i++) {
            $agent = $faker->randomElement($agents);
            $horaire = $agent->horaire ?? $faker->randomElement($horaires);
            $date = Carbon::now()->subDays(rand(0, 10));

            // Éviter les doublons de date_reference pour le même agent
            $exists = PresenceAgents::where('agent_id', $agent->id)
                ->where('date_reference', $date->toDateString())
                ->exists();

            if ($exists) continue;

            PresenceAgents::create([
                'agent_id' => $agent->id,
                'site_id' => $agent->site_id,
                'gps_site_id' => $agent->site_id,
                'station_check_in_id' => $agent->site_id,
                'horaire_id' => $horaire->id,
                'started_at' => $date->copy()->setTimeFromTimeString($horaire->started_at)->addMinutes(rand(-10, 30)),
                'ended_at' => $date->copy()->setTimeFromTimeString($horaire->ended_at)->addMinutes(rand(-10, 10)),
                'date_reference' => $date->toDateString(),
                'status' => $faker->randomElement(['present', 'retard']),
                'commentaires' => $faker->sentence,
            ]);
        }

        // 10. Créer des Maintenances (10 enregistrements)
        for ($i = 0; $i < 10; $i++) {
            $agent = $faker->randomElement($agents);
            $station = $faker->randomElement($stations);
            $date = Carbon::now()->subDays(rand(0, 15));

            MaintenanceAgent::create([
                'agent_id' => $agent->id,
                'station_id' => $station->id,
                'date_maintenance' => $date->toDateString(),
                'started_at' => $date->copy()->setTimeFromTimeString('08:00')->addMinutes(rand(0, 120)),
                'end_at' => $date->copy()->setTimeFromTimeString('16:00')->subMinutes(rand(0, 60)),
                'latlng' => $station->latlng ?? '-4.322447,15.307045',
                'commentaire' => 'Maintenance préventive : ' . $faker->sentence(),
            ]);
        }
    }
}
