import { useEffect, type ReactNode } from 'react';
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
    /** A link beside the label, at its far end ("Forgot your password?"). The field is stacked then. */
    labelAction?: ReactNode;
    className?: string;
    children: ReactNode;
};

/**
 * One field of a form: the label on the left of the input on desktop and above
 * it on a phone. An error is not shown under the input: it is a toast, the
 * input gets a red border (`aria-invalid`, which the inputs style) and the
 * first field with an error is focused (`useValidationErrorToasts`). Screen
 * readers still get the message.
 */
export default function FormField({
    label,
    htmlFor,
    error,
    hideLabel = false,
    stacked = false,
    labelAction,
    className,
    children,
}: Props) {
    const isMobile = useIsMobile();

    const invalid = Boolean(error);

    useEffect(() => {
        const input = document.getElementById(htmlFor);

        if (invalid) {
            input?.setAttribute('aria-invalid', 'true');
        } else {
            input?.removeAttribute('aria-invalid');
        }
    }, [invalid, htmlFor]);

    const row = !stacked && !hideLabel && !isMobile && !labelAction;

    const labelElement = (
        <Label
            htmlFor={htmlFor}
            className={cn(hideLabel && 'sr-only', row && 'col-span-1 pt-2.5')}
        >
            {label}
        </Label>
    );

    return (
        <div
            data-layout={row ? 'row' : 'stacked'}
            className={cn(
                row ? 'grid grid-cols-3 items-start gap-x-4' : 'grid gap-2',
                className,
            )}
        >
            {labelAction ? (
                <div className="flex items-center justify-between gap-2">
                    {labelElement}
                    {labelAction}
                </div>
            ) : (
                labelElement
            )}
            <div className={cn('grid gap-2', row && 'col-span-2')}>
                {children}
                <InputError message={error} className="sr-only" />
            </div>
        </div>
    );
}
