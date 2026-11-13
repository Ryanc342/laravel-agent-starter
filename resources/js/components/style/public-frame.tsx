import type { ReactNode } from 'react';
import PublicFooter from '@/components/shared/public-footer';
import PublicHeader, {
    type PublicLink,
} from '@/components/shared/public-header';

/**
 * A public page as the demos draw it: the public layout's top bar and footer
 * around the page's own content, in the demo's frame. The real layout is
 * `layouts/public-layout.tsx`; this takes `signedIn` as a prop because a demo
 * shows both states and the style page has no customer.
 */
export default function PublicFrame({
    links,
    signedIn = false,
    children,
}: {
    links: PublicLink[];
    signedIn?: boolean;
    children: ReactNode;
}) {
    return (
        <div className="overflow-hidden rounded-lg border bg-background">
            <PublicHeader links={links} signedIn={signedIn} />
            <div className="mx-auto w-full max-w-5xl px-4 py-8 md:px-6 md:py-12">
                {children}
            </div>
            <PublicFooter links={links} year={2026} />
        </div>
    );
}
