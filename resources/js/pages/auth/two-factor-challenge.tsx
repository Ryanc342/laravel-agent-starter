import { Head, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import FormField from '@/components/shared/form-field';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import StackedForm from '@/components/shared/stacked-form';
import { Button } from '@/components/ui/button';
import TwoFactorCodeInput from '@/components/users/two-factor-code-input';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = showRecoveryInput
        ? {
              title: 'Recovery code',
              toggleText: 'Use an authentication code instead',
          }
        : {
              title: 'Authentication code',
              toggleText: 'Use a recovery code instead',
          };

    setLayoutProps({
        title: authConfigContent.title,
    });

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title="Two-factor authentication" />

            <StackedForm
                wideButtons
                {...store.form()}
                resetOnError
                resetOnSuccess={!showRecoveryInput}
            >
                {({ errors, processing, clearErrors }) => (
                    <>
                        {showRecoveryInput ? (
                            <TextField
                                label="Recovery code"
                                id="recovery_code"
                                name="recovery_code"
                                type="text"
                                autoFocus={showRecoveryInput}
                                required
                                error={errors.recovery_code}
                            />
                        ) : (
                            <FormField
                                label="Authentication code"
                                htmlFor="code"
                                hideLabel
                                error={errors.code}
                            >
                                <TwoFactorCodeInput
                                    id="code"
                                    value={code}
                                    onChange={setCode}
                                    disabled={processing}
                                />
                            </FormField>
                        )}

                        <SubmitButton>Continue</SubmitButton>

                        <Button
                            variant="link"
                            onClick={() => toggleRecoveryMode(clearErrors)}
                        >
                            {authConfigContent.toggleText}
                        </Button>
                    </>
                )}
            </StackedForm>
        </>
    );
}
