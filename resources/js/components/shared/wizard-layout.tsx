import { Check } from 'lucide-react';
import type { ReactNode } from 'react';
import Actions from '@/components/shared/actions';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

/**
 * A multi-step page: the step list on the left and the current step's fields
 * (and the Back / Next buttons, passed as `footer` and drawn as `Actions`, Back first and Next last) on the right. The steps
 * are not links; the user moves with the buttons. On a phone the list is one
 * line at the top ("Step 2 of 3 · Team"). `current` counts from 0.
 */
export default function WizardLayout({
    steps,
    current,
    instruction,
    footer,
    children,
}: {
    steps: string[];
    current: number;
    /** One sentence saying what to do, for a step the user must take; leave it out otherwise. */
    instruction?: string;
    footer: ReactNode;
    children: ReactNode;
}) {
    const isMobile = useIsMobile();

    return (
        <div className="grid gap-6 md:grid-cols-4 md:gap-10">
            {isMobile ? (
                <p className="text-sm font-medium">
                    {`Step ${current + 1} of ${steps.length} · ${steps[current]}`}
                </p>
            ) : (
                <ol className="grid content-start gap-1" aria-label="Steps">
                    {steps.map((step, index) => (
                        <li
                            key={step}
                            aria-current={
                                index === current ? 'step' : undefined
                            }
                            className={cn(
                                'flex h-10 items-center gap-3 rounded-md px-3 text-sm',
                                index === current
                                    ? 'bg-accent-pressed font-medium'
                                    : 'text-muted-foreground',
                            )}
                        >
                            <span className="flex size-5 shrink-0 items-center justify-center text-xs tabular-nums">
                                {index < current ? (
                                    <Check
                                        aria-label="Done"
                                        className="size-4 text-primary"
                                    />
                                ) : (
                                    index + 1
                                )}
                            </span>
                            <span className="min-w-0 truncate">{step}</span>
                        </li>
                    ))}
                </ol>
            )}
            <div className="grid min-w-0 content-start gap-6 md:col-span-3">
                {instruction && (
                    <p className="text-sm text-muted-foreground">
                        {instruction}
                    </p>
                )}
                {children}
                <Actions>{footer}</Actions>
            </div>
        </div>
    );
}
