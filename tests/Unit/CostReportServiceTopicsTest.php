<?php

namespace Tests\Unit;

use App\Services\CostReportService;
use PHPUnit\Framework\TestCase;

class CostReportServiceTopicsTest extends TestCase
{
    public function test_cost_sheet_defines_six_topics_in_order(): void
    {
        $topics = array_keys((new CostReportService())->topics());

        $this->assertSame([
            'direct',
            'utilities',
            'maintenance',
            'packing_logistics',
            'indirect_labor',
            'admin_overheads',
        ], $topics);
    }
}
