import { Form } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/** The Inertia form's props for the default form data, without its own look. */
type FormProps = Omit<
    ComponentProps<typeof Form<Record<string, unknown>>>,
    'className' | 'noValidate'
>;

/**
 * A form on a page: its fields and buttons one under the other, the same space
 * between each. Use it for every form that is not in a dialog. `wideButtons`
 * makes each button the width of the form, as in the narrow sign-in column.
 * It is an Inertia `<Form>`, so it takes the Wayfinder route's `.form()` and
 * gives `errors` to its children.
 */
export default function StackedForm({
    wideButtons = false,
    ...props
}: FormProps & { wideButtons?: boolean }) {
    return (
        <Form
            noValidate
            className={cn(
                'grid gap-6',
                wideButtons && '*:data-[slot=button]:w-full',
            )}
            {...props}
        />
    );
}
