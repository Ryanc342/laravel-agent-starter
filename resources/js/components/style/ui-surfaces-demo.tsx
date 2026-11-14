import { MoreHorizontal } from 'lucide-react';
import { Specimen } from '@/components/style/style-section';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { t } from '@/lib/i18n';

/** What holds or reveals content: Table, Card, Avatar, Skeleton, Separator, and the layers (Popover, DropdownMenu, Sheet, Collapsible). */
/** Example data: what a user typed, shown as it is. */
const example = {
    task: 'Write the report',
    due: '17 Nov 2026',
    next: 'Book the venue',
    initials: 'TU',
};

export default function UiSurfacesDemo() {
    return (
        <div className="grid gap-8">
            <Specimen
                label={t(
                    'Table: horizontal row lines only (a list of records uses ResponsiveTable)',
                )}
            >
                <div className="w-full rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Title')}</TableHead>
                                <TableHead>{t('Due date')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell>{example.task}</TableCell>
                                <TableCell>{example.due}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>{example.next}</TableCell>
                                <TableCell>{t('No due date')}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Specimen>

            <Specimen
                label={t(
                    'Card: repeated items or a framed tool, never a card inside a card',
                )}
            >
                <Card className="w-full max-w-sm">
                    <CardHeader>
                        <CardTitle>{t('Card title')}</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm">
                        {t("The card's content.")}
                    </CardContent>
                </Card>
            </Specimen>

            <Specimen label={t('Avatar, Skeleton and Separator')}>
                <Avatar>
                    <AvatarFallback>{example.initials}</AvatarFallback>
                </Avatar>
                <Skeleton className="h-5 w-40" />
                <Separator className="w-24" />
            </Specimen>

            <Specimen
                label={t(
                    'Popover, DropdownMenu, Sheet and Collapsible: each opens from a button',
                )}
            >
                <Popover>
                    <PopoverTrigger asChild>
                        <Button variant="outline">{t('Popover')}</Button>
                    </PopoverTrigger>
                    <PopoverContent className="text-sm">
                        {t('A small card that opens from a button.')}
                    </PopoverContent>
                </Popover>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline">
                            <MoreHorizontal />
                            {t('Menu')}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuItem>{t('Edit')}</DropdownMenuItem>
                        <DropdownMenuItem>{t('Duplicate')}</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem>{t('Delete')}</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Sheet>
                    <SheetTrigger asChild>
                        <Button variant="outline">{t('Sheet')}</Button>
                    </SheetTrigger>
                    <SheetContent aria-describedby={undefined}>
                        <SheetHeader>
                            <SheetTitle>{t('Side panel')}</SheetTitle>
                        </SheetHeader>
                    </SheetContent>
                </Sheet>

                <Collapsible className="grid gap-2">
                    <CollapsibleTrigger asChild>
                        <Button variant="outline">{t('Collapsible')}</Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="text-sm text-muted-foreground">
                        {t('Details the user opens on demand.')}
                    </CollapsibleContent>
                </Collapsible>
            </Specimen>
        </div>
    );
}
