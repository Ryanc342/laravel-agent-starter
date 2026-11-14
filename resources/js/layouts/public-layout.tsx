import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import PublicFooter from '@/components/shared/public-footer';
import PublicHeader, {
    type PublicLink,
} from '@/components/shared/public-header';
import { useToday } from '@/hooks/use-today';

/**
 * The layout of every public page (docs/design/patterns/landing.md): the top
 * bar, the page at a readable width, and the footer. A page sets its links
 * with `Page.layout = { links: [...] }` (only the pages that exist). Signed-in
 * customer pages use it too, with their own account navigation.
 */
export default function PublicLayout({
    links = [],
    children,
}: {
    links?: PublicLink[];
    children: ReactNode;
}) {
    const { auth } = usePage().props;
    const today = useToday();

    return (
        <div className="flex min-h-svh flex-col bg-background text-foreground">
            <PublicHeader links={links} signedIn={auth.user !== null} />
            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-6 md:py-12">
                {children}
            </main>
            <PublicFooter links={links} year={today.year} />
        </div>
    );
}
