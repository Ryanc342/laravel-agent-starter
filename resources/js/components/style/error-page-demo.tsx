import ErrorContent from '@/components/shared/error-content';
import type { ErrorStatus } from '@/components/shared/error-content';

/** The four error pages side by side. The real page (pages/errors/show.tsx) puts the same content in a full-height column. */
export default function ErrorPageDemo({
    examples,
}: {
    examples: { status: ErrorStatus; signedIn: boolean }[];
}) {
    return (
        <div className="grid gap-6 md:grid-cols-2">
            {examples.map((example) => (
                <div
                    key={example.status}
                    className="rounded-lg border p-6 md:p-10"
                >
                    <ErrorContent {...example} />
                </div>
            ))}
        </div>
    );
}
