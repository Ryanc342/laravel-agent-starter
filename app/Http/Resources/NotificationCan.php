<?php

namespace App\Http\Resources;

/**
 * Which buttons a notification row shows (NotificationResource fills it).
 */
final class NotificationCan extends ResourceShape
{
    public function __construct(
        public bool $mark_read,
    ) {}
}
