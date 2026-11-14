import { usePasskeyRegister } from '@laravel/passkeys/react';
import { useState } from 'react';
import FormField from '@/components/shared/form-field';
import Actions from '@/components/shared/actions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useErrorsToast } from '@/hooks/use-errors-toast';
import { t } from '@/lib/i18n';

type Props = {
    onSuccess: () => void;
};

export default function PasskeyRegistration({ onSuccess }: Props) {
    const [name, setName] = useState('');

    const [showForm, setShowForm] = useState(false);
    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        onSuccess: () => {
            setName('');
            setShowForm(false);
            onSuccess();
        },
    });

    // The browser's passkey error is not an Inertia error, so it toasts here.
    useErrorsToast(error ? [error] : undefined, 'passkey-register-error');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        await register(name);
    };

    const handleCancel = () => {
        setShowForm(false);
        setName('');
    };

    if (!isSupported) {
        return (
            <div className="text-sm text-muted-foreground">
                {t('Passkeys are not supported in this browser.')}
            </div>
        );
    }

    if (!showForm) {
        return (
            <Actions>
                <Button variant="outline" onClick={() => setShowForm(true)}>
                    {t('Add passkey')}
                </Button>
            </Actions>
        );
    }

    return (
        <form
            onSubmit={handleSubmit}
            className="space-y-4 rounded-lg border border-border bg-muted/50 p-4"
        >
            <FormField
                label={t('Passkey name')}
                htmlFor="passkey-name"
                error={error ?? undefined}
            >
                <Input
                    id="passkey-name"
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder={t('e.g., MacBook Pro, iPhone')}
                    autoFocus
                />
                <p className="text-xs text-muted-foreground">
                    {t('A name helps you identify this passkey later.')}
                </p>
            </FormField>

            <Actions>
                <Button type="button" variant="ghost" onClick={handleCancel}>
                    {t('Cancel')}
                </Button>
                <Button type="submit" disabled={isLoading || !name.trim()}>
                    {isLoading ? t('Registering...') : t('Register passkey')}
                </Button>
            </Actions>
        </form>
    );
}
