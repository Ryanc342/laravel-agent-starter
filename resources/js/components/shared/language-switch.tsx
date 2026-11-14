import { Button } from '@/components/ui/button';
import { htmlLang, useLanguage } from '@/hooks/use-language';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * "EN / 中文" for the pages a guest sees (the public bar and the sign-in pages),
 * at the top right. It changes the language through the same route as the
 * Appearance setting and the `locale` cookie keeps it.
 */
export default function LanguageSwitch({ className }: { className?: string }) {
    const { language, languages, changeLanguage } = useLanguage();

    return (
        <nav
            aria-label={t('Language')}
            className={cn('flex items-center gap-1 text-sm', className)}
        >
            {languages.map(({ value, short, label }, index) => (
                <span key={value} className="flex items-center gap-1">
                    {index > 0 && (
                        <span
                            aria-hidden="true"
                            className="text-muted-foreground"
                        >
                            /
                        </span>
                    )}
                    <Button
                        variant="ghost"
                        size="sm"
                        lang={htmlLang(value)}
                        aria-label={label}
                        aria-current={language === value ? 'true' : undefined}
                        className={cn(
                            language === value
                                ? 'bg-accent-pressed font-medium'
                                : 'text-muted-foreground',
                        )}
                        onClick={() => changeLanguage(value)}
                    >
                        {short}
                    </Button>
                </span>
            ))}
        </nav>
    );
}
