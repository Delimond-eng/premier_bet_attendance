<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupPlanning;
use App\Models\AgentHistory;
use App\Models\AttendanceAuthorization;
use App\Models\AttendanceJustification;
use App\Models\Conge;
use App\Models\CongeType;
use App\Models\PresenceAgents;
use App\Models\PresenceHoraire;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = FakerFactory::create('fr_FR');
        $now = Carbon::now()->setTimezone('Africa/Kinshasa');
        $approverId = User::query()->orderBy('id')->value('id');

        // 1) Stations
        $stationsSeed = [
            ['code' => 'DEMO-GOM', 'name' => 'Station Gombe', 'adresse' => 'Gombe, Avenue du Fleuve', 'latlng' => '-4.320,15.310'],
            ['code' => 'DEMO-LIM', 'name' => 'Station Limete', 'adresse' => 'Limete, Industriel', 'latlng' => '-4.342,15.343'],
            ['code' => 'DEMO-NGD', 'name' => 'Station Ngaliema', 'adresse' => 'Ngaliema, Cité Verte', 'latlng' => '-4.372,15.258'],
            ['code' => 'DEMO-KSM', 'name' => 'Station Kasavubu', 'adresse' => 'Kasavubu, Marché Central', 'latlng' => '-4.327,15.307'],
            ['code' => 'DEMO-MTN', 'name' => 'Station Matete', 'adresse' => 'Matete, Route de l’Aéroport', 'latlng' => '-4.402,15.355'],
            ['code' => 'DEMO-NDJ', 'name' => 'Station Ndjili', 'adresse' => 'Ndjili, Boulevard Lumumba', 'latlng' => '-4.389,15.394'],
        ];

        $stations = collect($stationsSeed)->map(function ($s) use ($faker) {
            return Station::updateOrCreate(
                ['code' => $s['code']],
                [
                    'name' => $s['name'],
                    'adresse' => $s['adresse'],
                    'latlng' => $s['latlng'],
                    'phone' => $faker->phoneNumber(),
                    'presence' => random_int(5, 18),
                    'status' => 'actif',
                ]
            );
        });

        // 2) Horaires (2 par station)
        $horairesByStation = [];
        foreach ($stations as $station) {
            $day = PresenceHoraire::updateOrCreate(
                ['site_id' => $station->id, 'libelle' => 'Shift Jour'],
                [
                    'started_at' => '07:00',
                    'ended_at' => '18:00',
                    'tolerence_minutes' => 15,
                    'site_id' => $station->id,
                ]
            );

            $night = PresenceHoraire::updateOrCreate(
                ['site_id' => $station->id, 'libelle' => 'Shift Nuit'],
                [
                    'started_at' => '19:00',
                    'ended_at' => '06:00',
                    'tolerence_minutes' => 15,
                    'site_id' => $station->id,
                ]
            );

            $horairesByStation[$station->id] = [$day, $night];
        }

        // 3) Groupes d'agents
        $defaultHoraire = $horairesByStation[$stations->first()->id][0] ?? null;

        $groupsSeed = ['Equipe A', 'Equipe B', 'Equipe C'];
        $groups = collect($groupsSeed)->map(function ($name) use ($defaultHoraire) {
            return AgentGroup::updateOrCreate(
                ['libelle' => $name],
                [
                    'horaire_id' => $defaultHoraire?->id,
                    'cycle_days' => 7,
                    'status' => 'actif',
                ]
            );
        });

        // 4) Agents
        $targetAgents = 60;
        $existingAgents = Agent::count();
        $toCreate = max($targetAgents - $existingAgents, 0);

        for ($i = 1; $i <= $toCreate; $i++) {
            $station = $stations->random();
            $group = $groups->random();
            $stationHoraires = $horairesByStation[$station->id] ?? [];

            $horaire = $faker->boolean(70)
                ? ($stationHoraires[0] ?? null)
                : ($stationHoraires[1] ?? null);

            $matricule = 'AGT-' . str_pad((string) (1000 + $existingAgents + $i), 4, '0', STR_PAD_LEFT);

            Agent::create([
                'matricule' => $matricule,
                'fullname' => $faker->lastName() . ' ' . $faker->firstName(),
                'password' => Hash::make('salama123'),
                'role' => 'agent',
                'site_id' => $station->id,
                'groupe_id' => $group->id,
                'horaire_id' => $horaire?->id,
                'status' => 'actif',
            ]);
        }

        $agents = Agent::query()->with(['station', 'groupe', 'horaire'])->get();

        // 5) Historique d'affectation (quelques mutations)
        foreach ($agents->random(min(8, $agents->count())) as $agent) {
            $from = $stations->where('id', '!=', $agent->site_id)->random();
            $date = $now->copy()->subDays(random_int(10, 60));
            AgentHistory::updateOrCreate([
                'agent_id' => $agent->id,
                'date' => $date,
                'status' => 'mutation',
            ], [
                'site_id' => $agent->site_id,
                'site_provenance_id' => $from->id,
            ]);
        }

        // 6) Plannings du mois courant (pour semaine + matrice mensuelle)
        $startMonth = $now->copy()->startOfMonth();
        $endMonth = $now->copy()->endOfMonth();

        foreach ($agents as $agent) {
            $cursor = $startMonth->copy();
            while ($cursor->lte($endMonth)) {
                $isRest = $cursor->isSunday();

                $stationId = $agent->site_id;
                $stationHoraires = $horairesByStation[$stationId] ?? [];
                $horaire = $isRest ? null : ($cursor->isWeekend() ? ($stationHoraires[1] ?? ($stationHoraires[0] ?? null)) : ($stationHoraires[0] ?? null));

                AgentGroupPlanning::updateOrCreate(
                    ['agent_id' => $agent->id, 'date' => $cursor->toDateString()],
                    [
                        'agent_group_id' => $agent->groupe_id,
                        'horaire_id' => $horaire?->id,
                        'is_rest_day' => $isRest,
                    ]
                );

                $cursor->addDay();
            }
        }

        // 7) Présences (14 derniers jours)
        $daysBack = 14;
        $start = $now->copy()->subDays($daysBack)->startOfDay();
        $end = $now->copy()->startOfDay();

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            foreach ($agents as $agent) {
                // 80% présent (création presence), 20% absent (pas de ligne)
                if ($faker->boolean(20)) {
                    continue;
                }

                $planning = AgentGroupPlanning::query()
                    ->where('agent_id', $agent->id)
                    ->whereDate('date', $cursor->toDateString())
                    ->first();

                if ($planning && $planning->is_rest_day) {
                    continue;
                }

                $horaire = null;
                if ($planning?->horaire_id) {
                    $horaire = PresenceHoraire::find($planning->horaire_id);
                } elseif ($agent->horaire_id) {
                    $horaire = PresenceHoraire::find($agent->horaire_id);
                }

                $assignedStationId = $agent->site_id;
                $checkStationId = $faker->boolean(85) ? $assignedStationId : $stations->random()->id;

                $startedAt = $cursor->copy()->setTime(7, 0);
                $endedAt = null;
                $retard = 'non';

                if ($horaire) {
                    $rawStart = $horaire->getRawOriginal('started_at') ?? (string) $horaire->started_at;
                    $rawEnd = $horaire->getRawOriginal('ended_at') ?? (string) $horaire->ended_at;

                    // Les casts Laravel peuvent transformer TIME en datetime => on force un format heure uniquement.
                    $startTime = Carbon::parse($rawStart)->format('H:i:s');
                    $endTime = Carbon::parse($rawEnd)->format('H:i:s');

                    $shiftStart = Carbon::parse($cursor->toDateString(), 'Africa/Kinshasa')->setTimeFromTimeString($startTime);
                    $shiftEnd = Carbon::parse($cursor->toDateString(), 'Africa/Kinshasa')->setTimeFromTimeString($endTime);
                    if ($shiftEnd->lt($shiftStart)) {
                        $shiftEnd->addDay();
                    }

                    $retardChance = $faker->boolean(20);
                    if ($retardChance) {
                        $retard = 'oui';
                        $startedAt = $shiftStart->copy()->addMinutes(random_int(20, 90));
                    } else {
                        $startedAt = $shiftStart->copy()->addMinutes(random_int(-5, 10));
                    }

                    if ($faker->boolean(85)) {
                        $endedAt = $shiftEnd->copy()->addMinutes(random_int(-10, 30));
                    }
                } else {
                    $startedAt = $cursor->copy()->setTime(7, 0)->addMinutes(random_int(0, 45));
                    if ($faker->boolean(80)) {
                        $endedAt = $startedAt->copy()->addHours(random_int(8, 12))->addMinutes(random_int(0, 30));
                    }
                }

                $duree = null;
                $stationOut = null;
                if ($endedAt) {
                    $mins = $startedAt->diffInMinutes($endedAt);
                    $duree = intdiv($mins, 60) . 'h ' . ($mins % 60) . 'min';
                    $stationOut = $faker->boolean(90) ? $checkStationId : $stations->random()->id;
                }

                PresenceAgents::updateOrCreate(
                    ['agent_id' => $agent->id, 'date_reference' => $cursor->toDateString()],
                    [
                        'site_id' => $assignedStationId,
                        'gps_site_id' => $checkStationId,
                        'station_check_in_id' => $checkStationId,
                        'station_check_out_id' => $stationOut,
                        'horaire_id' => $horaire?->id,
                        'started_at' => $startedAt,
                        'ended_at' => $endedAt,
                        'duree' => $duree,
                        'retard' => $retard,
                        'status' => $endedAt ? 'depart' : 'arrive',
                    ]
                );
            }
            $cursor->addDay();
        }

        // 8) Congés approuvés (dans le mois courant)
        $congeTypes = collect([
            ['libelle' => 'Annuel', 'description' => 'Congé annuel', 'status' => 'actif'],
            ['libelle' => 'Maladie', 'description' => 'Congé maladie', 'status' => 'actif'],
            ['libelle' => 'Exceptionnel', 'description' => 'Congé exceptionnel', 'status' => 'actif'],
        ])->map(function ($t) {
            return CongeType::updateOrCreate(
                ['libelle' => $t['libelle']],
                ['description' => $t['description'], 'status' => $t['status']]
            );
        });

        foreach ($agents->random(min(6, $agents->count())) as $agent) {
            $from = $now->copy()->subDays(random_int(3, 10))->toDateString();
            $to = Carbon::parse($from)->addDays(random_int(1, 4))->toDateString();
            $type = $congeTypes->random();

            Conge::updateOrCreate([
                'agent_id' => $agent->id,
                'date_debut' => $from,
                'date_fin' => $to,
            ], [
                'conge_type_id' => $type->id,
                'type' => $type->libelle,
                'motif' => 'Congé annuel (démo)',
                'status' => 'approved',
                'approved_by' => $approverId,
            ]);
        }

        // 9) Autorisations approuvées (quelques jours)
        foreach ($agents->random(min(10, $agents->count())) as $agent) {
            $dateRef = $now->copy()->subDays(random_int(0, 10))->toDateString();
            $type = $faker->randomElement(['maladie', 'deuil', 'retard', 'absence']);
            AttendanceAuthorization::updateOrCreate([
                'agent_id' => $agent->id,
                'date_reference' => $dateRef,
                'type' => $type,
            ], [
                'minutes' => random_int(15, 120),
                'reason' => 'Autorisation spéciale (démo)',
                'status' => 'approved',
                'approved_by' => $approverId,
            ]);
        }

        // 10) Justifications (retard & absence) approuvées
        $latePresences = PresenceAgents::query()->where('retard', 'oui')->limit(10)->get();
        foreach ($latePresences as $p) {
            AttendanceJustification::firstOrCreate([
                'agent_id' => $p->agent_id,
                'date_reference' => $p->date_reference,
                'kind' => 'retard',
            ], [
                'presence_agent_id' => $p->id,
                'justification' => 'Justification retard (démo)',
                'status' => 'approved',
                'approved_by' => $approverId,
            ]);
        }

        foreach ($agents->random(min(8, $agents->count())) as $agent) {
            $dateRef = $now->copy()->subDays(random_int(0, 10))->toDateString();
            AttendanceJustification::updateOrCreate([
                'agent_id' => $agent->id,
                'date_reference' => $dateRef,
                'kind' => 'absence',
            ], [
                'presence_agent_id' => null,
                'justification' => 'Justification absence (démo)',
                'status' => 'approved',
                'approved_by' => $approverId,
            ]);
        }

        $this->command?->info('✅ Données de démonstration insérées (stations, horaires, groupes, agents, plannings, présences, RH).');
    }
}
