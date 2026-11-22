import { useId, type ComponentProps } from 'react';
import {
    FieldError,
    FloatingLabel,
    floatingMultilineControl,
} from '@/components/shared/form-field';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

/**
 * A text field of several lines: a message, a note, a description. Its label
 * is required and floats inside the box; it has no placeholder. `error` works
 * as in TextField.
 */
export default function TextareaField({
    label,
    error,
    id,
    className,
    ...props
}: Omit<ComponentProps<typeof Textarea>, 'placeholder'> & {
    label: string;
    error?: string;
}) {
    const fallbackId = useId();
    const textareaId = id ?? fallbackId;

    return (
        <div className={cn('relative', className)}>
            <Textarea
                id={textareaId}
                placeholder=" "
                aria-invalid={error ? true : undefined}
                className={floatingMultilineControl}
                {...props}
            />
            <FloatingLabel htmlFor={textareaId} multiline>
                {label}
            </FloatingLabel>
            <FieldError message={error} />
        </div>
    );
}
