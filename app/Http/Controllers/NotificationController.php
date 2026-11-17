<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $baseQuery = DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', 'employee_application_update');

        $unreadCount = (clone $baseQuery)
            ->whereNull('read_at')
            ->count();

        $notifications = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(function (object $notification): array {
                try {
                    $data = json_decode(
                        $notification->data,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                } catch (JsonException) {
                    $data = [];
                }

                if (! is_array($data)) {
                    $data = [];
                }

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Application update',
                    'message' => $data['message'] ?? '',
                    'reference_no' => $data['reference_no'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markAsRead(
        Request $request,
        string $notificationId
    ): RedirectResponse {
        $user = $request->user();

        $notification = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', 'employee_application_update')
            ->first();

        abort_unless($notification, 404);

        if ($notification->read_at === null) {
            DB::table('notifications')
                ->where('id', $notificationId)
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey())
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', 'employee_application_update')
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }
}