import { Check, Copy } from 'lucide-react';
import Actions from '@/components/shared/actions';
import ErrorState from '@/components/shared/error-state';
import IconButton from '@/components/shared/icon-button';
import LabeledDivider from '@/components/shared/labeled-divider';
import TextField from '@/components/shared/text-field';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useClipboard } from '@/hooks/use-clipboard';
import { useErrorsToast } from '@/hooks/use-errors-toast';

/**
 * The first step of the setup dialog: the QR code and the setup key to copy.
 */
export default function TwoFactorSetupStep({
    qrCodeSvg,
    manualSetupKey,
    buttonText,
    onNextStep,
    errors,
}: {
    qrCodeSvg: string | null;
    manualSetupKey: string | null;
    buttonText: string;
    onNextStep: () => void;
    errors: string[];
}) {
    const [copiedText, copy] = useClipboard();
    const IconComponent = copiedText === manualSetupKey ? Check : Copy;

    useErrorsToast(errors, 'two-factor-load-error');

    return (
        <>
            {errors?.length ? (
                <ErrorState message="Could not load the setup code. Close this window and try again." />
            ) : (
                <>
                    <div className="mx-auto flex max-w-md overflow-hidden">
                        <div className="mx-auto aspect-square w-64">
                            <div className="z-10 flex h-full w-full items-center justify-center p-5">
                                {qrCodeSvg ? (
                                    // A QR code is dark on white in dark mode too, so phones can scan it.
                                    <div
                                        className="aspect-square w-full rounded-lg bg-qr-background p-2 [&_svg]:size-full"
                                        dangerouslySetInnerHTML={{
                                            __html: qrCodeSvg,
                                        }}
                                    />
                                ) : (
                                    <Skeleton className="aspect-square w-full" />
                                )}
                            </div>
                        </div>
                    </div>

                    <Actions>
                        <Button onClick={onNextStep}>{buttonText}</Button>
                    </Actions>

                    <LabeledDivider>or, enter the code manually</LabeledDivider>

                    <div className="flex w-full items-center gap-2">
                        {!manualSetupKey ? (
                            <Skeleton className="h-12 w-full" />
                        ) : (
                            <>
                                <TextField
                                    label="Setup key"
                                    readOnly
                                    value={manualSetupKey}
                                    className="flex-1"
                                />
                                <IconButton
                                    label="Copy setup key"
                                    variant="outline"
                                    onClick={() => copy(manualSetupKey)}
                                >
                                    <IconComponent />
                                </IconButton>
                            </>
                        )}
                    </div>
                </>
            )}
        </>
    );
}
