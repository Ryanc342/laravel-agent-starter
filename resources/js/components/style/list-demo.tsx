import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import Pagination from '@/components/shared/pagination';
import ResponsiveTable from '@/components/shared/responsive-table';
import type { TableColumn } from '@/components/shared/responsive-table';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/lib/format';
import type { ResourceCollection } from '@/types/resource';

const columns: TableColumn<ExampleTask>[] = [
    {
        header: 'Title',
        className: 'max-w-80 truncate font-medium',
        cell: (task) => (
            <a href="#detail" className="underline-offset-4 hover:underline">
                {task.title}
            </a>
        ),
    },
    {
        header: 'Status',
        hideLabelOnCard: true,
        cell: (task) => (
            <div className="flex flex-wrap gap-2">
                <Badge variant="secondary">{task.status}</Badge>
                {task.overdue && <Badge variant="destructive">Overdue</Badge>}
            </div>
        ),
    },
    {
        header: 'Due date',
        cell: (task) => (task.due_on ? formatDate(task.due_on) : 'No due date'),
    },
];

/** A list of records: header with the create button, one filter row, the table, the pages. */
export default function ListDemo({
    tasks,
    pagination,
}: {
    tasks: ExampleTask[];
    pagination: ResourceCollection<ExampleTask>;
}) {
    return (
        <div className="flex flex-col gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title="Tasks" />
                <Button>Add task</Button>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <FormField
                    label="Search tasks"
                    htmlFor="list-demo-search"
                    hideLabel
                    className="w-full sm:w-64"
                >
                    <Input id="list-demo-search" placeholder="Search tasks" />
                </FormField>
                <Select defaultValue="all">
                    <SelectTrigger className="w-full sm:w-40">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem value="open">Open</SelectItem>
                        <SelectItem value="done">Done</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <ResponsiveTable
                columns={columns}
                rows={tasks}
                getRowKey={(task) => task.id}
            />
            <Pagination collection={pagination} />
        </div>
    );
}
