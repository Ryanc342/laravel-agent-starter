<?php

namespace App\Http\Resources;

/**
 * Which notification buttons the bell and the notifications page show (HandleInertiaRequests fills it).
 */
final class NotificationSummaryCan extends ResourceShape
{
    public function __construct(
        public bool $mark_all_read,
    ) {}
}
