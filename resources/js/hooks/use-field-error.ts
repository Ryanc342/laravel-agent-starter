import { useEffect } from 'react';
import { toast } from 'sonner';

// The fields whose error arrived in the same moment. One microtask later the
// first of them, in page order, is scrolled to and focused.
const waiting = new Set<string>();

function focusFirstField(): void {
    const fields = [...waiting]
        .map((id) => document.getElementById(id))
        .filter((field): field is HTMLElement => field !== null);

    waiting.clear();

    const [first] = fields.sort((a, b) =>
        a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING
            ? -1
            : 1,
    );

    if (!first) {
        return;
    }

    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    first.scrollIntoView({
        behavior: reduceMotion ? 'auto' : 'smooth',
        block: 'center',
    });
    first.focus({ preventScroll: true });
}

/**
 * What a field with an error does: its message is one toast, the input gets
 * the red border (`aria-invalid`), and the first field with an error on the
 * page is scrolled into view and focused. `FormField` calls it, so every form
 * built from FormField behaves the same.
 */
export function useFieldError(
    error: string | undefined,
    fieldId: string,
): void {
    useEffect(() => {
        const field = document.getElementById(fieldId);

        if (!error) {
            field?.removeAttribute('aria-invalid');

            return;
        }

        field?.setAttribute('aria-invalid', 'true');
        toast.error(error, { id: `field-error-${fieldId}` });

        if (waiting.size === 0) {
            queueMicrotask(focusFirstField);
        }

        waiting.add(fieldId);
    }, [error, fieldId]);
}
