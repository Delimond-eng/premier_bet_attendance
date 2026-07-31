<?php

namespace Tests\Feature;

use App\Http\Controllers\ExportController;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ExportFunctionColumnTest extends TestCase
{
    public function test_summary_rows_include_agent_function(): void
    {
        $controller = new ExportController();

        $agent = new \stdClass();
        $agent->id = 12;
        $agent->fullname = 'Jean Dupont';
        $agent->matricule = 'A-001';
        $agent->photo = null;
        $agent->site_id = 5;
        $agent->station = null;
        $agent->fonction = 'Superviseur';

        $matrix = [
            'Jean Dupont (A-001)' => [
                '01/01' => [
                    'status' => 'present',
                    'overtime_minutes' => 0,
                    'late_minutes' => 0,
                    'duration_minutes' => 0,
                ],
            ],
        ];

        $method = new \ReflectionMethod(ExportController::class, 'summarizeMatrix');
        $method->setAccessible(true);

        $rows = $method->invoke($controller, $matrix, new Collection([$agent]), 'brut');

        $this->assertSame('Superviseur', $rows[0]['agent']['fonction']);
    }
}
