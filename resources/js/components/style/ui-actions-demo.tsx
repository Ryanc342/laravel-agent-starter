import { Bell } from 'lucide-react';
import { toast } from 'sonner';
import { Specimen } from '@/components/style/style-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { t } from '@/lib/i18n';

const variants = [
    'default',
    'secondary',
    'outline',
    'ghost',
    'destructive',
    'link',
] as const;

// A status starts with a capital letter, as the backend sends it.
const badges = [
    { variant: 'default', label: 'Default' },
    { variant: 'secondary', label: 'Open' },
    { variant: 'outline', label: 'Outline' },
    { variant: 'success', label: 'Done' },
    { variant: 'warning', label: 'Due soon' },
    { variant: 'destructive', label: 'Overdue' },
] as const;

/** Button, Badge, Spinner, Tooltip and toasts: what the user presses and what tells them. */
export default function UiActionsDemo() {
    return (
        <div className="grid gap-8">
            <Specimen
                label={t(
                    'Button variants: one default per area, outline for the rest',
                )}
            >
                {variants.map((variant) => (
                    <Button key={variant} variant={variant}>
                        {variant}
                    </Button>
                ))}
            </Specimen>

            <Specimen label={t('Button sizes')}>
                <Button size="sm">{t('Small')}</Button>
                <Button>{t('Default')}</Button>
                <Button size="lg">{t('Large')}</Button>
                <span className="text-sm text-muted-foreground">
                    {t('or in a sentence:')}{' '}
                    <Button variant="link" size="inline">
                        {t('inline')}
                    </Button>
                </span>
            </Specimen>

            <Specimen
                label={t(
                    'Button states: disabled, and sending (disabled with a Spinner)',
                )}
            >
                <Button disabled>{t('Disabled')}</Button>
                <Button disabled>
                    <Spinner />
                    {t('Saving')}
                </Button>
                <Button variant="outline" disabled>
                    {t('Disabled')}
                </Button>
            </Specimen>

            <Specimen
                label={t('Icon button: always a Tooltip and an aria-label')}
            >
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label={t('Notifications')}
                        >
                            <Bell />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{t('Notifications')}</TooltipContent>
                </Tooltip>
            </Specimen>

            <Specimen
                label={t('Badge: a status, in the words the backend sends')}
            >
                {badges.map(({ variant, label }) => (
                    <Badge key={variant} variant={variant}>
                        {label}
                    </Badge>
                ))}
            </Specimen>

            <Specimen
                label={t('Toasts: the result of an action and a network error')}
            >
                <Button
                    variant="outline"
                    onClick={() => toast.success('Task created.')}
                >
                    {t('Success toast')}
                </Button>
                <Button
                    variant="outline"
                    onClick={() =>
                        toast.error(
                            'Cannot reach the server. Check your connection and try again.',
                        )
                    }
                >
                    {t('Error toast')}
                </Button>
            </Specimen>
        </div>
    );
}
