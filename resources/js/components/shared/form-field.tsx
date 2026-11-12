import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    /** The id of the input inside, so the label and the input belong together. */
    htmlFor: string;
    error?: string;
    /** Hide the label visually when the layout already says what the field is (a search box). */
    hideLabel?: boolean;
    /** Keep the label above the input on desktop too: for narrow columns such as the sign-in pages. */
    stacked?: boolean;
    className?: string;
    children: ReactNode;
};

/**
 * One field of a form: the label on the left of the input on desktop and above
 * it on a phone, the error under the input.
 */
export default function FormField({
    label,
    htmlFor,
    error,
    hideLabel = false,
    stacked = false,
    className,
    children,
}: Props) {
    const isMobile = useIsMobile();
    const row = !stacked && !hideLabel && !isMobile;

    return (
        <div
            data-layout={row ? 'row' : 'stacked'}
            className={cn(
                row ? 'grid grid-cols-3 items-start gap-x-4' : 'grid gap-2',
                className,
            )}
        >
            <Label
                htmlFor={htmlFor}
                className={cn(
                    hideLabel && 'sr-only',
                    row && 'col-span-1 pt-2.5',
                )}
            >
                {label}
            </Label>
            <div className={cn('grid gap-2', row && 'col-span-2')}>
                {children}
                <InputError message={error} />
            </div>
        </div>
    );
}
