import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import Timeline, { type TimelineEvent } from '@/components/shared/timeline';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/**
 * A record someone approves or rejects: the actions at the right end of the
 * header (Reject, then Approve last), the facts, and the history as a timeline.
 * Reject asks for a reason in a dialog. After a decision the buttons go and the
 * status changes. React decides nothing: the label, the badge variant and the
 * `can.*` flags come in with each state (`states`), and the reason is checked
 * by `answer`; on a real page the backend sends the new state and the
 * FormRequest's error, and here the page's example data stands in for it.
 */
export type ApprovalState = {
    label: string;
    variant: 'success' | 'destructive' | 'warning';
    can: { approve: boolean; reject: boolean };
};

export default function ApprovalDemo({
    title,
    facts,
    events,
    states,
    answer,
}: {
    title: string;
    facts: { label: string; value: string }[];
    events: TimelineEvent[];
    states: Record<'pending' | 'approved' | 'rejected', ApprovalState>;
    answer: (reason: string) => string | null;
}) {
    const [history, setHistory] = useState(events);
    const [state, setState] = useState<keyof typeof states>('pending');
    const view = states[state];
    const [reason, setReason] = useState('');
    const [open, setOpen] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const decide = (
        decision: 'approved' | 'rejected',
        note: string | null = null,
    ) => {
        setState(decision);
        setHistory([
            ...history,
            {
                id: history.length + 1,
                actor: 'You',
                action: states[decision].label.toLowerCase(),
                when: 'just now',
                note,
            },
        ]);
    };

    const reject = () => {
        const message = answer(reason);

        setError(message);

        if (message) {
            toast.error(message, { id: 'approval-reason' });

            return;
        }

        decide('rejected', reason);
        setOpen(false);
        setReason('');
    };

    return (
        <div className="grid gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 flex-wrap items-start gap-2">
                    <Heading title={title} />
                    <Badge variant={view.variant} className="mt-1">
                        {view.label}
                    </Badge>
                </div>

                <div
                    data-testid="record-actions"
                    className="ml-auto flex flex-wrap justify-end gap-2"
                >
                    <Button variant="outline">Edit request</Button>
                    {view.can.reject && (
                        <Button variant="outline" onClick={() => setOpen(true)}>
                            Reject
                        </Button>
                    )}
                    {view.can.approve && (
                        <Button onClick={() => decide('approved')}>
                            Approve
                        </Button>
                    )}
                </div>
            </div>

            <dl className="grid max-w-xl gap-1 text-sm">
                {facts.map((fact) => (
                    <div key={fact.label} className="grid gap-1 pb-3">
                        <dt className="text-muted-foreground">{fact.label}</dt>
                        <dd>{fact.value}</dd>
                    </div>
                ))}
            </dl>

            <div className="grid gap-3">
                <Heading variant="small" title="History" />
                <Timeline events={history} />
            </div>

            <ResponsiveDialog
                open={open}
                onOpenChange={setOpen}
                title="Reject request"
            >
                <ResponsiveDialogBody>
                    <FormField
                        label="Reason"
                        htmlFor="approval-reason"
                        error={error ?? undefined}
                    >
                        <Input
                            id="approval-reason"
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                        />
                    </FormField>
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose asChild>
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </ResponsiveDialogClose>
                    <Button variant="destructive" onClick={reject}>
                        Reject request
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialog>
        </div>
    );
}
