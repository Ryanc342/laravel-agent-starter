import { useLayoutEffect, useRef, useState, type ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/** The container width below which a row of actions stacks: 28rem, the same step as `FormField`'s `@md`. */
const NARROW_WIDTH = 448;

/**
 * Every row of action buttons (docs/design/components.md): at the right end of
 * its row with the main button last, and full width and stacked with the main
 * button on top when its container, the element it sits in, is narrow (a
 * phone, a narrow dialog). Put the buttons in reading order: Cancel and the
 * other actions first, the main one last.
 */
export default function Actions({
    className,
    ...props
}: ComponentProps<'div'>) {
    const ref = useRef<HTMLDivElement>(null);
    const [narrow, setNarrow] = useState(false);

    useLayoutEffect(() => {
        const container = ref.current?.parentElement;

        if (!container || typeof ResizeObserver === 'undefined') {
            return;
        }

        const observer = new ResizeObserver(([entry]) =>
            setNarrow(entry.contentRect.width < NARROW_WIDTH),
        );

        observer.observe(container);

        return () => observer.disconnect();
    }, []);

    return (
        <div
            ref={ref}
            data-slot="actions"
            data-layout={narrow ? 'stacked' : 'row'}
            className={cn(
                'flex gap-2',
                narrow
                    ? 'w-full flex-col-reverse *:w-full [&_[data-slot=button]]:w-full'
                    : 'ml-auto flex-wrap items-center justify-end',
                className,
            )}
            {...props}
        />
    );
}
