import type { ReactNode } from 'react';

/**
 * The layout of the error pages (403, 404, 500, 503): the message centred on
 * an empty screen, with no sidebar, so it works for a signed-out visitor and
 * when the app is failing.
 */
export default function ErrorLayout({ children }: { children: ReactNode }) {
    return (
        <main className="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">{children}</div>
        </main>
    );
}
