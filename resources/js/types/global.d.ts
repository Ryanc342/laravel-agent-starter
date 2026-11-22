// A module, so the declarations below add to React's and Inertia's types.
export {};

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        // The keys every page receives (HandleInertiaRequests::share()); a test
        // checks this list against the backend. The groups inside are in
        // resources/js/types/resources.d.ts, written by hand to match the backend.
        sharedPageProps: {
            name: string;
            signedIn: boolean;
            auth: App.Http.Resources.SharedAuth;
            notificationSummary: App.Http.Resources.NotificationSummary | null;
            latestNotifications?: App.Http.Resources.NotificationResource[];
            today: App.Http.Resources.Today;
            perPageOptions: number[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
