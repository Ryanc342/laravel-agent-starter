import { usePasskeyRegister } from '@laravel/passkeys/react';
import { useState } from 'react';
import Actions from '@/components/shared/actions';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import { Button } from '@/components/ui/button';
import { useErrorsToast } from '@/hooks/use-errors-toast';

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
                Passkeys are not supported in this browser.
            </div>
        );
    }

    if (!showForm) {
        return (
            <Actions>
                <Button variant="outline" onClick={() => setShowForm(true)}>
                    Add
                </Button>
            </Actions>
        );
    }

    return (
        <form
            noValidate
            onSubmit={handleSubmit}
            className="space-y-4 rounded-lg border border-border bg-muted/50 p-4"
        >
            <div className="grid gap-2">
                <TextField
                    label="Passkey name"
                    id="passkey-name"
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    autoFocus
                    error={error ?? undefined}
                />
                <p className="text-xs text-muted-foreground">
                    A name such as the device helps you identify this passkey
                    later.
                </p>
            </div>

            <Actions>
                <Button variant="ghost" onClick={handleCancel}>
                    Cancel
                </Button>
                <SubmitButton sending={isLoading} disabled={!name.trim()}>
                    Register
                </SubmitButton>
            </Actions>
        </form>
    );
}
