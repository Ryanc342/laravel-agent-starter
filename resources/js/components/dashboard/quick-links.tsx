import { Link } from '@inertiajs/react';
import { Bell, Palette, ShieldCheck, UserRound } from 'lucide-react';
import { edit as editAppearance } from '@/routes/appearance';
import { index as notifications } from '@/routes/notifications';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

// The app's own pages the user comes back to. The dashboard itself is not
// listed. A project adds its main pages here as it grows.
const quickLinks: NavItem[] = [
    { title: 'Notifications', href: notifications(), icon: Bell },
    { title: 'Profile', href: editProfile(), icon: UserRound },
    { title: 'Security', href: editSecurity(), icon: ShieldCheck },
    { title: 'Appearance', href: editAppearance(), icon: Palette },
];

/** One row per page: an icon and the page's name as a link. */
export default function QuickLinks() {
    return (
        <ul className="divide-y rounded-lg border">
            {quickLinks.map((item) => (
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
