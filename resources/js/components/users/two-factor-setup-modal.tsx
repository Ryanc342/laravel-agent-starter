import { ScanLine } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import ResponsiveDialog, {
    ResponsiveDialogBody,
} from '@/components/shared/responsive-dialog';
import TwoFactorSetupStep from '@/components/users/two-factor-setup-step';
import TwoFactorVerificationStep from '@/components/users/two-factor-verification-step';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { t } from '@/lib/i18n';

type Props = {
    isOpen: boolean;
    onClose: () => void;
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    clearSetupData: () => void;
    fetchSetupData: () => Promise<void>;
    errors: string[];
};

export default function TwoFactorSetupModal({
    isOpen,
    onClose,
    requiresConfirmation,
    twoFactorEnabled,
    qrCodeSvg,
    manualSetupKey,
    clearSetupData,
    fetchSetupData,
    errors,
}: Props) {
    const [showVerificationStep, setShowVerificationStep] =
        useState<boolean>(false);

    // Not memoised: the sentences must follow the language of the page.
    const modalConfig = ((): {
        title: string;
        description: string;
        buttonText: string;
    } => {
        if (twoFactorEnabled) {
            return {
                title: t('Two-factor authentication enabled'),
                description: t(
                    'Scan the QR code or enter the setup key in your authenticator app.',
                ),
                buttonText: t('Close'),
            };
        }

        if (showVerificationStep) {
            return {
                title: t('Verify authentication code'),
                description: t(
                    'Enter the :length-digit code from your authenticator app.',
                    { length: OTP_MAX_LENGTH },
                ),
                buttonText: t('Continue'),
            };
        }

        return {
            title: t('Enable two-factor authentication'),
            description: t(
                'Scan the QR code or enter the setup key in your authenticator app.',
            ),
            buttonText: t('Continue'),
        };
    })();

    const resetModalState = useCallback(() => {
        if (twoFactorEnabled) {
            clearSetupData();
        }

        setShowVerificationStep(false);
    }, [clearSetupData, twoFactorEnabled]);

    const handleClose = useCallback(() => {
        resetModalState();
        onClose();
    }, [onClose, resetModalState]);

    const handleModalNextStep = useCallback(() => {
        if (requiresConfirmation) {
            setShowVerificationStep(true);

            return;
        }

        clearSetupData();
        handleClose();
    }, [requiresConfirmation, clearSetupData, handleClose]);

    const fetchSetupDataRef = useRef(fetchSetupData);

    useEffect(() => {
        fetchSetupDataRef.current = fetchSetupData;
    }, [fetchSetupData]);

    useEffect(() => {
        if (isOpen && !qrCodeSvg) {
            void fetchSetupDataRef.current();
        }
    }, [isOpen, qrCodeSvg]);

    return (
        <ResponsiveDialog
            open={isOpen}
            onOpenChange={(open) => !open && handleClose()}
            title={modalConfig.title}
            instruction={modalConfig.description}
            icon={<ScanLine className="size-6" />}
            narrow
        >
            <ResponsiveDialogBody className="items-center">
                {showVerificationStep ? (
                    <TwoFactorVerificationStep
                        onClose={handleClose}
                        onBack={() => setShowVerificationStep(false)}
                    />
                ) : (
                    <TwoFactorSetupStep
                        qrCodeSvg={qrCodeSvg}
                        manualSetupKey={manualSetupKey}
                        buttonText={modalConfig.buttonText}
                        onNextStep={handleModalNextStep}
                        errors={errors}
                    />
                )}
            </ResponsiveDialogBody>
        </ResponsiveDialog>
    );
}
