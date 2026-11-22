import { Link, type InertiaLinkProps } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

/**
 * The last line of a public page: one short sentence and one button, under a
 * line. Use it to end a home or product page with the next step.
 */
export default function ClosingCall({
    text,
    action,
}: {
    text: string;
    action: { label: string; href: InertiaLinkProps['href'] };
}) {
    return (
        <section className="flex flex-col gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-base font-medium">{text}</p>
            <Button asChild>
                <Link href={action.href}>{action.label}</Link>
            </Button>
        </section>
    );
}
