<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContinueWeeklyPlanningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('agent_group_plannings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('agent_group_id');
            $table->unsignedBigInteger('horaire_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->date('date');
            $table->boolean('is_rest_day')->default(false);
            $table->timestamps();
        });
    }

    public function test_it_copies_the_previous_week_and_updates_matching_plannings_idempotently(): void
    {
        $this->insertPlanning(1, 2, 3, 7, '2025-06-09', false);
        $this->insertPlanning(4, 5, null, null, '2025-06-10', true);
        $this->insertPlanning(1, 8, null, 7, '2025-06-16', true);

        $this->artisan('planning:continue-week', ['--week' => '2025-06-18'])
            ->assertExitCode(0);

        $monday = DB::table('agent_group_plannings')
            ->where('agent_id', 1)
            ->where('date', '2025-06-16')
            ->first();
        $this->assertSame(2, $monday->agent_group_id);
        $this->assertSame(3, $monday->horaire_id);
        $this->assertSame(7, $monday->site_id);
        $this->assertSame(0, $monday->is_rest_day);

        $tuesday = DB::table('agent_group_plannings')
            ->where('agent_id', 4)
            ->where('date', '2025-06-17')
            ->first();
        $this->assertNotNull($tuesday);
        $this->assertNull($tuesday->site_id);
        $this->assertSame(1, $tuesday->is_rest_day);

        $this->artisan('planning:continue-week', ['--week' => '2025-06-18'])
            ->assertExitCode(0);

        $this->assertSame(4, DB::table('agent_group_plannings')->count());
    }

    public function test_dry_run_does_not_write_plannings(): void
    {
        $this->insertPlanning(1, 2, 3, 7, '2025-06-09', false);

        $this->artisan('planning:continue-week', [
            '--week' => '2025-06-16',
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, DB::table('agent_group_plannings')->count());
    }

    private function insertPlanning(
        int $agentId,
        int $groupId,
        ?int $horaireId,
        ?int $siteId,
        string $date,
        bool $isRestDay
    ): void {
        DB::table('agent_group_plannings')->insert([
            'agent_id' => $agentId,
            'agent_group_id' => $groupId,
            'horaire_id' => $horaireId,
            'site_id' => $siteId,
            'date' => $date,
            'is_rest_day' => $isRestDay,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
