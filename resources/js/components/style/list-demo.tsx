import Heading from '@/components/heading';
import type { Choice } from '@/components/shared/column-menu';
import ListToolbar from '@/components/shared/list-toolbar';
import Pagination from '@/components/shared/pagination';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatMoney } from '@/lib/format';
import type { ResourceCollection } from '@/types/resource';
import { t } from '@/lib/i18n';
import Actions from '@/components/shared/actions';

function demoColumns(statusChoices: Choice[]): TableColumn<ExampleTask>[] {
    return [
        {
            header: t('Title'),
            className: 'max-w-80 truncate font-medium',
            menu: { sortKey: 'title', search: { param: 'search' } },
            cell: (task) => (
                <a
                    href="#detail"
                    className="underline-offset-4 hover:underline active:text-primary-pressed"
                >
                    {task.title}
                </a>
            ),
        },
        {
            header: t('Status'),
            hideLabelOnCard: true,
            menu: {
                sortKey: 'status',
                options: { param: 'status', choices: statusChoices },
            },
            cell: (task) => (
                <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">{task.status}</Badge>
                    {task.overdue && (
                        <Badge variant="destructive">{t('Overdue')}</Badge>
                    )}
                </div>
            ),
        },
        {
            header: t('Amount'),
            menu: {
                sortKey: 'amount',
                range: {
                    fromParam: 'amount_from',
                    toParam: 'amount_to',
                    type: 'number',
                },
            },
            cell: (task) => formatMoney(task.amount),
        },
        {
            header: t('Due date'),
            menu: {
                sortKey: 'due_on',
                range: {
                    fromParam: 'due_from',
                    toParam: 'due_to',
                    type: 'date',
                },
            },
            cell: (task) =>
                task.due_on ? formatDate(task.due_on) : t('No due date'),
        },
    ];
}

/**
 * A list of records: header with the create button, the search box (and on a
 * phone the "Sort & filter" button), the table with its column menus, the
 * pages. The example data does not filter; the menus change the address only.
 */
export default function ListDemo({
    tasks,
    pagination,
    statusChoices,
}: {
    tasks: ExampleTask[];
    pagination: ResourceCollection<ExampleTask>;
    statusChoices: Choice[];
}) {
    const columns = demoColumns(statusChoices);

    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title={t('Tasks')} />
                <Actions>
                    <Button>{t('Add task')}</Button>
                </Actions>
            </div>

            <div className="flex flex-col gap-4">
                <ListToolbar
                    columns={columns}
                    searchLabel={t('Search tasks')}
                />
                <ResponsiveTable
                    columns={columns}
                    rows={tasks}
                    getRowKey={(task) => task.id}
                    emptyMessage={t('You have no tasks yet.')}
                />
            </div>
            <Pagination collection={pagination} />
        </div>
    );
}
