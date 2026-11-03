import { Form } from '@inertiajs/react';
import AllNotificationsReadController from '@/actions/App/Http/Controllers/AllNotificationsReadController';
import { Button } from '@/components/ui/button';

/**
 * "Mark all as read", for the notifications page and the bell's card. Shown
 * from notificationSummary.can.mark_all_read, which the backend calculates.
 */
export default function MarkAllReadButton({
    size = 'default',
    onSuccess,
}: {
    size?: 'default' | 'sm';
    onSuccess?: () => void;
}) {
    return (
        <Form
            {...AllNotificationsReadController.store.form()}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
        >
            {({ processing }) => (
                <Button
                    type="submit"
                    variant={size === 'sm' ? 'ghost' : 'outline'}
                    size={size}
                    disabled={processing}
                >
                    Mark all as read
                </Button>
            )}
        </Form>
    );
}
