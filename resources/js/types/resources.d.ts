declare namespace App {
    namespace Http {
        namespace Resources {
            export type NotificationCan = {
                mark_read: boolean;
            };
            export type NotificationResource = {
                id: string;
                message: string;
                created_at_diff: string | null;
                is_unread: boolean;
                can: App.Http.Resources.NotificationCan;
            };
            export type NotificationSummary = {
                unread: number;
                can: App.Http.Resources.NotificationSummaryCan;
            };
            export type NotificationSummaryCan = {
                mark_all_read: boolean;
            };
            export type PasskeyResource = {
                id: string;
                name: string;
                authenticator: string | null;
                created_at_diff: string;
                last_used_at_diff: string | null;
            };
            export type SharedAuth = {
                user: App.Http.Resources.UserResource | null;
            };
            export type Today = {
                date: string;
                year: number;
            };
            export type UserResource = {
                id: string;
                name: string;
                email: string;
                initials: string;
            };
        }
    }
}
