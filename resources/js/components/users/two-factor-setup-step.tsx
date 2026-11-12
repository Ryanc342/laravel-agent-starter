import { Check, Copy } from 'lucide-react';
import ErrorState from '@/components/shared/error-state';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
                        <div className="mx-auto aspect-square w-64 rounded-lg border border-border">
                            <div className="z-10 flex h-full w-full items-center justify-center p-5">
                                {qrCodeSvg ? (
                                    // A QR code is dark on white in dark mode too, so phones can scan it.
                                    <div
                                        className="aspect-square w-full rounded-lg bg-white p-2 [&_svg]:size-full"
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

                    <div className="flex w-full space-x-5">
                        <Button className="w-full" onClick={onNextStep}>
                            {buttonText}
                        </Button>
                    </div>

                    <div className="relative flex w-full items-center justify-center">
                        <div className="absolute inset-0 top-1/2 h-px w-full bg-border" />
                        <span className="relative bg-background px-2 py-1 text-muted-foreground">
                            or, enter the code manually
                        </span>
                    </div>

                    <div className="flex w-full items-center gap-2">
                        {!manualSetupKey ? (
                            <Skeleton className="h-10 w-full" />
                        ) : (
                            <>
                                <Input
                                    readOnly
                                    aria-label="Setup key"
                                    value={manualSetupKey}
                                />
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Copy setup key"
                                            onClick={() => copy(manualSetupKey)}
                                        >
                                            <IconComponent />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        Copy setup key
                                    </TooltipContent>
                                </Tooltip>
                            </>
                        )}
                    </div>
                </>
            )}
        </>
    );
}
