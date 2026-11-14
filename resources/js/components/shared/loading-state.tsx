import { Skeleton } from '@/components/ui/skeleton';
import { useIsMobile } from '@/hooks/use-mobile';
import { t } from '@/lib/i18n';

/**
 * Data that arrives later: skeletons shaped like what is coming. A phone gets
 * cards, like the list it stands for (ResponsiveTable); desktop gets rows.
 * Inside a table or a box that already frames it, `framed={false}` draws bare
 * rows with no border of their own, on a phone too.
 */
export default function LoadingState({
    rows = 3,
    framed = true,
}: {
    rows?: number;
    framed?: boolean;
}) {
    const isMobile = useIsMobile() && framed;

    return (
        <div
            role="status"
            aria-busy="true"
            className={
                isMobile ? 'grid gap-3' : framed ? 'rounded-lg border' : ''
            }
        >
            <span className="sr-only">{t('Loading')}</span>
            {Array.from({ length: rows }, (_, index) =>
                isMobile ? (
                    <div
                        key={index}
                        data-slot="loading-card"
                        className="grid gap-3 rounded-lg border p-4"
                    >
                        <Skeleton className="h-5 w-2/3" />
                        <Skeleton className="h-4 w-1/3" />
                    </div>
                ) : (
                    <div
                        key={index}
                        data-slot="loading-row"
                        className="border-b p-4 last:border-b-0"
                    >
                        <Skeleton className="h-5 w-full" />
                    </div>
                ),
            )}
        </div>
    );
}
