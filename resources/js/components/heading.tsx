/**
 * A title: the page's own (`default`, the page's one h1) or a part's
 * (`small`, an h2). It has no description under it.
 */
export default function Heading({
    title,
    variant = 'default',
}: {
    title: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'space-y-0.5'}>
            {variant === 'small' ? (
                <h2 className="mb-0.5 text-base font-medium">{title}</h2>
            ) : (
                <h1 className="text-xl font-semibold">{title}</h1>
            )}
        </header>
    );
}
