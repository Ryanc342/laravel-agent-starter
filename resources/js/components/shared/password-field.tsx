import { Eye, EyeOff } from 'lucide-react';
import { useId, useState, type ComponentProps } from 'react';
import {
    FieldError,
    FloatingLabel,
    floatingControl,
} from '@/components/shared/form-field';
import IconButton from '@/components/shared/icon-button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/**
 * A password field: the user's current password or a new one. Use it for
 * every password. A button at its end shows or hides what was typed. Its
 * label is required and floats inside the box; `error` works as in TextField.
 */
export default function PasswordField({
    label,
    error,
    id,
    className,
    ...props
}: Omit<ComponentProps<typeof Input>, 'type' | 'placeholder' | 'size'> & {
    label: string;
    error?: string;
}) {
    const fallbackId = useId();
    const inputId = id ?? fallbackId;
    const [shown, setShown] = useState(false);

    return (
        <div className={cn('relative', className)}>
            <Input
                id={inputId}
                type={shown ? 'text' : 'password'}
                size="end-icon"
                placeholder=" "
                aria-invalid={error ? true : undefined}
                className={floatingControl}
                {...props}
            />
            <FloatingLabel htmlFor={inputId}>{label}</FloatingLabel>
            <IconButton
                label={shown ? 'Hide password' : 'Show password'}
                size="icon-sm"
                className="absolute top-1/2 right-1.5 -translate-y-1/2 text-muted-foreground"
                onClick={() => setShown((current) => !current)}
            >
                {shown ? <EyeOff /> : <Eye />}
            </IconButton>
            <FieldError message={error} />
        </div>
    );
}
