import { Head } from '@inertiajs/react';
import TaskPdfController from '@/actions/App/Http/Controllers/TaskPdfController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { index } from '@/routes/tasks';
import type { Task } from '@/types/task';

/**
 * The sheet of one task, as it prints. The bar above it ("Print", "Download
 * PDF") is hidden when printing, and so is the app chrome (resources/css/app.css,
 * `@media print`). The PDF is made on the server and shows the same facts.
 */
export default function PrintTask({ task }: { task: Task }) {
    return (
        <>
            <Head title={task.title} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap gap-2 print:hidden">
                    <Button onClick={() => window.print()}>Print</Button>
                    <Button variant="outline" asChild>
                        <a href={TaskPdfController.url(task.id)}>
                            Download PDF
                        </a>
                    </Button>
                </div>

                <div className="flex max-w-3xl flex-col gap-6 rounded-lg border p-6 print:max-w-none print:rounded-none print:border-0 print:p-0">
                    <div className="flex flex-wrap items-start gap-2">
                        <Heading title={task.title} />
                        <Badge variant="secondary" className="mt-1">
                            {task.status_label}
                        </Badge>
                        {task.is_overdue && (
                            <Badge variant="destructive" className="mt-1">
                                Overdue
                            </Badge>
                        )}
                    </div>

                    <dl className="grid gap-1 text-sm">
                        <dt className="text-muted-foreground">Due date</dt>
                        <dd>
                            {task.due_on
                                ? formatDate(task.due_on)
                                : 'No due date'}
                        </dd>
                    </dl>
                </div>
            </div>
        </>
    );
}

// The breadcrumb ends at the list, the way back, as on the task page.
PrintTask.layout = {
    breadcrumbs: [{ title: 'Tasks', href: index() }],
};
