import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { inputVariants } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { parseDateValue, toDateValue } from '@/lib/date-value';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type Props = {
    /** The id the label points at (`FormField`'s `htmlFor`). */
    id: string;
    /** The form field name. Set it inside a `<Form>` so the value is sent as `YYYY-MM-DD`. */
    name?: string;
    /** `YYYY-MM-DD`, or '' for no date. Pass it to control the field. */
    value?: string;
    defaultValue?: string;
    onChange?: (value: string) => void;
    size?: 'default' | 'sm';
    placeholder?: string;
    disabled?: boolean;
    className?: string;
};

/**
 * The one date field of the app, the same in every browser: a button that
 * shows the date and opens a month calendar. It reads and writes `YYYY-MM-DD`,
 * so URLs and requests are as they were with a native date input. A picked day
 * closes the calendar; "Clear" empties the field. Errors mark it through
 * `aria-invalid`, like an `Input` (`FormField` sets it).
 */
export default function DatePicker({
    id,
    name,
    value,
    defaultValue = '',
    onChange,
    size = 'default',
    placeholder = 'Pick a date',
    disabled,
    className,
}: Props) {
    const [open, setOpen] = useState(false);
    const [inner, setInner] = useState(defaultValue);
    const current = value ?? inner;
    const date = parseDateValue(current);

    const change = (next: string) => {
        setInner(next);
        onChange?.(next);
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    id={id}
                    type="button"
                    data-slot="date-picker"
                    data-size={size}
                    disabled={disabled}
                    className={cn(
                        inputVariants({ size }),
                        'items-center justify-between gap-2 text-left',
                        !date && 'text-muted-foreground',
                        className,
                    )}
                >
                    <span className="truncate">
                        {date ? formatDate(current) : placeholder}
                    </span>
                    <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
                </button>
            </PopoverTrigger>
            {name ? <input type="hidden" name={name} value={current} /> : null}
            <PopoverContent align="start" className="w-auto p-0">
                <Calendar
                    mode="single"
                    required
                    selected={date}
                    defaultMonth={date}
                    onSelect={(picked) => change(toDateValue(picked))}
                />
                {date ? (
                    <div className="border-t p-1">
                        <Button
                            type="button"
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
    );
}
