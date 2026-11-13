import Heading from '@/components/heading';
import EmptyState from '@/components/shared/empty-state';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import { Button } from '@/components/ui/button';
import type { ReactNode } from 'react';

type Row = { id: number; title: string; status: string };

const columns: TableColumn<Row>[] = [
    { header: 'Title', cell: (row) => row.title },
    { header: 'Status', cell: (row) => row.status },
];

function Region({
    title,
    framed = true,
    children,
}: {
    title: string;
    framed?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={
                framed
                    ? 'grid content-start gap-4 rounded-lg border p-4'
                    : 'grid content-start gap-4'
            }
        >
            <Heading variant="small" title={title} />
            {children}
        </div>
    );
}

function TableState({
    subject,
    loading,
    failed,
}: {
    subject: string;
    loading?: boolean;
    failed?: boolean;
}) {
    return (
        <ResponsiveTable
            columns={columns}
            rows={[]}
            getRowKey={(row) => row.id}
            emptyMessage={`You have no ${subject} yet.`}
            loading={loading}
            error={
                failed
                    ? {
                          message: `Could not load your ${subject}.`,
                          onRetry: () => undefined,
                      }
                    : undefined
            }
        />
    );
}

/**
 * A region with nothing in it, still loading, or that failed to load. Inside a
 * table or box the state is centred under the header row. `subject` is what the
 * region lists.
 */
export default function StatesDemo({ subject }: { subject: string }) {
    return (
        <div className="grid gap-6 md:grid-cols-2">
            <Region title="Empty, in a table" framed={false}>
                <TableState subject={subject} />
            </Region>
            <Region title="Loading, in a table" framed={false}>
                <TableState subject={subject} loading />
            </Region>
            <Region title="Failed to load, in a table" framed={false}>
                <TableState subject={subject} failed />
            </Region>
            <Region title="Empty, with the create button">
                <EmptyState
                    message={`You have no ${subject} yet.`}
                    action={<Button>Add task</Button>}
                />
            </Region>
        </div>
    );
}
