/**
 * Matches app/Http/Resources/NotificationResource.php. Change both together.
 * Named AppNotification so it does not hide the browser's Notification type.
 */
export type AppNotification = {
    id: string;
    message: string;
    created_at: string | null;
    created_at_diff: string | null;
    is_unread: boolean;
    can: {
        mark_read: boolean;
    };
};

/**
 * Shared with every page by app/Http/Middleware/HandleInertiaRequests.php
 * (notificationSummary). Null for guests.
 */
export type NotificationSummary = {
    unread: number;
    can: {
        mark_all_read: boolean;
    };
};
