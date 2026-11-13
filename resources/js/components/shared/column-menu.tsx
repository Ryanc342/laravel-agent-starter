import { ArrowDown, ArrowUp, Filter } from 'lucide-react';
import { useId } from 'react';
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

/**
 * Whether this column's own controls show a choice: text in its search box, a
 * ticked option the tick list offers, an end of its range. The funnel mirrors
 * what the menu displays; what the backend applies is its own decision.
 */
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
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Input id={id} type={type} {...inputProps} />
        </div>
    );
}

function TickList({ param, choices }: { param: string; choices: Choice[] }) {
    const { url, change } = useListQuery();
    const ticked = listParams(url, param);
    const id = useId();

    return (
        <div className="grid gap-2">
            <div className="flex gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                        change({
                            [param]: choices.map((choice) => choice.value),
                        })
                    }
                >
                    Select all
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => change({ [param]: [] })}
                >
                    Unselect all
                </Button>
            </div>
            {choices.map((choice) => (
                <div key={choice.value} className="flex items-center gap-2">
                    <Checkbox
                        id={`${id}-${choice.value}`}
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
                    <Label htmlFor={`${id}-${choice.value}`}>
                        {choice.label}
                    </Label>
                </div>
            ))}
        </div>
    );
}

/**
 * The choices of one column: sort, a search inside it, a tick list or a
 * from-to range, as its config says. Used by the header's popover and, on a
 * phone, by the "Sort & filter" sheet (which has one Clear for all columns).
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

    return (
        <div className="grid gap-4">
            {menu.sortKey && (
                <div className="grid gap-1">
                    {(['asc', 'desc'] as const).map((way) => (
                        <Button
                            key={way}
                            type="button"
                            variant="ghost"
                            className="justify-start"
                            aria-pressed={sorted && direction === way}
                            onClick={() =>
                                change({ sort: menu.sortKey!, direction: way })
                            }
                        >
                            {way === 'asc' ? <ArrowUp /> : <ArrowDown />}
                            {way === 'asc'
                                ? 'Sort ascending'
                                : 'Sort descending'}
                        </Button>
                    ))}
                </div>
            )}
            {menu.search && (
                <UrlSearchInput
                    param={menu.search.param}
                    aria-label={`Search ${header}`}
                    placeholder={`Search ${header.toLowerCase()}`}
                />
            )}
            {menu.options && (
                <TickList
                    param={menu.options.param}
                    choices={menu.options.choices}
                />
            )}
            {menu.range && (
                <div className="grid grid-cols-2 gap-3">
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
                </div>
            )}
            {clearable && (
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => change(clearedChanges(menu, url))}
                >
                    Clear
                </Button>
            )}
        </div>
    );
}

/**
 * A table header that opens its column's menu. A sorted column shows an arrow
 * and a filtered one a funnel, both in the primary colour; a column that is
 * neither looks like plain text.
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
            <PopoverContent align="start">
                <ColumnMenuContent header={header} menu={menu} />
            </PopoverContent>
        </Popover>
    );
}
