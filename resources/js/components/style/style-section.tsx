import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { cn } from '@/lib/utils';

/** One section of the style overview page, reachable from its menu by `id`. */
export default function StyleSection({
    id,
    title,
    children,
}: {
    id: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <section id={id} className="grid scroll-mt-6 gap-6">
            <Heading variant="small" title={title} />
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
 * One fixed state of a demo (empty, with the server's errors, after a
 * decision): the label says which, and the demo shows it as the page looks
 * then. The amounts, messages and errors come from the demo data; nothing is
 * worked out on the page.
 */
export function DemoState({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    return (
        <div className="grid min-w-0 content-start gap-3">
            <p className="text-sm text-muted-foreground">{label}</p>
            {children}
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
        <div className={cn('rounded-lg bg-muted/40', !flush && 'p-4 md:p-6')}>
            {children}
        </div>
    );
}
