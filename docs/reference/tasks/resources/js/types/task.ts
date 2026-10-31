/**
 * Matches app/Http/Resources/TaskResource.php. Change both together.
 */
export type Task = {
    id: number;
    title: string;
    due_on: string | null;
    status: 'open' | 'done';
    status_label: string;
    completed_at: string | null;
    is_overdue: boolean;
    can: {
        update: boolean;
        delete: boolean;
        complete: boolean;
    };
};
