import Heading from '@/components/heading';
import EmptyState from '@/components/shared/empty-state';
import ErrorState from '@/components/shared/error-state';
import LoadingState from '@/components/shared/loading-state';
import { Button } from '@/components/ui/button';
import type { ReactNode } from 'react';

function Region({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div className="grid content-start gap-4 rounded-lg border p-4">
            <Heading variant="small" title={title} />
            {children}
        </div>
    );
}

/** A region with nothing in it, still loading, or that failed to load. `subject` is what the region lists. */
export default function StatesDemo({ subject }: { subject: string }) {
    return (
        <div className="grid gap-6 md:grid-cols-2">
            <Region title="Empty">
                <EmptyState message={`You have no ${subject} yet.`} />
            </Region>
            <Region title="Empty, with the create button">
                <EmptyState
                    message={`You have no ${subject} yet.`}
                    action={<Button>Add task</Button>}
                />
            </Region>
            <Region title="Loading">
                <LoadingState rows={3} />
            </Region>
            <Region title="Failed to load">
                <ErrorState
                    message={`Could not load your ${subject}.`}
                    onRetry={() => undefined}
                />
            </Region>
        </div>
    );
}
