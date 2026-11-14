import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * The bar that floats at the bottom of a list once rows are ticked, visible
 * while the user scrolls: "2 selected · Complete · Delete · ✕". The actions are
 * the caller's buttons (the main one `default`, the others `outline`). When a
 * whole page is ticked and more results exist, the caller passes `total` and
 * `onSelectAllResults` and the bar offers "Select all N results"; once every
 * result is selected it says "All N selected". On a phone the cards have no
 * header checkbox, so the caller also passes `onSelectPage` (while the page is
 * not fully ticked) and the bar offers "Select this page" first. Nothing shows
 * with no rows ticked.
 * Place it last on the page: it leaves room under itself so it never hides the end.
 */
export default function BulkActionBar({
    count,
    allResults = false,
    total,
    onSelectPage,
    onSelectAllResults,
    onClear,
    children,
}: {
    count: number;
    allResults?: boolean;
    total?: number;
    onSelectPage?: () => void;
    onSelectAllResults?: () => void;
    onClear: () => void;
    children: ReactNode;
}) {
    const isMobile = useIsMobile();
    const offersPage = isMobile && !allResults && onSelectPage !== undefined;
    const offersAll =
        !allResults && onSelectAllResults !== undefined && total !== undefined;

    if (count === 0) {
        return null;
    }

    return (
        <>
            {/* Room at the end of the page, so the bar never covers the last rows or the pagination. */}
            <div aria-hidden className="h-20" />
            <div className="pointer-events-none fixed inset-x-0 bottom-4 z-40 flex justify-center px-4">
                <div
                    role="region"
                    aria-label={t('Bulk actions')}
                    data-slot="bulk-action-bar"
                    className="pointer-events-auto flex w-full max-w-full flex-wrap items-center gap-2 rounded-lg border bg-popover px-3 py-2 text-popover-foreground shadow-md sm:w-auto sm:gap-3 sm:px-4"
                >
                    {/* With a link, or the longer "All N selected", the count and the link take the first line on a phone and the buttons the second. */}
                    <div
                        className={cn(
                            'flex items-center gap-3',
                            (offersPage || offersAll || allResults) &&
                                'w-full sm:w-auto',
                        )}
                    >
                        <span className="text-sm font-medium whitespace-nowrap tabular-nums">
                            {allResults && total !== undefined
                                ? t('All :total selected', { total })
                                : t(':count selected', { count })}
                        </span>
                        {offersPage ? (
                            <Button
                                variant="link"
                                size="inline"
                                onClick={onSelectPage}
                            >
                                {t('Select this page')}
                            </Button>
                        ) : null}
                        {offersAll ? (
                            <Button
                                variant="link"
                                size="inline"
                                onClick={onSelectAllResults}
                            >
                                {t('Select all :total results', { total })}
                            </Button>
                        ) : null}
                    </div>
                    <div className="flex flex-1 items-center gap-2 sm:flex-none">
                        {children}
                    </div>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={t('Clear selection')}
                                onClick={onClear}
                            >
                                <X />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>{t('Clear selection')}</TooltipContent>
                    </Tooltip>
                </div>
            </div>
        </>
    );
}
