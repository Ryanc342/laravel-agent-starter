import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { t } from '@/lib/i18n';

export function Breadcrumbs({
    breadcrumbs,
}: {
    breadcrumbs: BreadcrumbItemType[];
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <>
            {breadcrumbs.length > 0 && (
                // One line always: only the current page shrinks and is cut
                // short, so a long title never pushes the way back out of view.
                <Breadcrumb className="min-w-0">
                    <BreadcrumbList className="flex-nowrap">
                        {breadcrumbs.map((item, index) => {
                            const isLast = index === breadcrumbs.length - 1;
                            // The last crumb is the page itself, unless it
                            // points elsewhere: a record's page ends at its
                            // list, and that crumb is the way back.
                            const isCurrent = isLast && isCurrentUrl(item.href);

                            return (
                                <Fragment key={index}>
                                    <BreadcrumbItem
                                        className={
                                            isCurrent ? 'min-w-0' : 'shrink-0'
                                        }
                                    >
                                        {isCurrent ? (
                                            <BreadcrumbPage className="block truncate">
                                                {t(item.title)}
                                            </BreadcrumbPage>
                                        ) : (
                                            <BreadcrumbLink asChild>
                                                <Link href={item.href}>
                                                    {t(item.title)}
                                                </Link>
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!isLast && <BreadcrumbSeparator />}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            )}
        </>
    );
}
