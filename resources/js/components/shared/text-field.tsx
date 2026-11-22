import { useId, type ComponentProps } from 'react';
import {
    FieldError,
    FloatingLabel,
    floatingControl,
} from '@/components/shared/form-field';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/**
 * A one-line text field: name, email, number, a code. Use it for any value the
 * user types on one line. Its label is required and floats inside the box; it
 * has no placeholder. `error` is the field's message from the server, shown as
 * FieldError says.
 */
export default function TextField({
    label,
    error,
    id,
    className,
    ...props
}: Omit<ComponentProps<typeof Input>, 'placeholder' | 'size'> & {
    label: string;
    error?: string;
}) {
    const fallbackId = useId();
    const inputId = id ?? fallbackId;

    return (
        <div className={cn('relative', className)}>
            <Input
                id={inputId}
                placeholder=" "
                aria-invalid={error ? true : undefined}
                className={floatingControl}
                {...props}
            />
            <FloatingLabel htmlFor={inputId}>{label}</FloatingLabel>
            <FieldError message={error} />
        </div>
    );
}
