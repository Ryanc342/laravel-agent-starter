import type { Auth } from '@/types/auth';
import type {
    AppNotification,
    NotificationSummary,
} from '@/types/notification';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            /** `en` or `zh_CN` (app/Enums/Locale.php). */
            locale: string;
            /** The sentences of the current language, keyed by their English text; empty in English. */
            translations: Record<string, string>;
            /** The choices of both language switches (app/Enums/Locale.php `options()`). */
            locales: { value: string; label: string; short: string }[];
            auth: Auth;
            notificationSummary: NotificationSummary | null;
            latestNotifications?: AppNotification[];
            perPageOptions: number[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
