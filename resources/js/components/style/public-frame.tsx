import type { ReactNode } from 'react';
import PublicFooter from '@/components/shared/public-footer';
import PublicHeader, {
    type PublicLink,
} from '@/components/shared/public-header';
import { useToday } from '@/hooks/use-today';

/**
 * A public page as the demos draw it: the public layout's top bar and footer
 * around the page's own content, in the demo's frame. The real layout is
 * `layouts/public-layout.tsx`; this takes `signedIn` as a prop because a demo
 * shows both states and the style page has no customer. `name` names the
 * frame's page links and footer links, so the frames on one page are told apart.
 */
export default function PublicFrame({
    name,
    links,
    signedIn = false,
    children,
}: {
    name: string;
    links: PublicLink[];
    signedIn?: boolean;
    children: ReactNode;
}) {
    const today = useToday();

    return (
        <div className="overflow-hidden rounded-lg bg-muted/40">
            <PublicHeader
                links={links}
                signedIn={signedIn}
                label={`${name} pages`}
            />
            <div className="mx-auto grid w-full max-w-5xl content-start gap-12 px-4 py-8 md:gap-16 md:px-6 md:py-12">
                {children}
            </div>
            <PublicFooter
                links={links}
                year={today.year}
                label={`${name} footer`}
            />
        </div>
    );
}
