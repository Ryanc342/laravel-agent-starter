export default function Heading({
    title,
    variant = 'default',
}: {
    title: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'space-y-0.5'}>
            <h2
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold'
                }
            >
                {title}
            </h2>
        </header>
    );
}
