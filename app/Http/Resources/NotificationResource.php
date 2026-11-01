<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The data contract with resources/js/types/notification.ts. Change both together.
 * Every flag and every can.* calls an existing owner; none writes a condition.
 *
 * @mixin Notification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'message' => $this->message(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_diff' => $this->created_at?->diffForHumans(),
            'is_unread' => $this->isUnread(),
            'can' => [
                'mark_read' => (bool) $user?->can('markRead', $this->resource) && $this->canBeMarkedRead(),
            ],
        ];
    }
}
