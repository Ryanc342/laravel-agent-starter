import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { htmlLang, useLanguage } from '@/hooks/use-language';
import { t } from '@/lib/i18n';

/** The language choice on Settings → Appearance: two modes, so a ToggleGroup. */
export default function LanguageToggle({ className }: { className?: string }) {
    const { language, languages, changeLanguage } = useLanguage();

    return (
        <ToggleGroup
            type="single"
            variant="outline"
            value={language}
            // Pressing the language that is on would clear the value; one stays on.
            onValueChange={(value) => {
                if (value) {
                    changeLanguage(value);
                }
            }}
            className={className}
            aria-label={t('Language')}
        >
            {languages.map(({ value, label }) => (
                <ToggleGroupItem
                    key={value}
                    value={value}
                    lang={htmlLang(value)}
                >
                    {label}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
