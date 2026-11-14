import { useFlashToast } from '@/hooks/use-flash-toast';
import { useNetworkErrorToast } from '@/hooks/use-network-error-toast';
import { useValidationErrorToasts } from '@/hooks/use-validation-error-toasts';
import { useAppearance } from '@/hooks/use-appearance';
import { t } from '@/lib/i18n';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();
    useNetworkErrorToast();
    useValidationErrorToasts();

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            containerAriaLabel={t('Notifications')}
            position="top-center"
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
