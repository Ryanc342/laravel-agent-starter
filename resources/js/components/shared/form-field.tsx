import { useEffect, type ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/**
 * The classes a boxed field's control takes so its label floats: it is the
 * label's `peer`, it is taller than a plain input so the label fits above the
 * value, and its blank placeholder lets the label rest inside the box while the
 * field is empty (the `field-empty` variant in app.css).
 */
export const floatingControl = 'peer h-12 pt-5 pb-1';

/** The same for a box of several lines (TextareaField). */
export const floatingMultilineControl = 'peer pt-6';

/**
 * The label of a boxed field (TextField, PasswordField, TextareaField,
 * SelectField, DatePicker, SearchBox): it sits inside the box, above the value,
 * and rests in the middle of the box while the field is empty and not focused.
 * It comes right after the control, which carries `floatingControl`.
 */
export function FloatingLabel({
    htmlFor,
    multiline = false,
    children,
}: {
    htmlFor: string;
    multiline?: boolean;
    children: string;
}) {
    return (
        <label
            htmlFor={htmlFor}
            className={cn(
                'pointer-events-none absolute top-1.5 left-3.5 text-xs text-muted-foreground transition-all',
                multiline
                    ? 'field-empty:top-3 field-empty:text-base md:field-empty:text-sm'
                    : 'field-empty:top-1/2 field-empty:-translate-y-1/2 field-empty:text-base md:field-empty:text-sm',
            )}
        >
            {children}
        </label>
    );
}

/**
 * A boxed field's error: never shown under the box. The box gets a red border
 * (its control takes `aria-invalid`), the toast and the focus come from
 * useValidationErrorToasts, and screen readers get this text.
 */
export function FieldError({ message }: { message?: string }) {
    return <InputError message={message} className="sr-only" />;
}

type Props = {
    label: string;
    /** The id of the control inside, so the label and the control belong together. */
    htmlFor: string;
    error?: string;
    /** Hide the label visually when the screen already says what the field is (the heading above a one-time code). */
    hideLabel?: boolean;
    className?: string;
    children: ReactNode;
};

/**
 * A field without a box of its own: a one-time code, a file picker. The label
 * sits above the control. A boxed field (text, password, several lines, a
 * choice from a list, a date, a search) uses its own component instead, whose
 * label floats inside the box. An error marks the control `aria-invalid` and is
 * read to screen readers, as FieldError says.
 */
export default function FormField({
    label,
    htmlFor,
    error,
    hideLabel = false,
    className,
    children,
}: Props) {
    const invalid = Boolean(error);

    useEffect(() => {
        const control = document.getElementById(htmlFor);

        if (invalid) {
            control?.setAttribute('aria-invalid', 'true');
        } else {
            control?.removeAttribute('aria-invalid');
        }
    }, [invalid, htmlFor]);

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={htmlFor} className={cn(hideLabel && 'sr-only')}>
                {label}
            </Label>
            {children}
            <FieldError message={error} />
        </div>
    );
}
