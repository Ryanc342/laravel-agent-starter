export type TimelineEvent = {
    id: string | number;
    /** Who did it, as the backend sends it. */
    actor: string;
    /** What they did ("submitted", "approved"). */
    action: string;
    /** When, already formatted by the backend. */
    when: string;
    /** A reason or comment under the row (a rejection's reason). */
    note?: string | null;
};

/**
 * Who did what and when, oldest first: one row per event on a thin line. The
 * events come from the backend; nothing here decides what happened.
 */
export default function Timeline({ events }: { events: TimelineEvent[] }) {
    return (
        <ol className="grid">
            {events.map((event, index) => (
                <li
                    key={event.id}
                    className="relative flex gap-3 pb-6 last:pb-0"
                >
                    {index < events.length - 1 ? (
                        <span
                            aria-hidden="true"
                            className="absolute top-3 bottom-0 left-1 w-px bg-border"
                        />
                    ) : null}
                    <span
                        aria-hidden="true"
                        className="relative mt-1.5 size-2 shrink-0 rounded-full bg-muted-foreground"
                    />
                    <div className="grid min-w-0 gap-1">
                        <p className="text-sm break-words">
                            <span className="font-medium">{event.actor}</span>{' '}
                            {event.action}
                            <span className="text-muted-foreground">
                                {' · '}
                                {event.when}
                            </span>
                        </p>
                        {event.note ? (
                            <p className="text-sm break-words text-muted-foreground">
                                {event.note}
                            </p>
                        ) : null}
                    </div>
                </li>
            ))}
        </ol>
    );
}
