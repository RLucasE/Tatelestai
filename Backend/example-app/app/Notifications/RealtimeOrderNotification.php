<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class RealtimeOrderNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $eventType,
        public string $title,
        public string $message,
        public ?int $sellId = null,
        public ?int $offerId = null,
        public ?int $establishmentId = null,
        public ?string $actionUrl = null,
        public array $meta = []
    ) {}

    /**
     * Canales de notificación: persistencia en BD y emisión WebSocket inmediata.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Representación de la notificación para la base de datos (PostgreSQL).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'sell_id' => $this->sellId,
            'offer_id' => $this->offerId,
            'establishment_id' => $this->establishmentId,
            'action_url' => $this->actionUrl,
            'meta' => $this->meta,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Representación para Laravel Echo (WebSockets vía Reverb).
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'event_type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'sell_id' => $this->sellId,
            'offer_id' => $this->offerId,
            'establishment_id' => $this->establishmentId,
            'action_url' => $this->actionUrl,
            'meta' => $this->meta,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
