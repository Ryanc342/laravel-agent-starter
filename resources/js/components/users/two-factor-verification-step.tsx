import { Form } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Actions from '@/components/shared/actions';
import FormField from '@/components/shared/form-field';
import SubmitButton from '@/components/shared/submit-button';
import { Button } from '@/components/ui/button';
import TwoFactorCodeInput from '@/components/users/two-factor-code-input';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { confirm } from '@/routes/two-factor';

/**
 * The second step of the setup dialog: confirm the code from the authenticator app.
 */
export default function TwoFactorVerificationStep({
    onClose,
    onBack,
}: {
    onClose: () => void;
    onBack: () => void;
}) {
    const [code, setCode] = useState<string>('');
    const pinInputContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setTimeout(() => {
            pinInputContainerRef.current?.querySelector('input')?.focus();
        }, 0);
    }, []);

    return (
        <Form
            noValidate
            {...confirm.form()}
            onSuccess={() => onClose()}
            resetOnError
            resetOnSuccess
            className="w-full"
        >
            {({
                processing,
                errors,
            }: {
                processing: boolean;
                errors?: { confirmTwoFactorAuthentication?: { code?: string } };
            }) => (
                <>
                    <div
                        ref={pinInputContainerRef}
                        className="relative w-full space-y-3"
                    >
                        <FormField
                            label="Authentication code"
                            htmlFor="otp"
                            hideLabel
                            className="py-2"
                            error={errors?.confirmTwoFactorAuthentication?.code}
                        >
                            <TwoFactorCodeInput
                                id="otp"
                                onChange={setCode}
                                disabled={processing}
                            />
                        </FormField>

                        <Actions>
                            <Button
                                variant="outline"
                                onClick={onBack}
                                disabled={processing}
                            >
                                Back
                            </Button>
                            <SubmitButton
                                disabled={code.length < OTP_MAX_LENGTH}
                            >
                                Confirm
                            </SubmitButton>
                        </Actions>
                    </div>
                </>
            )}
        </Form>
    );
}
