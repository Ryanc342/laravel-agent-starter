import { Form } from '@inertiajs/react';
import SubmitButton from '@/components/shared/submit-button';
import { store as markAllRead } from '@/routes/notifications/read-all';

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
            noValidate
            {...markAllRead.form()}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
        >
            <SubmitButton
                variant={size === 'sm' ? 'ghost' : 'outline'}
                size={size}
            >
                Mark all as read
            </SubmitButton>
        </Form>
    );
}
