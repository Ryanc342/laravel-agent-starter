import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { msg, t } from '@/lib/i18n';

const modes: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: msg('Light') },
    { value: 'dark', icon: Moon, label: msg('Dark') },
    { value: 'system', icon: Monitor, label: msg('System') },
];

/** The light, dark or system mode: three modes, so a ToggleGroup. */
export default function AppearanceToggleTab({
    className,
}: {
    className?: string;
}) {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <ToggleGroup
            type="single"
            variant="outline"
            value={appearance}
            // Pressing the mode that is on would clear the value; one stays on.
            onValueChange={(value) => {
                if (value) {
                    updateAppearance(value as Appearance);
                }
            }}
            className={className}
            aria-label={t('Appearance')}
        >
            {modes.map(({ value, icon: Icon, label }) => (
                <ToggleGroupItem key={value} value={value}>
                    <Icon />
                    {t(label)}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
