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
            // Reutilizar lastSells de SellController
            $lastSellsResponse = $this->sellController->lastSells();
            $lastSells = json_decode($lastSellsResponse->getContent(), true)['data'];

            // Reutilizar offerStats de AdmOfferController
            $offerStatsResponse = $this->admOfferController->offerStats();
            $offerStats = json_decode($offerStatsResponse->getContent(), true)['data'];

            // Reutilizar userStats de AdmUserController
            $userStatsResponse = $this->admUserController->userStats();
            $userStatsData = json_decode($userStatsResponse->getContent(), true);
            $userStats = [
                'total' => $userStatsData['total'],
                'data' => $userStatsData['data']
            ];

            // Reutilizar activeOffersCount de AdmOfferController
            $activeOffersResponse = $this->admOfferController->activeOffersCount();
            $activeOffers = json_decode($activeOffersResponse->getContent(), true)['data'];


            return (new DashboardExport($userStats, $lastSells, $activeOffers))
                ->download('dashboard-stats.xlsx');

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar el dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
