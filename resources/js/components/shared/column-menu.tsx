import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Filter,
    ListChecks,
    ListX,
    X,
} from 'lucide-react';
import { Fragment, useId, type ComponentProps, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import UrlSearchInput from '@/components/shared/url-search-input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { useListQuery } from '@/hooks/use-list-query';
import { useUrlParam } from '@/hooks/use-url-param';
import { listParam, listParams, type ParamChange } from '@/lib/list-query';
import { cn } from '@/lib/utils';

export type Choice = { value: string; label: string };

/** What a column's menu offers; each part is optional. The names are URL parameters. */
export type ColumnMenuConfig = {
    /** The value of `?sort=` for this column; without it the menu has no sort. */
    sortKey?: string;
    /** A search inside the column (the list's `search` parameter). */
    search?: { param: string };
    /** A tick list of fixed options; the choices come from the backend. */
    options?: { param: string; choices: Choice[] };
    /** A from-to range; both ends are included by the backend. */
    range?: { fromParam: string; toParam: string; type: 'date' | 'number' };
};

/** A column that may have a menu: its header text and what the menu offers. */
export type MenuColumn = { header: string; menu?: ColumnMenuConfig };

/** The URL parameters a column's menu owns: clearing the column empties them. */
export function menuParams(menu: ColumnMenuConfig): string[] {
    return [
        menu.search?.param,
        menu.options?.param,
        menu.range?.fromParam,
        menu.range?.toParam,
    ].filter((param): param is string => Boolean(param));
}

/** Everything one column's menu changes in the address, emptied; the sort only if it is this column's. */
export function clearedChanges(
    menu: ColumnMenuConfig,
    url: string,
): Record<string, ParamChange> {
    const changes: Record<string, ParamChange> = {};

    if (menu.sortKey && listParam(url, 'sort') === menu.sortKey) {
        changes.sort = null;
        changes.direction = null;
    }

    for (const param of menuParams(menu)) {
        changes[param] = null;
    }

    return changes;
}

/** Whether the list is sorted by this column, and which way. */
function sortState(
    menu: ColumnMenuConfig,
    url: string,
): { sorted: boolean; direction: 'asc' | 'desc' } {
    return {
        sorted:
            Boolean(menu.sortKey) && listParam(url, 'sort') === menu.sortKey,
        direction: listParam(url, 'direction') === 'desc' ? 'desc' : 'asc',
    };
}

/**
 * Whether this column's own controls show a choice: text in its search box, a
 * ticked option the tick list offers, an end of its range. The funnel mirrors
 * what the menu displays; what the backend applies is its own decision.
 */
function isFiltered(menu: ColumnMenuConfig, url: string): boolean {
    return (
        Boolean(
            menu.search && listParam(url, menu.search.param).trim() !== '',
        ) ||
        Boolean(
            menu.options &&
            listParams(url, menu.options.param).some((value) =>
                menu.options!.choices.some((choice) => choice.value === value),
            ),
        ) ||
        Boolean(
            menu.range &&
            (listParam(url, menu.range.fromParam) ||
                listParam(url, menu.range.toParam)),
        )
    );
}

/**
 * The look of every choice in the menu, the same as a dropdown menu's item:
 * one height, one left edge, a 16px icon slot, grey on hover, darker on press.
 * The grey of the icon is `MenuRow`'s: a tick row's check mark keeps the
 * checkbox's own colour.
 */
const rowClass =
    "flex w-full cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-hidden select-none hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent active:bg-accent-pressed [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4";

/** One choice of the menu that does something when clicked. */
function MenuRow({
    selected = false,
    className,
    ...props
}: ComponentProps<'button'> & { selected?: boolean }) {
    return (
        <button
            type="button"
            data-slot="column-menu-row"
            className={cn(
                rowClass,
                "[&_svg:not([class*='text-'])]:text-muted-foreground",
                selected && 'bg-accent-pressed hover:bg-accent-pressed',
                className,
            )}
            {...props}
        />
    );
}

/** A row that holds a box instead of a click: the left padding is the same as the other rows'. */
function InputRow({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            data-slot="column-menu-row"
            className={cn('flex items-center gap-2 px-2 py-1', className)}
            {...props}
        />
    );
}

/** The thin line between two groups, edge to edge of the popover (which pads by `p-1`). */
function MenuLine() {
    return (
        <Separator className="-mx-1 my-1 data-[orientation=horizontal]:w-auto" />
    );
}

function RangeEnd({
    label,
    param,
    type,
}: {
    label: string;
    param: string;
    type: 'date' | 'number';
}) {
    const id = useId();
    // A picked date asks at once; a typed amount waits for a pause.
    const { inputProps } = useUrlParam(param, type === 'date' ? 0 : 400);

    return (
        <InputRow>
            <Label htmlFor={id} className="w-10 shrink-0 font-normal">
                {label}
            </Label>
            <Input id={id} type={type} className="h-8" {...inputProps} />
        </InputRow>
    );
}

function TickList({ param, choices }: { param: string; choices: Choice[] }) {
    const { url, change } = useListQuery();
    const ticked = listParams(url, param);
    const id = useId();

    return (
        <>
            <div className="grid">
                <MenuRow
                    onClick={() =>
                        change({
                            [param]: choices.map((choice) => choice.value),
                        })
                    }
                >
                    <ListChecks />
                    Select all
                </MenuRow>
                <MenuRow onClick={() => change({ [param]: [] })}>
                    <ListX />
                    Unselect all
                </MenuRow>
            </div>
            <MenuLine />
            <div className="grid">
                {choices.map((choice) => (
                    <label
                        key={choice.value}
                        htmlFor={`${id}-${choice.value}`}
                        data-slot="column-menu-row"
                        className={cn(rowClass, 'cursor-pointer')}
                    >
                        <Checkbox
                            id={`${id}-${choice.value}`}
                            className="size-4"
                            checked={ticked.includes(choice.value)}
                            onCheckedChange={(checked) =>
                                change({
                                    [param]: checked
                                        ? [...ticked, choice.value]
                                        : ticked.filter(
                                              (value) => value !== choice.value,
                                          ),
                                })
                            }
                        />
                        {choice.label}
                    </label>
                ))}
            </div>
        </>
    );
}

/**
 * The choices of one column as one column of rows: sort, a search inside it, a
 * tick list or a from-to range as its config says, and Clear, with a thin line
 * between the groups. Used by the header's popover and, on a phone, by the
 * "Sort & filter" sheet (which has one Clear for all columns).
 */
export function ColumnMenuContent({
    header,
    menu,
    clearable = true,
}: {
    header: string;
    menu: ColumnMenuConfig;
    clearable?: boolean;
}) {
    const { url, change } = useListQuery();
    const { sorted, direction } = sortState(menu, url);
    const groups: ReactNode[] = [];

    if (menu.sortKey) {
        groups.push(
            <div key="sort" className="grid">
                {(['asc', 'desc'] as const).map((way) => (
                    <MenuRow
                        key={way}
                        selected={sorted && direction === way}
                        aria-pressed={sorted && direction === way}
                        onClick={() =>
                            change({ sort: menu.sortKey!, direction: way })
                        }
                    >
                        {way === 'asc' ? <ArrowUp /> : <ArrowDown />}
                        {way === 'asc' ? 'Sort ascending' : 'Sort descending'}
                    </MenuRow>
                ))}
            </div>,
        );
    }

    if (menu.search || menu.options || menu.range) {
        groups.push(
            <div key="filter" className="grid">
                {menu.search && (
                    <InputRow>
                        <UrlSearchInput
                            param={menu.search.param}
                            aria-label={`Search ${header}`}
                            placeholder={`Search ${header.toLowerCase()}`}
                            className="h-8"
                        />
                    </InputRow>
                )}
                {menu.options && (
                    <TickList
                        param={menu.options.param}
                        choices={menu.options.choices}
                    />
                )}
                {menu.range && (
                    <>
                        <RangeEnd
                            label="From"
                            param={menu.range.fromParam}
                            type={menu.range.type}
                        />
                        <RangeEnd
                            label="To"
                            param={menu.range.toParam}
                            type={menu.range.type}
                        />
                    </>
                )}
            </div>,
        );
    }

    if (clearable) {
        groups.push(
            <MenuRow
                key="clear"
                onClick={() => change(clearedChanges(menu, url))}
            >
                <X />
                Clear
            </MenuRow>,
        );
    }

    return (
        <div className="grid">
            {groups.map((group, index) => (
                <Fragment key={index}>
                    {index > 0 && <MenuLine />}
                    {group}
                </Fragment>
            ))}
        </div>
    );
}

/**
 * A table header that opens its column's menu. It always shows a faint grey
 * up-down arrow, so the user sees it can be clicked; a sorted column shows an
 * up or down arrow instead and a filtered one adds a funnel, both in the
 * primary colour. A column without a menu is plain text (the caller decides).
 */
export default function ColumnMenu({
    header,
    menu,
}: {
    header: string;
    menu: ColumnMenuConfig;
}) {
    const { url } = useListQuery();
    const { sorted, direction } = sortState(menu, url);
    const filtered = isFiltered(menu, url);

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="-ml-3 font-medium"
                >
                    {header}
                    {!sorted && (
                        <ArrowUpDown
                            data-slot="sortable-indicator"
                            aria-hidden="true"
                            className="text-muted-foreground/60"
                        />
                    )}
                    {sorted &&
                        (direction === 'asc' ? (
                            <ArrowUp
                                data-slot="sort-indicator"
                                aria-hidden="true"
                                className="text-primary"
                            />
                        ) : (
                            <ArrowDown
                                data-slot="sort-indicator"
                                aria-hidden="true"
                                className="text-primary"
                            />
                        ))}
                    {filtered && (
                        <Filter
                            data-slot="filter-indicator"
                            aria-hidden="true"
                            className={cn('text-primary')}
                        />
                    )}
                    {sorted && (
                        <span className="sr-only">
                            , sorted{' '}
                            {direction === 'asc' ? 'ascending' : 'descending'}
                        </span>
                    )}
                    {filtered && <span className="sr-only">, filtered</span>}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="start" className="p-1">
                <ColumnMenuContent header={header} menu={menu} />
            </PopoverContent>
        </Popover>
    );
}
