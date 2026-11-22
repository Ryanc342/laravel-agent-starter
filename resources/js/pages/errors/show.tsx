import { Head } from '@inertiajs/react';
import ErrorContent from '@/components/shared/error-content';
import type { ErrorStatus } from '@/components/shared/error-content';

type ErrorPageProps = {
    status: ErrorStatus;
    signedIn: boolean;
};

/** The page for 403, 404, 500 and 503 (bootstrap/app.php renders it). */
export default function ShowError({ status, signedIn }: ErrorPageProps) {
    return (
        <>
            <Head title={String(status)} />
            <ErrorContent status={status} signedIn={signedIn} />
        </>
    );
}
