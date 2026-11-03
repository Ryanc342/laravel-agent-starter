import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { show } from '@/routes/tasks';
import type { Task } from '@/types/task';

/**
 * The task list. The title opens the task's page, where its actions are. The
 * status comes right after the title, so a phone shows both without scrolling.
 */
export default function TaskTable({ tasks }: { tasks: Task[] }) {
    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="px-4">Title</TableHead>
                        <TableHead className="px-4">Status</TableHead>
                        <TableHead className="px-4">Due date</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {tasks.map((task) => (
                        <TableRow key={task.id}>
                            <TableCell className="max-w-80 truncate px-4 font-medium">
                                <Link
                                    href={show(task.id)}
                                    className="underline-offset-4 hover:underline"
                                >
                                    {task.title}
                                </Link>
                            </TableCell>
                            <TableCell className="px-4">
                                <div className="flex gap-2">
                                    <Badge variant="secondary">
                                        {task.status_label}
                                    </Badge>
                                    {task.is_overdue && (
                                        <Badge variant="destructive">
                                            Overdue
                                        </Badge>
                                    )}
                                </div>
                            </TableCell>
                            <TableCell className="px-4">
                                {task.due_on && formatDate(task.due_on)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
