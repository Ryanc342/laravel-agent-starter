import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';

/**
 * A file field whose words are the app's own: a "Choose file" button and the
 * chosen file's name ("No file chosen" before), both translated. The browser's
 * own file input says them in the browser's language, so it stays hidden; it
 * still carries `name` into the form, and when a failed submit focuses the
 * field by that name, focus moves on to the button. `id` is the button's, so
 * the `FormField` label names it and its error marks it.
 */
export default function FilePicker({
    id,
    name,
    accept,
    required = false,
}: {
    id: string;
    name?: string;
    accept?: string;
    /** Announced as required; the FormRequest still decides (`noValidate`). */
    required?: boolean;
}) {
    const input = useRef<HTMLInputElement>(null);
    const button = useRef<HTMLButtonElement>(null);
    const [file, setFile] = useState<string | null>(null);

    return (
        <div className="flex min-w-0 items-center gap-3">
            <input
                ref={input}
                type="file"
                name={name}
                accept={accept}
                required={required}
                tabIndex={-1}
                aria-hidden="true"
                // Visually hidden but focusable, so focus can be passed on.
                className="sr-only"
                onFocus={() => button.current?.focus({ preventScroll: true })}
                onChange={(event) =>
                    setFile(event.target.files?.[0]?.name ?? null)
                }
            />
            <Button
                ref={button}
                id={id}
                type="button"
                variant="outline"
                aria-describedby={`${id}-chosen`}
                aria-required={required || undefined}
                onClick={() => input.current?.click()}
            >
                {t('Choose file')}
            </Button>
            <span
                id={`${id}-chosen`}
                className="min-w-0 truncate text-sm text-muted-foreground"
            >
                {file ?? t('No file chosen')}
            </span>
        </div>
    );
}
