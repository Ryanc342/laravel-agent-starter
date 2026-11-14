import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/shared/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import TwoFactorCodeInput from '@/components/users/two-factor-code-input';
import { store } from '@/routes/two-factor/login';
import { msg, t } from '@/lib/i18n';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = showRecoveryInput
        ? {
              title: msg('Recovery code'),
              toggleText: t('login using an authentication code'),
          }
        : {
              title: msg('Authentication code'),
              toggleText: t('login using a recovery code'),
          };

    setLayoutProps({
        title: authConfigContent.title,
    });

    // The toggle button stays a button inside the sentence: split it at its placeholder.
    const toggleSentence = t('or you can :action', {
        action: '\u0001',
    }).split('\u0001');

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title={t('Two-factor authentication')} />

            <div className="space-y-6">
                <Form
                    {...store.form()}
                    className="space-y-4"
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                <FormField
                                    label={t('Recovery code')}
                                    htmlFor="recovery_code"
                                    hideLabel
                                    error={errors.recovery_code}
                                >
                                    <Input
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        placeholder={t('Enter recovery code')}
                                        autoFocus={showRecoveryInput}
                                        required
                                    />
                                </FormField>
                            ) : (
                                <FormField
                                    label={t('Authentication code')}
                                    htmlFor="code"
                                    hideLabel
                                    error={errors.code}
                                >
                                    <div className="flex w-full items-center justify-center">
                                        <TwoFactorCodeInput
                                            id="code"
                                            value={code}
                                            onChange={setCode}
                                            disabled={processing}
                                        />
                                    </div>
                                </FormField>
                            )}

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                {t('Continue')}
                            </Button>

                            <div className="text-center text-sm text-muted-foreground">
                                <span>{toggleSentence[0]}</span>
                                <Button
                                    type="button"
                                    variant="link"
                                    size="inline"
                                    onClick={() =>
                                        toggleRecoveryMode(clearErrors)
                                    }
                                >
                                    {authConfigContent.toggleText}
                                </Button>
                                {toggleSentence[1] && (
                                    <span>{toggleSentence[1]}</span>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
