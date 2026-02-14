<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentGroup;
use App\Models\AgentGroupAssignment;
use App\Models\GroupPlanningCycle;
use App\Models\PresenceHoraire;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('Agents_Perspective.csv');
        if (!is_file($csvPath)) {
            $this->command?->warn("Agents_Perspective.csv introuvable: {$csvPath}");
            return;
        }

        $rows = $this->readCsv($csvPath);
        if (count($rows) < 2) {
            $this->command?->warn("Agents_Perspective.csv est vide ou sans lignes exploitables.");
            return;
        }

        $header = array_map(fn ($v) => strtoupper(trim((string) $v)), $rows[0]);
        $colAgent = $this->findColumnIndex($header, ['AGENT', 'NOM', 'FULLNAME']);
        $colMatricule = $this->findColumnIndex($header, ['MATRICULE', 'MAT']);

        // Expected day columns, but we only need them to extract unique horaires.
        $dayCols = [];
        foreach (['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'] as $day) {
            $idx = $this->findColumnIndex($header, [$day]);
            if ($idx !== null) {
                $dayCols[] = $idx;
            }
        }

        if ($colMatricule === null) {
            $this->command?->warn("CSV invalide: colonne MATRICULE introuvable.");
            return;
        }

        if (count($dayCols) !== 7) {
            $this->command?->warn("CSV invalide: colonnes LUNDI..DIMANCHE introuvables ou incompletes.");
            return;
        }

        $tz = 'Africa/Kinshasa';
        $now = Carbon::now($tz)->startOfDay();

        Schema::disableForeignKeyConstraints();
        try {
            $this->truncateOrDelete('agent_group_plannings');
            $this->truncateOrDelete('agent_group_assignments');
            $this->truncateOrDelete('group_planning_cycles');
            $this->truncateOrDelete('agents');
            $this->truncateOrDelete('agent_groups');
            $this->truncateOrDelete('presence_horaires');
            $this->truncateOrDelete('sites');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $station = Station::create([
            'name' => 'ITIMBIRI',
            'code' => 'ITIMBIRI',
            'adresse' => 'Direction General (ITIMBIRI)',
            'latlng' => null,
            'phone' => null,
            'presence' => 1,
            'status' => 'actif',
        ]);

        $flexGroup = AgentGroup::create([
            'libelle' => 'SHIFT FLEXIBLE',
            'horaire_id' => null,
            'cycle_days' => 7,
            'status' => 'actif',
        ]);

        // 1) Create unique horaires from CSV time ranges.
        $ranges = $this->extractTimeRanges($rows, $dayCols);
        foreach ($ranges as $key => [$start, $end]) {
            PresenceHoraire::create([
                'libelle' => "Horaire {$start}-{$end}",
                'started_at' => $start,
                'ended_at' => $end,
                'tolerence_minutes' => 15,
                'site_id' => $station->id,
            ]);
        }

        // 2) Create agents and assign them to station + flexible group.
        $createdAgents = 0;
        foreach (array_slice($rows, 1) as $row) {
            $matricule = trim((string) ($row[$colMatricule] ?? ''));
            if ($matricule === '') {
                continue;
            }

            $fullname = $colAgent !== null ? trim((string) ($row[$colAgent] ?? '')) : '';
            if ($fullname === '') {
                $fullname = $matricule;
            }

            $agent = Agent::create([
                'matricule' => $matricule,
                'fullname' => $fullname,
                'password' => Hash::make('salama123'),
                'role' => 'agent',
                'site_id' => $station->id,
                'groupe_id' => $flexGroup->id,
                'horaire_id' => null,
                'status' => 'actif',
            ]);

            AgentGroupAssignment::create([
                'agent_id' => $agent->id,
                'agent_group_id' => $flexGroup->id,
                'start_date' => $now->toDateString(),
                'end_date' => null,
            ]);

            $createdAgents += 1;
        }

        // Keep these empty: the planning will be imported via CSV/Excel.
        GroupPlanningCycle::where('agent_group_id', $flexGroup->id)->delete();

        $this->command?->info("✅ Seeder ITIMBIRI OK: 1 station, " . count($ranges) . " horaires, {$createdAgents} agents, groupe SHIFT FLEXIBLE.");
    }

    private function truncateOrDelete(string $table): void
    {
        try {
            DB::table($table)->truncate();
        } catch (\Throwable $_) {
            DB::table($table)->delete();
        }
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readCsv(string $path): array
    {
        $firstLine = '';
        try {
            $fh = fopen($path, 'rb');
            if (is_resource($fh)) {
                $firstLine = (string) fgets($fh);
                fclose($fh);
            }
        } catch (\Throwable $_) {
        }

        $delimiter = $this->sniffDelimiter($firstLine);
        $file = new \SplFileObject($path, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delimiter);

        $rows = [];
        foreach ($file as $row) {
            if (!is_array($row)) {
                continue;
            }
            // Normalize to simple array of strings.
            $rows[] = array_map(fn ($v) => $v === null ? null : (string) $v, $row);
        }

        // Remove trailing empty line produced by SplFileObject sometimes.
        while (!empty($rows) && count(array_filter($rows[count($rows) - 1], fn ($v) => trim((string) $v) !== '')) === 0) {
            array_pop($rows);
        }

        return $rows;
    }

    private function sniffDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = -1;
        foreach ($candidates as $cand) {
            $count = substr_count($line, $cand);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $cand;
            }
        }
        return $best;
    }

    /**
     * @param array<int, string> $header
     * @param array<int, string> $needles
     */
    private function findColumnIndex(array $header, array $needles): ?int
    {
        $needles = array_map(fn ($s) => strtoupper(trim((string) $s)), $needles);
        foreach ($header as $i => $h) {
            $v = strtoupper(trim((string) $h));
            foreach ($needles as $n) {
                if ($v === $n) {
                    return (int) $i;
                }
            }
        }
        return null;
    }

    /**
     * @param array<int, array<int, string|null>> $rows
     * @param array<int, int> $dayCols
     * @return array<string, array{0:string,1:string}>
     */
    private function extractTimeRanges(array $rows, array $dayCols): array
    {
        $ranges = [];
        foreach (array_slice($rows, 1) as $row) {
            foreach ($dayCols as $idx) {
                $cell = trim((string) ($row[$idx] ?? ''));
                if ($cell === '' || strtoupper($cell) === 'OFF') {
                    continue;
                }
                if (preg_match('/^(\\d{1,2})\\s*:\\s*(\\d{2})\\s*-\\s*(\\d{1,2})\\s*:\\s*(\\d{2})$/', $cell, $m)) {
                    $start = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
                    $end = sprintf('%02d:%02d', (int) $m[3], (int) $m[4]);
                    $ranges[$start . '-' . $end] = [$start, $end];
                }
            }
        }
        ksort($ranges);
        return $ranges;
    }
}

