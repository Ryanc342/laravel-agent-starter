import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type Detail = { label: string; value: ReactNode };

/**
 * The facts of one record, label and value: the due date, who asked, the
 * delivery address. Use it wherever a page shows a record's facts. `stacked`
 * puts each label above its value (a record's page); `beside` puts the label
 * at the start and the value at the end of one line (a short summary, the
 * review step of a checkout). `columns` sets two facts side by side from a
 * tablet up. A fact with no value says so in a few words ("No due date").
 */
export default function DetailList({
    details,
    layout = 'stacked',
    columns = 1,
}: {
    details: Detail[];
    layout?: 'stacked' | 'beside';
    columns?: 1 | 2;
}) {
    return (
        <dl
            className={cn(
                'grid max-w-xl gap-4 text-sm',
                columns === 2 && 'max-w-none sm:grid-cols-2',
                layout === 'beside' && 'gap-3',
            )}
        >
            {details.map((detail) => (
                <div
                    key={detail.label}
                    className={cn(
                        layout === 'stacked'
                            ? 'grid gap-1'
                            : 'flex justify-between gap-4',
                    )}
                >
                    <dt className="text-muted-foreground">{detail.label}</dt>
                    <dd
                        className={cn(
                            'min-w-0 break-words',
                            layout === 'beside' && 'text-right',
                        )}
                    >
                        {detail.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
