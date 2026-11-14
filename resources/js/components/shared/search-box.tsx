import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useId, useState, type FormEvent } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n';

/**
 * The search box of the top bar. Enter opens the results page (`action`) with
 * the text in the URL (`?q=invoice`); the page passes the text back as
 * `defaultValue` so the box shows it again. An empty box does nothing. It is
 * shown only when a project has something to search.
 */
export default function SearchBox({
    action,
    defaultValue = '',
}: {
    action: string;
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
            role="search"
            onSubmit={submit}
            className="relative w-full max-w-sm"
        >
            <Label htmlFor={id} className="sr-only">
                {t('Search')}
            </Label>
            <Search
                aria-hidden="true"
                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                id={id}
                type="search"
                value={text}
                onChange={(event) => setText(event.target.value)}
                placeholder={t('Search')}
                className="pl-9"
            />
        </form>
    );
}
