import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                <div className="grid auto-rows-min gap-6 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-lg border border-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-lg border border-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-lg border border-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                    </div>
                </div>
                <div className="relative min-h-screen flex-1 overflow-hidden rounded-lg border border-border md:min-h-min">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
