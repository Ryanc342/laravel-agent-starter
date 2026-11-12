import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { cn } from '@/lib/utils';

/** One section of the style overview page, reachable from its menu by `id`. */
export default function StyleSection({
    id,
    title,
    description,
    children,
}: {
    id: string;
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section id={id} className="grid scroll-mt-6 gap-6">
            <Heading variant="small" title={title} description={description} />
            {children}
        </section>
    );
}

/** A labelled group of examples: the label says what they show. */
export function Specimen({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-3">
            <p className="text-sm text-muted-foreground">{label}</p>
            <div className="flex flex-wrap items-center gap-3">{children}</div>
        </div>
    );
}

/**
 * The box a pattern demo sits in, so a demo reads as one screen of its own.
 * `flush` is for a demo that brings its own page padding (the settings layout).
 */
export function DemoFrame({
    flush = false,
    children,
}: {
    flush?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'rounded-lg border bg-background',
                !flush && 'p-4 md:p-6',
            )}
        >
            {children}
        </div>
    );
}
