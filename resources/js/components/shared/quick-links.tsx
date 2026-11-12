import { Link } from '@inertiajs/react';
import type { NavItem } from '@/types';

/**
 * One row per page the user comes back to: an icon and the page's name as a
 * link. The page decides the list (the dashboard lists its own main pages).
 */
export default function QuickLinks({ links }: { links: NavItem[] }) {
    return (
        <ul className="divide-y rounded-lg border">
            {links.map((item) => (
                <li key={item.title}>
                    <Link
                        href={item.href}
                        prefetch
                        className="flex h-12 items-center gap-3 px-4 text-sm transition-colors hover:bg-muted"
                    >
                        {item.icon && (
                            <item.icon className="size-4 text-muted-foreground" />
                        )}
                        {item.title}
                    </Link>
                </li>
            ))}
        </ul>
    );
}
