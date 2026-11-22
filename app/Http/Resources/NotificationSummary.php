<?php

namespace App\Http\Resources;

/**
 * `notificationSummary` in every page's shared data: what the notification
 * bell and the notifications page show for the signed-in user.
 */
final class NotificationSummary extends ResourceShape
{
    public function __construct(
        public int $unread,
        public NotificationSummaryCan $can,
    ) {}
}
