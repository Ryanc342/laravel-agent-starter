import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

/**
 * The first input in page order whose name has an error. Inside an open dialog
 * only the dialog is searched, so a field of the page behind it with the same
 * name (`password`) is not taken.
 */
function firstFieldWithError(names: string[]): HTMLElement | null {
    const dialogs = document.querySelectorAll('[role="dialog"]');
    const scope = dialogs.item(dialogs.length - 1) ?? document;
    const selector = names
        .map((name) => `[name="${CSS.escape(name)}"]`)
        .join(',');

    return selector ? scope.querySelector<HTMLElement>(selector) : null;
}

function focusField(field: HTMLElement): void {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    field.scrollIntoView({
        behavior: reduceMotion ? 'auto' : 'smooth',
        block: 'center',
    });
    field.focus({ preventScroll: true });
}

/**
 * A failed submit: each field error is one toast, and the first field with an
 * error is scrolled into view and focused. Inertia fires `error` for every
 * failed visit, so the same error after a second submit is announced again.
 * The red border is `FormField`'s. Registered once, by the Toaster.
 */
export function useValidationErrorToasts(): void {
    useEffect(() => {
        return router.on('error', (event) => {
            const errors: Record<string, string> =
                (event as CustomEvent).detail?.errors ?? {};
            const names = Object.keys(errors);

            for (const name of names) {
                toast.error(errors[name], { id: `field-error-${name}` });
            }

            // After the render that shows the errors (a dialog may be opening).
            setTimeout(() => {
                const field = firstFieldWithError(names);

                if (field) {
                    focusField(field);
                }
            }, 0);
        });
    }, []);
}
