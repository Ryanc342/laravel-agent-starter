import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import type { ExampleTask } from '@/components/style/types';
import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';

/** One record's page: the breadcrumb that ends at the list, its name, status and actions, then its facts. */
export default function DetailDemo({ task }: { task: ExampleTask }) {
    return (
        <div className="flex flex-col gap-8">
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="#list">Tasks</BreadcrumbLink>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 flex-wrap items-start gap-2">
                    <Heading title={task.title} />
                    <Badge variant="secondary" className="mt-1">
                        {task.status}
                    </Badge>
                    {task.overdue && (
                        <Badge variant="destructive" className="mt-1">
                            Overdue
                        </Badge>
                    )}
                </div>

                <Actions data-testid="record-actions">
                    <Button variant="outline">Edit task</Button>
                    <Button variant="outline">Delete task</Button>
                    <Button>Complete task</Button>
                </Actions>
            </div>

            <dl className="grid max-w-xl gap-1 text-sm">
                <dt className="text-muted-foreground">Due date</dt>
                <dd>{task.due_on ? formatDate(task.due_on) : 'No due date'}</dd>
            </dl>
        </div>
    );
}
