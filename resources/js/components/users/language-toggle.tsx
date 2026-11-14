import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { languages, useLanguage, type Language } from '@/hooks/use-language';
import { t } from '@/lib/i18n';

/** The language choice on Settings → Appearance: two modes, so a ToggleGroup. */
export default function LanguageToggle({ className }: { className?: string }) {
    const { language, changeLanguage } = useLanguage();

    return (
        <ToggleGroup
            type="single"
            variant="outline"
            value={language}
            // Pressing the language that is on would clear the value; one stays on.
            onValueChange={(value) => {
                if (value) {
                    changeLanguage(value as Language);
                }
            }}
            className={className}
            aria-label={t('Language')}
        >
            {languages.map(({ value, name }) => (
                <ToggleGroupItem
                    key={value}
                    value={value}
                    lang={value === 'en' ? 'en' : 'zh-CN'}
                >
                    {name}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
