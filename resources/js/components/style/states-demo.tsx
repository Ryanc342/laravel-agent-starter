import Heading from '@/components/heading';
import EmptyState from '@/components/shared/empty-state';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import { Button } from '@/components/ui/button';
import type { ReactNode } from 'react';
import { t } from '@/lib/i18n';

type Row = { id: number; title: string; status: string };

const columns: TableColumn<Row>[] = [
    { header: t('Title'), cell: (row) => row.title },
    { header: t('Status'), cell: (row) => row.status },
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
            emptyMessage={t('You have no :subject yet.', { subject })}
            loading={loading}
            error={
                failed
                    ? {
                          message: t('Could not load your :subject.', {
                              subject,
                          }),
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
            <Region title={t('Empty, in a table')} framed={false}>
                <TableState subject={subject} />
            </Region>
            <Region title={t('Loading, in a table')} framed={false}>
                <TableState subject={subject} loading />
            </Region>
            <Region title={t('Failed to load, in a table')} framed={false}>
                <TableState subject={subject} failed />
            </Region>
            <Region title={t('Empty, with the create button')}>
                <EmptyState
                    message={t('You have no :subject yet.', { subject })}
                    action={<Button>{t('Add task')}</Button>}
                />
            </Region>
        </div>
    );
}
