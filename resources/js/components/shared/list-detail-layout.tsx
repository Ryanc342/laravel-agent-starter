import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

/**
 * A list with the selected record's detail beside it. On a computer the list
 * takes about a third of the width and the detail the rest. On a phone the
 * list comes first and a selected record opens as its own screen with a back
 * link at the top left. Which record is selected is decided by the URL (the
 * page passes `selected`); with nothing selected the page shows the first.
 */
export default function ListDetailLayout({
    list,
    detail,
    selected,
    backHref,
    backLabel,
}: {
    list: ReactNode;
    detail: ReactNode;
    /** A record is selected: on a phone the detail replaces the list. */
    selected: boolean;
    backHref: string;
    backLabel: string;
}) {
    const isMobile = useIsMobile();

    if (isMobile) {
        return selected ? (
            <div className="grid gap-4">
                <Link
                    href={backHref}
                    className="flex w-fit items-center gap-1 text-sm text-primary transition-colors active:text-primary-pressed"
                >
                    <ChevronLeft className="size-4" />
                    {backLabel}
                </Link>
                {detail}
            </div>
        ) : (
            list
        );
    }

    return (
        <div className="grid gap-6 md:grid-cols-3">
            <div className="min-w-0">{list}</div>
            <div className="min-w-0 md:col-span-2">{detail}</div>
        </div>
    );
}

/** A row of the list: a link, so the keyboard and screen readers work; the selected row has the selected colour. */
export function ListDetailRow({
    href,
    selected,
    children,
}: {
    href: string;
    selected: boolean;
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            preserveScroll
            aria-current={selected ? 'true' : undefined}
            className={cn(
                'grid gap-1 border-b p-4 transition-colors last:border-b-0 hover:bg-accent active:bg-accent-pressed',
                selected && 'bg-accent-pressed',
            )}
        >
            {children}
        </Link>
    );
}
