import { Link, usePage } from '@inertiajs/react';
import type { PublicLink } from '@/components/shared/public-header';
import { t } from '@/lib/i18n';

/** The foot of a public page: one row of links and the copyright line. */
export default function PublicFooter({
    links,
    year,
}: {
    links: PublicLink[];
    year: number;
}) {
    const { name } = usePage().props;

    return (
        <footer className="border-t">
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-8 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6">
                {links.length > 0 && (
                    <nav
                        aria-label={t('Footer')}
                        className="flex flex-wrap gap-x-6 gap-y-2"
                    >
                        {links.map((link) => (
                            <Link
                                key={link.title}
                                href={link.href}
                                className="hover:text-foreground active:text-primary-pressed"
                            >
                                {t(link.title)}
                            </Link>
                        ))}
                    </nav>
                )}
                <p>
                    © {year} {name}
                </p>
            </div>
        </footer>
    );
}
