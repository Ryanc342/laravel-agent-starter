import { ListFilter } from 'lucide-react';
import {
    clearedChanges,
    ColumnMenuContent,
    type MenuColumn,
} from '@/components/shared/column-menu';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useListQuery } from '@/hooks/use-list-query';
import { t } from '@/lib/i18n';
import type { ParamChange } from '@/lib/list-query';

/**
 * On a phone the table is cards with no header row, so the column menus move
 * into a sheet from the bottom: one section per column with a menu, and one
 * Clear that empties every sort, filter and search (rows per page stays).
 */
export default function SortFilterSheet({
    columns,
}: {
    columns: MenuColumn[];
}) {
    const { url, change } = useListQuery();
    const withMenu = columns.filter((column) => column.menu);

    if (withMenu.length === 0) {
        return null;
    }

    const clearAll = () => {
        const changes: Record<string, ParamChange> = {
            sort: null,
            direction: null,
            search: null,
        };

        for (const column of withMenu) {
            Object.assign(changes, clearedChanges(column.menu!, url));
        }

        change(changes);
    };

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button type="button" variant="outline">
                    <ListFilter />
                    {t('Sort & filter')}
                </Button>
            </SheetTrigger>
            <SheetContent side="bottom" className="max-h-5/6 overflow-y-auto">
                <SheetHeader>
                    <SheetTitle>{t('Sort & filter')}</SheetTitle>
                </SheetHeader>
                <div className="grid gap-6 px-6">
                    {withMenu.map((column) => (
                        <section
                            key={column.header}
                            aria-label={column.header}
                            className="grid gap-3"
                        >
                            <h3 className="text-sm font-medium">
                                {column.header}
                            </h3>
                            <div className="px-1">
                                <ColumnMenuContent
                                    header={column.header}
                                    menu={column.menu!}
                                    clearable={false}
                                />
                            </div>
                        </section>
                    ))}
                </div>
                <SheetFooter>
                    <Button type="button" variant="outline" onClick={clearAll}>
                        {t('Clear')}
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
