import type { ComponentProps, ReactNode } from 'react';
import Actions from '@/components/shared/actions';
import ResponsiveDialog, {
    ResponsiveDialogClose,
} from '@/components/shared/responsive-dialog';
import { Button } from '@/components/ui/button';

/** The dialog's props without `instruction`, kept for each width separately. */
type WithoutInstruction<T> = T extends unknown ? Omit<T, 'instruction'> : never;

/**
 * Confirm something that cannot be undone: the action named in full as the
 * title, a `consequence` that says what will happen, then the buttons. Put
 * `ConfirmDialogActions` with the destructive button after any fields (a
 * password), inside the `<Form>` when the confirm button submits one.
 */
export default function ConfirmDialog({
    consequence,
    ...props
}: WithoutInstruction<ComponentProps<typeof ResponsiveDialog>> & {
    consequence: string;
}) {
    return <ResponsiveDialog instruction={consequence} {...props} />;
}

/** Cancel (it closes the dialog), then the confirm button passed as `children`. */
export function ConfirmDialogActions({
    onCancel,
    children,
}: {
    onCancel?: () => void;
    children: ReactNode;
}) {
    return (
        <Actions className="shrink-0">
            <ResponsiveDialogClose asChild>
                <Button type="button" variant="outline" onClick={onCancel}>
                    Cancel
                </Button>
            </ResponsiveDialogClose>
            {children}
        </Actions>
    );
}
