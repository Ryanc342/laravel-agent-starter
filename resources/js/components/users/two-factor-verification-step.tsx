import { Form } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import FormField from '@/components/shared/form-field';
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
            {...confirm.form()}
            onSuccess={() => onClose()}
            resetOnError
            resetOnSuccess
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
                            <div className="flex w-full items-center justify-center">
                                <TwoFactorCodeInput
                                    id="otp"
                                    onChange={setCode}
                                    disabled={processing}
                                />
                            </div>
                        </FormField>

                        <div className="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1"
                                onClick={onBack}
                                disabled={processing}
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                className="flex-1"
                                disabled={
                                    processing || code.length < OTP_MAX_LENGTH
                                }
                            >
                                Confirm
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
