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
 * A record someone approves or rejects: Approve and Reject to the right of the
 * title with the other actions, the facts, and the history as a timeline.
 * Reject asks for a reason in a dialog. After a decision the buttons go and the
 * status changes (in a real page the backend sends both).
 */
export default function ApprovalDemo({
    title,
    facts,
    events,
}: {
    title: string;
    facts: { label: string; value: string }[];
    events: TimelineEvent[];
}) {
    const [history, setHistory] = useState(events);
    const [status, setStatus] = useState<'Pending' | 'Approved' | 'Rejected'>(
        'Pending',
    );
    const [reason, setReason] = useState('');
    const [open, setOpen] = useState(false);
    const [empty, setEmpty] = useState(false);

    const decide = (
        decision: 'Approved' | 'Rejected',
        note: string | null = null,
    ) => {
        setStatus(decision);
        setHistory([
            ...history,
            {
                id: history.length + 1,
                actor: 'You',
                action: decision.toLowerCase(),
                when: 'just now',
                note,
            },
        ]);
    };

    const reject = () => {
        if (!reason.trim()) {
            setEmpty(true);
            toast.error('Enter a reason.', { id: 'approval-reason' });

            return;
        }

        decide('Rejected', reason);
        setOpen(false);
        setReason('');
        setEmpty(false);
    };

    return (
        <div className="grid gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 flex-wrap items-start gap-2">
                    <Heading title={title} />
                    <Badge
                        variant={
                            status === 'Approved'
                                ? 'success'
                                : status === 'Rejected'
                                  ? 'destructive'
                                  : 'warning'
                        }
                        className="mt-1"
                    >
                        {status}
                    </Badge>
                </div>

                <div className="flex flex-wrap gap-2">
                    {status === 'Pending' && (
                        <>
                            <Button onClick={() => decide('Approved')}>
                                Approve
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setOpen(true)}
                            >
                                Reject
                            </Button>
                        </>
                    )}
                    <Button variant="outline">Edit request</Button>
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
                        error={empty ? 'Enter a reason.' : undefined}
                    >
                        <Input
                            id="approval-reason"
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                        />
                    </FormField>
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <Button variant="destructive" onClick={reject}>
                        Reject request
                    </Button>
                    <ResponsiveDialogClose asChild>
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </ResponsiveDialogClose>
                </ResponsiveDialogFooter>
            </ResponsiveDialog>
        </div>
    );
}
