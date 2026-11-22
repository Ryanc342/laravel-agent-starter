import { Form } from '@inertiajs/react';
import { useState, type ComponentProps, type ReactNode } from 'react';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';
import { ResponsiveDialogBody } from '@/components/shared/responsive-dialog';
import SubmitButton from '@/components/shared/submit-button';
import { Button } from '@/components/ui/button';

/** What a Wayfinder route's `.form()` gives: where the request goes and how. */
type RouteForm = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

/**
 * The button of an action that cannot be undone: delete, regenerate, revoke.
 * Use it for every such action. Pressing it only opens ConfirmDialog, which
 * names the action (`title`) and says what will happen (`consequence`); the
 * dialog's confirm button sends the request and is disabled while sending
 * (SubmitButton). Both buttons say the same verb (`children`). `fields` are
 * what the action needs typed first, such as the password; they get the
 * request's errors. `form` is the Wayfinder route's `.form()`.
 */
export default function ConfirmButton({
    form,
    title,
    consequence,
    fields,
    onSuccess,
    children,
    ...buttonProps
}: Omit<
    ComponentProps<typeof Button>,
    'type' | 'onClick' | 'form' | 'children'
> & {
    form: RouteForm;
    title: string;
    consequence: string;
    /** The verb on this button and on the confirm button ("Delete"). */
    children: string;
    fields?: (errors: Record<string, string>) => ReactNode;
    onSuccess?: () => void;
}) {
    const [open, setOpen] = useState(false);

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={setOpen}
            trigger={<Button {...buttonProps}>{children}</Button>}
            title={title}
            consequence={consequence}
        >
            <Form
                noValidate
                {...form}
                options={{ preserveScroll: true }}
                resetOnSuccess
                onSuccess={() => {
                    setOpen(false);
                    onSuccess?.();
                }}
                className="flex min-h-0 flex-1 flex-col gap-6"
            >
                {({ errors, resetAndClearErrors }) => (
                    <>
                        {fields && (
                            <ResponsiveDialogBody>
                                {fields(errors)}
                            </ResponsiveDialogBody>
                        )}
                        <ConfirmDialogActions onCancel={resetAndClearErrors}>
                            <SubmitButton variant="destructive">
                                {children}
                            </SubmitButton>
                        </ConfirmDialogActions>
                    </>
                )}
            </Form>
        </ConfirmDialog>
    );
}
