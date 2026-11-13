import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * The bar that floats at the bottom of a list once rows are ticked, visible
 * while the user scrolls: "2 selected · Complete · Delete · ✕". The actions are
 * the caller's buttons (the main one `default`, the others `outline`). When a
 * whole page is ticked and more results exist, the caller passes `total` and
 * `onSelectAllResults` and the bar offers "Select all N results"; once every
 * result is selected it says "All N selected". Nothing shows with no rows ticked.
 * Place it last on the page: it leaves room under itself so it never hides the end.
 */
export default function BulkActionBar({
    count,
    allResults = false,
    total,
    onSelectAllResults,
    onClear,
    children,
}: {
    count: number;
    allResults?: boolean;
    total?: number;
    onSelectAllResults?: () => void;
    onClear: () => void;
    children: ReactNode;
}) {
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
                    aria-label="Bulk actions"
                    data-slot="bulk-action-bar"
                    className="pointer-events-auto flex w-full max-w-full items-center gap-2 rounded-lg border bg-popover px-3 py-2 text-popover-foreground shadow-md sm:w-auto sm:flex-wrap sm:gap-3 sm:px-4"
                >
                    <span className="text-sm font-medium whitespace-nowrap tabular-nums">
                        {allResults && total !== undefined
                            ? `All ${total} selected`
                            : `${count} selected`}
                    </span>
                    {!allResults &&
                    onSelectAllResults &&
                    total !== undefined ? (
                        <Button
                            variant="link"
                            size="inline"
                            onClick={onSelectAllResults}
                        >
                            Select all {total} results
                        </Button>
                    ) : null}
                    <div className="flex flex-1 items-center gap-2 sm:flex-none">
                        {children}
                    </div>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Clear selection"
                                onClick={onClear}
                            >
                                <X />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Clear selection</TooltipContent>
                    </Tooltip>
                </div>
            </div>
        </>
    );
}
