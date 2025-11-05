<?php

namespace App\Http\Controllers;

use App\Exports\DashboardExport;

class DashboardExportController extends Controller
{
    protected $sellController;
    protected $admOfferController;
    protected $admUserController;

    public function __construct(
        SellController $sellController,
        AdmOfferController $admOfferController,
        AdmUserController $admUserController
    ) {
        $this->sellController = $sellController;
        $this->admOfferController = $admOfferController;
        $this->admUserController = $admUserController;
    }

    public function export()
    {
        try {
            $lastSellsResponse = $this->sellController->lastSells();
            $lastSells = json_decode($lastSellsResponse->getContent(), true)['data'];

            $offerStatsResponse = $this->admOfferController->offerStats();
            $offerStats = json_decode($offerStatsResponse->getContent(), true)['data'];

            $userStatsResponse = $this->admUserController->userStats();
            $userStatsData = json_decode($userStatsResponse->getContent(), true);
            $userStats = [
                'total' => $userStatsData['total'],
                'data' => $userStatsData['data']
            ];

            $activeOffersResponse = $this->admOfferController->activeOffersCount();
            $activeOffers = json_decode($activeOffersResponse->getContent(), true)['data'];

            $expiringOffersResponse = $this->admOfferController->expiringOffersCount();
            $expiringOffers = json_decode($expiringOffersResponse->getContent(), true)['data'];

            return (new DashboardExport($userStats, $lastSells, $activeOffers, $expiringOffers))
                ->download('dashboard-stats.xlsx');

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar el dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
