<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExport implements WithMultipleSheets
{
    use Exportable;

    protected $userStats;
    protected $lastSells;
    protected $activeOffers;
    protected $expiringOffers;

    public function __construct($userStats, $lastSells, $activeOffers, $expiringOffers)
    {
        $this->userStats = $userStats;
        $this->lastSells = $lastSells;
        $this->activeOffers = $activeOffers;
        $this->expiringOffers = $expiringOffers;
    }

    public function sheets(): array
    {
        return [
            new UserStatsSheet($this->userStats),
            new LastSellsSheet($this->lastSells),
            new ActiveOffersSheet($this->activeOffers),
            new ExpiringOffersSheet($this->expiringOffers),
        ];
    }
}
