<?php

namespace App\Http\Resources;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * `notificationSummary` in every page's shared data: what the notification
 * bell and the notifications page show for the signed-in user.
 */
#[TypeScript]
final class NotificationSummary extends ResourceShape
{
    public function __construct(
        public int $unread,
        public NotificationSummaryCan $can,
    ) {}
}
