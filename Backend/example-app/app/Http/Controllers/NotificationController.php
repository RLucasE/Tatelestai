<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Lista las notificaciones del usuario autenticado y la cantidad de no leídas.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(30)
            ->get();

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'data' => NotificationResource::collection($notifications),
        ], 200);
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Notificación no encontrada',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ], 200);
    }

    /**
     * Marca todas las notificaciones pendientes del usuario como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'Todas las notificaciones fueron marcadas como leídas',
            'unread_count' => 0,
        ], 200);
    }
}
