import type { ReactNode } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useListQuery } from '@/hooks/use-list-query';
import { listParam } from '@/lib/list-query';

export type UrlTab = { value: string; label: string; content: ReactNode };

/**
 * The views of one page as pill tabs. The selected tab lives only in the URL
 * (`?tab=history`), like rows per page: a missing or unknown value shows the
 * first tab, and changing it keeps the page's other parameters.
 */
export default function UrlTabs({
    tabs,
    param = 'tab',
}: {
    tabs: UrlTab[];
    param?: string;
}) {
    const { url, change } = useListQuery();
    const asked = listParam(url, param);
    const selected = tabs.some((tab) => tab.value === asked)
        ? asked
        : tabs[0]?.value;

    return (
        <Tabs
            value={selected}
            onValueChange={(value) => change({ [param]: value })}
        >
            <TabsList>
                {tabs.map((tab) => (
                    <TabsTrigger key={tab.value} value={tab.value}>
                        {tab.label}
                    </TabsTrigger>
                ))}
            </TabsList>
            {tabs.map((tab) => (
                <TabsContent key={tab.value} value={tab.value}>
                    {tab.content}
                </TabsContent>
            ))}
        </Tabs>
    );
}
