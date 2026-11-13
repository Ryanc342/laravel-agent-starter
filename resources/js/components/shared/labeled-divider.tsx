import type { ReactNode } from 'react';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

/**
 * A line across the width with a few words in the middle ("Or continue with
 * email", "or, enter the code manually"): the one divider of the sign-in and
 * security steps.
 */
export default function LabeledDivider({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'relative flex w-full items-center justify-center',
                className,
            )}
        >
            <Separator className="absolute inset-x-0" />
            <span className="relative bg-background px-2 py-1 text-sm text-muted-foreground">
                {children}
            </span>
        </div>
    );
}
