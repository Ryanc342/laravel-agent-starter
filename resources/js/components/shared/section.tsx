import type { ReactNode } from 'react';
import Heading from '@/components/heading';

/**
 * One part of a page under its small heading: "Profile", "Delete account",
 * "Passkeys". Use it for each part of a settings page and for any page made
 * of several titled parts.
 */
export default function Section({
    title,
    children,
}: {
    title: string;
    children: ReactNode;
}) {
    return (
        <section className="space-y-6">
            <Heading variant="small" title={title} />
            {children}
        </section>
    );
}
