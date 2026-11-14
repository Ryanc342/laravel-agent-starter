import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitch from '@/components/shared/language-switch';
import { t } from '@/lib/i18n';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({ children, title }: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <LanguageSwitch className="absolute top-4 right-4" />
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-9 fill-current text-foreground" />
                            </div>
                            <span className="sr-only">{t(title ?? '')}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">
                                {t(title ?? '')}
                            </h1>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
