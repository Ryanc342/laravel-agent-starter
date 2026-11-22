import { Link } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

/**
 * The pages of a customer's account:
 * one row of links under the top bar, the current page in the selected colour.
 * A link is current when its address is the page's, or when it says so itself
 * (`isActive`, as a demo does). On a phone the row scrolls sideways inside its
 * own strip, never the page.
 */
export default function AccountNav({ items }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <nav
            aria-label="Account"
            className="-mx-4 flex gap-1 overflow-x-auto border-b px-4 pb-3 md:mx-0 md:px-0"
        >
            {items.map((item) => {
                const current = item.isActive ?? isCurrentUrl(item.href);

                return (
                    <Link
                        key={item.title}
                        href={item.href}
                        aria-current={current ? 'page' : undefined}
                        className={cn(
                            'flex h-10 shrink-0 items-center rounded-md px-3 text-sm font-medium transition-colors hover:bg-accent active:bg-accent-pressed',
                            current && 'bg-accent-pressed',
                        )}
                    >
                        {item.title}
                    </Link>
                );
            })}
        </nav>
    );
}
