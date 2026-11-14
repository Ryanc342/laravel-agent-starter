import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitch from '@/components/shared/language-switch';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';
import { msg, t } from '@/lib/i18n';
import { dashboard, home, login, register } from '@/routes';
import type { NavItem } from '@/types';

export type PublicLink = Pick<NavItem, 'title' | 'href'>;

const defaultLabels = {
    login: msg('Log in'),
    getStarted: msg('Get started'),
    dashboard: msg('Dashboard'),
    openMenu: msg('Open menu'),
    menu: msg('Menu'),
};

export type PublicHeaderLabels = typeof defaultLabels;

/**
 * The top bar of a public page: the logo, the page links, and on the right
 * "Log in" and "Get started" (or "Dashboard" when signed in). On a phone the
 * links and buttons wait in a `Sheet` that a menu button opens. A full-width
 * strip with a line under it, no shadow. `signedIn` comes in as a prop so a
 * demo can show both states; `PublicLayout` reads it from the page props.
 */
export default function PublicHeader({
    links,
    signedIn,
    labels = defaultLabels,
}: {
    links: PublicLink[];
    signedIn: boolean;
    /** The words on the bar, for a project that is not in English. */
    labels?: PublicHeaderLabels;
}) {
    const { name } = usePage().props;
    const isMobile = useIsMobile();

    const buttons = signedIn ? (
        <Button asChild>
            <Link href={dashboard()}>{t(labels.dashboard)}</Link>
        </Button>
    ) : (
        <>
            <Button asChild variant={isMobile ? 'outline' : 'ghost'}>
                <Link href={login()}>{t(labels.login)}</Link>
            </Button>
            <Button asChild>
                <Link href={register()}>{t(labels.getStarted)}</Link>
            </Button>
        </>
    );

    return (
        <header className="border-b bg-background">
            <div className="mx-auto flex h-16 w-full max-w-6xl items-center gap-4 px-4 md:px-6">
                <Link
                    href={home()}
                    className="flex min-w-0 items-center gap-2 font-semibold"
                >
                    <AppLogoIcon className="size-7 shrink-0 fill-current" />
                    <span className="truncate">{name}</span>
                </Link>

                {isMobile ? (
                    <>
                        <LanguageSwitch className="ml-auto" />
                        <Sheet>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <SheetTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="shrink-0"
                                            aria-label={t(labels.openMenu)}
                                        >
                                            <Menu />
                                        </Button>
                                    </SheetTrigger>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t(labels.openMenu)}
                                </TooltipContent>
                            </Tooltip>
                            <SheetContent side="right" className="gap-6 p-4">
                                <SheetHeader className="p-0">
                                    <SheetTitle>{t(labels.menu)}</SheetTitle>
                                </SheetHeader>
                                {links.length > 0 && (
                                    <nav
                                        aria-label={t('Pages')}
                                        className="grid gap-1"
                                    >
                                        {links.map((link) => (
                                            <Button
                                                key={link.title}
                                                asChild
                                                variant="ghost"
                                                className="justify-start"
                                            >
                                                <Link href={link.href}>
                                                    {t(link.title)}
                                                </Link>
                                            </Button>
                                        ))}
                                    </nav>
                                )}
                                <div className="grid gap-2">{buttons}</div>
                            </SheetContent>
                        </Sheet>
                    </>
                ) : (
                    <>
                        {links.length > 0 && (
                            <nav
                                aria-label={t('Pages')}
                                className="flex items-center gap-1"
                            >
                                {links.map((link) => (
                                    <Button
                                        key={link.title}
                                        asChild
                                        variant="ghost"
                                    >
                                        <Link href={link.href}>
                                            {t(link.title)}
                                        </Link>
                                    </Button>
                                ))}
                            </nav>
                        )}
                        <div className="ml-auto flex items-center gap-2">
                            <LanguageSwitch />
                            {buttons}
                        </div>
                    </>
                )}
            </div>
        </header>
    );
}
