import { CalendarIcon } from 'lucide-react';
import { useId, useState } from 'react';
import {
    FieldError,
    FloatingLabel,
    floatingControl,
} from '@/components/shared/form-field';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { inputVariants } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { parseDateValue, toDateValue } from '@/lib/date-value';
import { useToday } from '@/hooks/use-today';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    id?: string;
    /** The form field name. Set it inside a `<Form>` so the value is sent as `YYYY-MM-DD`. */
    name?: string;
    /** `YYYY-MM-DD`, or '' for no date. Pass it to control the field. */
    value?: string;
    defaultValue?: string;
    onChange?: (value: string) => void;
    error?: string;
    disabled?: boolean;
    className?: string;
};

/**
 * The one date field of the app, the same in every browser: a box that shows
 * the date and opens a month calendar. Use it for every date the user picks.
 * It reads and writes `YYYY-MM-DD`, so URLs and requests are as they were with
 * a native date input. A picked day closes the calendar; "Clear" empties the
 * field. Its label is required and floats inside the box; `error` works as in
 * TextField.
 */
export default function DatePicker({
    label,
    id,
    name,
    value,
    defaultValue = '',
    onChange,
    error,
    disabled,
    className,
}: Props) {
    const fallbackId = useId();
    const buttonId = id ?? fallbackId;
    const [open, setOpen] = useState(false);
    const today = useToday();
    const [inner, setInner] = useState(defaultValue);
    const current = value ?? inner;
    const date = parseDateValue(current);

    const change = (next: string) => {
        setInner(next);
        onChange?.(next);
        setOpen(false);
    };

    return (
        <div className={cn('relative', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <button
                        id={buttonId}
                        type="button"
                        data-slot="date-picker"
                        data-empty={date ? undefined : ''}
                        disabled={disabled}
                        aria-invalid={error ? true : undefined}
                        className={cn(
                            inputVariants(),
                            floatingControl,
                            'items-center justify-between gap-2 text-left',
                        )}
                    >
                        <span className="truncate">
                            {date ? formatDate(current) : ''}
                        </span>
                        <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
                    </button>
                </PopoverTrigger>
                <FloatingLabel htmlFor={buttonId}>{label}</FloatingLabel>
                {name ? (
                    <input type="hidden" name={name} value={current} />
                ) : null}
                <PopoverContent align="start" className="w-auto p-0">
                    <Calendar
                        mode="single"
                        required
                        selected={date}
                        defaultMonth={date}
                        today={parseDateValue(today.date)}
                        onSelect={(picked) => change(toDateValue(picked))}
                    />
                    {date ? (
                        <div className="border-t p-1">
                            <Button
                                variant="ghost"
                                className="w-full"
                                onClick={() => change('')}
                            >
                                Clear
                            </Button>
                        </div>
                    ) : null}
                </PopoverContent>
            </Popover>
            <FieldError message={error} />
        </div>
    );
}
