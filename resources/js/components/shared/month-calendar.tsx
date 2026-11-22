import { ChevronLeft, ChevronRight } from 'lucide-react';
import EmptyState from '@/components/shared/empty-state';
import Heading from '@/components/heading';
import IconButton from '@/components/shared/icon-button';
import { Button } from '@/components/ui/button';
import { useIsMobile } from '@/hooks/use-mobile';
import { useListQuery } from '@/hooks/use-list-query';
import { useToday } from '@/hooks/use-today';
import { parseDateValue } from '@/lib/date-value';
import { formatDate, intlLocale } from '@/lib/format';
import { listParam } from '@/lib/list-query';
import {
    monthGrid,
    monthTitle,
    monthValue,
    parseMonth,
    shiftMonth,
    weekdayNames,
} from '@/lib/month-grid';
import { cn } from '@/lib/utils';

export type CalendarEvent = {
    id: string | number;
    date: string;
    title: string;
};

const visibleEvents = 3;

/**
 * A month view only. The month lives in the URL (`?month=2026-11`); the header
 * is the month's name with "Previous month", "Next month" and "Today". Each
 * day lists its events as one-line buttons ("+2 more" past three); an event
 * and "+N more" are handed to the page, which opens them in a dialog. On a
 * phone the month is a list ordered by date, days with no events left out. The
 * backend sends the month's events; the grid is built with `Intl` and `Date`.
 */
export default function MonthCalendar({
    events,
    onEvent,
    onMore,
    weekStartsOn = 0,
    today: todayProp,
    defaultMonth,
}: {
    events: CalendarEvent[];
    onEvent: (event: CalendarEvent) => void;
    onMore: (date: string) => void;
    weekStartsOn?: number;
    /** The day that counts as today, `YYYY-MM-DD`; the backend's by default. */
    today?: string;
    /** The month shown when the URL has none (`YYYY-MM`); this month by default. */
    defaultMonth?: string;
}) {
    const isMobile = useIsMobile();
    const shared = useToday();
    const today = todayProp ?? shared.date;
    const { url, change } = useListQuery();
    const todayDate = parseDateValue(today);

    if (todayDate === undefined) {
        throw new Error(`Today "${today}" is not a date (YYYY-MM-DD).`);
    }

    const month = monthValue(
        parseMonth(listParam(url, 'month') || defaultMonth || '', todayDate),
    );
    const byDate = new Map<string, CalendarEvent[]>();

    for (const event of events) {
        byDate.set(event.date, [...(byDate.get(event.date) ?? []), event]);
    }

    const nav = (label: string, to: string, icon: React.ReactNode) => (
        <IconButton
            label={label}
            variant="outline"
            onClick={() => change({ month: to })}
        >
            {icon}
        </IconButton>
    );

    const header = (
        <div className="flex flex-wrap items-center justify-between gap-2">
            <Heading title={monthTitle(month, intlLocale())} />
            <div className="flex items-center gap-2">
                {nav('Previous month', shiftMonth(month, -1), <ChevronLeft />)}
                {nav('Next month', shiftMonth(month, 1), <ChevronRight />)}
                <Button
                    variant="outline"
                    onClick={() => change({ month: today.slice(0, 7) })}
                >
                    Today
                </Button>
            </div>
        </div>
    );

    if (isMobile) {
        const days = [...byDate.keys()]
            .filter((date) => date.startsWith(month))
            .sort();

        return (
            <div className="grid gap-4">
                {header}
                {days.length === 0 ? (
                    <div className="rounded-lg border px-4 py-8">
                        <EmptyState message="Nothing this month." />
                    </div>
                ) : (
                    days.map((date) => (
                        <section key={date} className="grid gap-2">
                            <Heading variant="small" title={formatDate(date)} />
                            <ul className="divide-y rounded-lg border">
                                {(byDate.get(date) ?? []).map((event) => (
                                    <li key={event.id}>
                                        <button
                                            type="button"
                                            onClick={() => onEvent(event)}
                                            className="w-full px-4 py-3 text-left text-sm break-words transition-colors hover:bg-accent active:bg-accent-pressed"
                                        >
                                            {event.title}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))
                )}
            </div>
        );
    }

    return (
        <div className="grid gap-4">
            {header}
            <div className="overflow-hidden rounded-lg border">
                <div className="grid grid-cols-7 border-b bg-muted text-center text-xs text-muted-foreground">
                    {weekdayNames(intlLocale(), weekStartsOn).map((name) => (
                        <div key={name} className="py-2">
                            {name}
                        </div>
                    ))}
                </div>
                {monthGrid(month, weekStartsOn).map((week) => (
                    <div
                        key={week[0].date}
                        className="grid grid-cols-7 border-b last:border-b-0"
                    >
                        {week.map((day) => {
                            const dayEvents = byDate.get(day.date) ?? [];

                            return (
                                <div
                                    key={day.date}
                                    data-date={day.date}
                                    className="grid min-h-28 min-w-0 content-start gap-1 border-r p-1 last:border-r-0"
                                >
                                    <span
                                        className={cn(
                                            'px-1 text-sm tabular-nums',
                                            day.outside &&
                                                'text-muted-foreground',
                                            day.date === today &&
                                                'font-medium text-primary',
                                        )}
                                        aria-current={
                                            day.date === today
                                                ? 'date'
                                                : undefined
                                        }
                                    >
                                        {day.day}
                                    </span>
                                    {dayEvents
                                        .slice(0, visibleEvents)
                                        .map((event) => (
                                            <button
                                                key={event.id}
                                                type="button"
                                                onClick={() => onEvent(event)}
                                                className="truncate rounded-md bg-muted px-2 py-1 text-left text-xs transition-colors hover:bg-accent active:bg-accent-pressed"
                                            >
                                                {event.title}
                                            </button>
                                        ))}
                                    {dayEvents.length > visibleEvents ? (
                                        <button
                                            type="button"
                                            onClick={() => onMore(day.date)}
                                            className="rounded-md px-2 py-1 text-left text-xs text-muted-foreground transition-colors hover:bg-accent active:bg-accent-pressed"
                                        >
                                            {`+${
                                                dayEvents.length - visibleEvents
                                            } more`}
                                        </button>
                                    ) : null}
                                </div>
                            );
                        })}
                    </div>
                ))}
            </div>
        </div>
    );
}
