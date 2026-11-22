import { router } from '@inertiajs/react';
import { useId, useState, type FormEvent } from 'react';
import { FloatingLabel, floatingControl } from '@/components/shared/form-field';
import { Input } from '@/components/ui/input';

/**
 * The search box of the top bar. Use it when a project has something to
 * search across its records. Enter opens the results page (`action`) with the
 * text in the URL (`?q=invoice`); the page passes the text back as
 * `defaultValue` so the box shows it again. An empty box does nothing. Its
 * label floats inside the box.
 */
export default function SearchBox({
    action,
    label = 'Search',
    defaultValue = '',
}: {
    action: string;
    label?: string;
    defaultValue?: string;
}) {
    const id = useId();
    const [text, setText] = useState(defaultValue);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (text.trim() !== '') {
            router.get(action, { q: text.trim() });
        }
    };

    return (
        <form
            noValidate
            role="search"
            onSubmit={submit}
            className="relative w-full max-w-sm"
        >
            <Input
                id={id}
                type="search"
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder=" "
                className={floatingControl}
            />
            <FloatingLabel htmlFor={id}>{label}</FloatingLabel>
        </form>
    );
}
