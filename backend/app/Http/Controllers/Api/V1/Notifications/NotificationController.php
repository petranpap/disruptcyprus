<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\CursorRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

/**
 * In-app inbox. Every notification stores the same {type, title, body, url} payload, already in the reader's language.
 */
class NotificationController extends Controller
{
    public const PER_PAGE = 20;

    public function index(CursorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $page = $user->notifications()
            ->reorder()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(self::PER_PAGE, cursor: $request->cursor());

        return response()->json([
            'data' => collect($page->items())->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'general',
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'per_page' => self::PER_PAGE,
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => null,
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => ['count' => $user->unreadNotifications()->count()]]);
    }

    public function markRead(Request $request, string $id): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
