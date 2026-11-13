import { useState } from 'react';
import ResponsiveDialog, {
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import MonthCalendar, {
    type CalendarEvent,
} from '@/components/shared/month-calendar';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';

/**
 * A month of events by date. An event opens in a dialog with its facts; "+N
 * more" opens the day's events. The month is in the URL (`?month=2026-11`); on
 * a phone the month is a list ordered by date.
 */
export default function CalendarDemo({
    events,
    today,
    month,
}: {
    events: CalendarEvent[];
    today: string;
    /** The month shown when the URL has none (`YYYY-MM`). */
    month: string;
}) {
    const [event, setEvent] = useState<CalendarEvent | null>(null);
    const [day, setDay] = useState<string | null>(null);

    return (
        <>
            <MonthCalendar
                events={events}
                today={today}
                defaultMonth={month}
                onEvent={setEvent}
                onMore={setDay}
            />

            <ResponsiveDialog
                open={event !== null}
                onOpenChange={(open) => !open && setEvent(null)}
                title={event?.title ?? ''}
            >
                <dl className="grid gap-1 text-sm">
                    <dt className="text-muted-foreground">Date</dt>
                    <dd>{event ? formatDate(event.date) : ''}</dd>
                </dl>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose asChild>
                        <Button variant="outline">Close</Button>
                    </ResponsiveDialogClose>
                </ResponsiveDialogFooter>
            </ResponsiveDialog>

            <ResponsiveDialog
                open={day !== null}
                onOpenChange={(open) => !open && setDay(null)}
                title={day ? formatDate(day) : ''}
            >
                <ul className="divide-y rounded-lg border">
                    {events
                        .filter((item) => item.date === day)
                        .map((item) => (
                            <li key={item.id} className="px-4 py-3 text-sm">
                                {item.title}
                            </li>
                        ))}
                </ul>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose asChild>
                        <Button variant="outline">Close</Button>
                    </ResponsiveDialogClose>
                </ResponsiveDialogFooter>
            </ResponsiveDialog>
        </>
    );
}
