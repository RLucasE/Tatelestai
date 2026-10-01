<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'establishment' => $this['establishment'],
            'summary' => $this['summary'],
            'live_stock' => $this['live_stock'],
            'today_pickups' => $this['today_pickups'],
        ];
    }
}
