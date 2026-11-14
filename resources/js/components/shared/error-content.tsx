import { Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { msg, t } from '@/lib/i18n';
import { dashboard, login } from '@/routes';

const messages = {
    403: {
        heading: msg('No permission'),
        line: msg("You don't have access to this page."),
    },
    404: {
        heading: msg('Page not found'),
        line: msg("This page doesn't exist or has moved."),
    },
    500: {
        heading: msg('Something went wrong'),
        line: msg("It's on our side. Try again in a moment."),
    },
    503: {
        heading: msg('Back soon'),
        line: msg("We're updating the system. Try again in a few minutes."),
    },
} as const;

export type ErrorStatus = keyof typeof messages;

/**
 * What a failing page says: the status, what happened, what to do, and one
 * button. The backend decides the status; this only words it. A 403 or 404 leads
 * back to the dashboard (or to the login page for someone signed out); a 500 or
 * 503 offers to try again.
 */
export default function ErrorContent({
    status,
    signedIn,
}: {
    status: ErrorStatus;
    signedIn: boolean;
}) {
    const { heading, line } = messages[status];
    const retry = status === 500 || status === 503;

    return (
        <div className="flex flex-col items-center gap-6 text-center">
            <div className="grid gap-2">
                <p className="text-sm text-muted-foreground">{status}</p>
                <Heading title={t(heading)} />
                <p className="text-sm text-muted-foreground">{t(line)}</p>
            </div>

            {retry ? (
                <Button onClick={() => router.reload()}>
                    {t('Try again')}
                </Button>
            ) : (
                <Button asChild>
                    <Link href={signedIn ? dashboard() : login()}>
                        {signedIn ? t('Go to dashboard') : t('Log in')}
                    </Link>
                </Button>
            )}
        </div>
    );
}
