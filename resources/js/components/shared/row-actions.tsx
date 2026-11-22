import { Ellipsis } from 'lucide-react';
import IconButton from '@/components/shared/icon-button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useRef } from 'react';

export type RowAction = {
    label: string;
    /** Runs the action; a destructive one opens its `ConfirmDialog` here. */
    onSelect: () => void;
    /** Deleting, removing: the last item, set apart, in the destructive colour. */
    destructive?: boolean;
};

/**
 * A row's own actions in a list whose records have no detail page
 *: one icon button with three dots that opens a menu. The
 * other actions keep their order; the destructive ones come last, after a line.
 */
export default function RowActions({ actions }: { actions: RowAction[] }) {
    // The chosen action runs once the menu has closed and focus is back on the
    // three-dot button, so a dialog it opens returns focus there when it closes.
    const chosen = useRef<(() => void) | null>(null);
    const trigger = useRef<HTMLButtonElement>(null);
    const others = actions.filter((action) => !action.destructive);
    const destructive = actions.filter((action) => action.destructive);

    return (
        <DropdownMenu>
            <IconButton
                ref={trigger}
                label="More actions"
                trigger={DropdownMenuTrigger}
                size="icon-sm"
                className="shrink-0"
            >
                <Ellipsis />
            </IconButton>
            <DropdownMenuContent
                align="end"
                onCloseAutoFocus={(event) => {
                    const run = chosen.current;
                    chosen.current = null;
                    event.preventDefault();
                    trigger.current?.focus();
                    run?.();
                }}
            >
                {others.map((action) => (
                    <DropdownMenuItem
                        key={action.label}
                        onSelect={() => {
                            chosen.current = action.onSelect;
                        }}
                    >
                        {action.label}
                    </DropdownMenuItem>
                ))}
                {others.length > 0 && destructive.length > 0 && (
                    <DropdownMenuSeparator />
                )}
                {destructive.map((action) => (
                    <DropdownMenuItem
                        key={action.label}
                        variant="destructive"
                        onSelect={() => {
                            chosen.current = action.onSelect;
                        }}
                    >
                        {action.label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
