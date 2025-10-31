<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExport implements WithMultipleSheets
{
    use Exportable;

    protected $userStats;

    public function __construct($userStats)
    {
        $this->userStats = $userStats;
    }

    public function sheets(): array
    {
        return [
            'UserStatsSheet' => new UserStatsSheet($this->userStats),
        ];
    }
}
