import { Head } from '@inertiajs/react';
import ErrorContent from '@/components/shared/error-content';
import type { ErrorStatus } from '@/components/shared/error-content';

type ErrorPageProps = {
    status: ErrorStatus;
    signedIn: boolean;
};

/**
 * The page for 403, 404, 500 and 503 (bootstrap/app.php renders it). It has no
 * sidebar, so it also works for a signed-out visitor and when the app is failing.
 */
export default function ShowError({ status, signedIn }: ErrorPageProps) {
    return (
        <>
            <Head title={String(status)} />

            <div className="flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <ErrorContent status={status} signedIn={signedIn} />
                </div>
            </div>
        </>
    );
}
