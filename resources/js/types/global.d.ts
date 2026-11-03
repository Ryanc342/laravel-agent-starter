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
            auth: Auth;
            notificationSummary: NotificationSummary | null;
            latestNotifications?: AppNotification[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
