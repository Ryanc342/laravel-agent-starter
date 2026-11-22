import { useId } from 'react';
import {
    FieldError,
    FloatingLabel,
    floatingControl,
} from '@/components/shared/form-field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type SelectOption = { value: string; label: string };

/**
 * A choice of one value from a list: a status, a country, rows per page. Use
 * it whenever the user picks one of several fixed options in a box. The
 * options come from the backend. Its label is required and floats inside the
 * box; `name` sends the value with a form; `error` works as in TextField.
 */
export default function SelectField({
    label,
    options,
    id,
    name,
    value,
    defaultValue,
    onValueChange,
    error,
    disabled,
    className,
}: {
    label: string;
    options: SelectOption[];
    id?: string;
    name?: string;
    value?: string;
    defaultValue?: string;
    onValueChange?: (value: string) => void;
    error?: string;
    disabled?: boolean;
    className?: string;
}) {
    const fallbackId = useId();
    const triggerId = id ?? fallbackId;

    return (
        <div className={cn('relative', className)}>
            <Select
                name={name}
                value={value}
                defaultValue={defaultValue}
                onValueChange={onValueChange}
                disabled={disabled}
            >
                <SelectTrigger
                    id={triggerId}
                    aria-invalid={error ? true : undefined}
                    className={cn(
                        floatingControl,
                        'w-full data-[size=default]:h-12',
                    )}
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <FloatingLabel htmlFor={triggerId}>{label}</FloatingLabel>
            <FieldError message={error} />
        </div>
    );
}
