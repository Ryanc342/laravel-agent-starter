import { useEffect, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    /** The id of the input inside, so the label and the input belong together. */
    htmlFor: string;
    error?: string;
    /** Hide the label visually when the layout already says what the field is (a search box). */
    hideLabel?: boolean;
    /** A link beside the label, at its far end ("Forgot your password?"). The label is above the input then. */
    labelAction?: ReactNode;
    className?: string;
    children: ReactNode;
};

/**
 * One field of a form. It decides from its own width, by a container query:
 * the label sits to the left of the input when the form is wide and above it
 * when the form is narrow (a phone, the sign-in column, a narrow dialog). An error is not shown under the input: it is a toast, the
 * input gets a red border (`aria-invalid`, which the inputs style) and the
 * first field with an error is focused (`useValidationErrorToasts`). Screen
 * readers still get the message.
 */
export default function FormField({
    label,
    htmlFor,
    error,
    hideLabel = false,
    labelAction,
    className,
    children,
}: Props) {
    const invalid = Boolean(error);

    useEffect(() => {
        const input = document.getElementById(htmlFor);

        if (invalid) {
            input?.setAttribute('aria-invalid', 'true');
        } else {
            input?.removeAttribute('aria-invalid');
        }
    }, [invalid, htmlFor]);

    const beside = !hideLabel && !labelAction;

    const labelElement = (
        <Label
            htmlFor={htmlFor}
            className={cn(
                hideLabel && 'sr-only',
                beside && '@md:col-span-1 @md:pt-2.5',
            )}
        >
            {label}
        </Label>
    );

    return (
        <div className={cn('@container', className)}>
            <div
                className={cn(
                    'grid gap-2',
                    beside && '@md:grid-cols-3 @md:items-start @md:gap-x-4',
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
                <div className={cn('grid gap-2', beside && '@md:col-span-2')}>
                    {children}
                    <InputError message={error} className="sr-only" />
                </div>
            </div>
        </div>
    );
}
