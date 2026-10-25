<?php

namespace Tests\Unit;

use App\Http\Controllers\ExportController;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;

class MonthlyDoubleShiftExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();
        Container::setInstance($container);
        $container->instance('request', Request::create('/', 'GET'));
    }

    public function test_monthly_summary_counts_multiple_shifts_as_presence(): void
    {
        $controller = new ExportController();
        $method = new ReflectionMethod($controller, 'summarizeMatrix');
        $method->setAccessible(true);

        $rows = $method->invoke($controller, [
            'Agent (A001)' => [
                '01' => [
                    'status' => 'double_shift',
                    'arrivee' => '3 C-1',
                    'depart' => '19:00',
                    'late_first_checkin' => true,
                    'late_minutes' => 10,
                    'duration_minutes' => 360,
                    'overtime_minutes' => 0,
                ],
            ],
        ], new Collection(), 'details');

        $this->assertSame('3 C-1', $rows[0]['days']['01']);
        $this->assertSame(1, $rows[0]['present']);
        $this->assertSame(1, $rows[0]['retard']);
        $this->assertSame(1, $rows[0]['total_presences']);
        $this->assertSame(1, $rows[0]['total_retards']);
    }
}
