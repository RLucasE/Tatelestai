<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'event_type' => $data['event_type'] ?? 'info',
            'title' => $data['title'] ?? 'Notificación',
            'message' => $data['message'] ?? '',
            'sell_id' => $data['sell_id'] ?? null,
            'offer_id' => $data['offer_id'] ?? null,
            'establishment_id' => $data['establishment_id'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'meta' => $data['meta'] ?? [],
            'read_at' => $this->read_at?->toIso8601String(),
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
