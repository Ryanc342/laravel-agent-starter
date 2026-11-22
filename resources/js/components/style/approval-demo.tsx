import { useState } from 'react';
import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import DetailList, { type Detail } from '@/components/shared/detail-list';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import Timeline, { type TimelineEvent } from '@/components/shared/timeline';
import { DemoState } from '@/components/style/style-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

/**
 * A request as the backend sends it at one moment: its status label and badge,
 * the `can.*` flags for the buttons, and its history.
 */
export type ApprovalView = {
    moment: string;
    label: string;
    variant: 'success' | 'destructive' | 'warning';
    can: { approve: boolean; reject: boolean };
    events: TimelineEvent[];
};

/**
 * A record someone approves or rejects: the actions at the right end of the
 * header (Reject, then Approve last), the facts, and the history as a
 * timeline. Reject asks for a reason in a dialog. It shows fixed states
 * (`views`): waiting for a decision, approved, rejected. React decides
 * nothing: each state's label, badge, buttons and history are demo data.
 */
export default function ApprovalDemo({
    title,
    facts,
    views,
}: {
    title: string;
    facts: Detail[];
    views: ApprovalView[];
}) {
    const [rejecting, setRejecting] = useState(false);

    return (
        <div className="grid gap-10">
            {views.map((view) => (
                <DemoState key={view.moment} label={view.moment}>
                    <div className="grid gap-8">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="flex min-w-0 flex-wrap items-start gap-2">
                                <Heading title={title} />
                                <Badge variant={view.variant} className="mt-1">
                                    {view.label}
                                </Badge>
                            </div>

                            <Actions>
                                <Button variant="outline">Edit</Button>
                                {view.can.reject && (
                                    <Button
                                        variant="outline"
                                        onClick={() => setRejecting(true)}
                                    >
                                        Reject
                                    </Button>
                                )}
                                {view.can.approve && <Button>Approve</Button>}
                            </Actions>
                        </div>

                        <DetailList details={facts} />

                        <div className="grid gap-3">
                            <Heading variant="small" title="History" />
                            <Timeline events={view.events} />
                        </div>
                    </div>
                </DemoState>
            ))}

            <ResponsiveDialog
                open={rejecting}
                onOpenChange={setRejecting}
                title="Reject request"
            >
                <form
                    noValidate
                    onSubmit={(event) => {
                        event.preventDefault();
                        setRejecting(false);
                    }}
                    className="flex min-h-0 flex-1 flex-col gap-6"
                >
                    <ResponsiveDialogBody>
                        <TextField label="Reason" name="reason" />
                    </ResponsiveDialogBody>
                    <ResponsiveDialogFooter>
                        <ResponsiveDialogClose asChild>
                            <Button variant="outline">Cancel</Button>
                        </ResponsiveDialogClose>
                        <SubmitButton variant="destructive" sending={false}>
                            Reject
                        </SubmitButton>
                    </ResponsiveDialogFooter>
                </form>
            </ResponsiveDialog>
        </div>
    );
}
