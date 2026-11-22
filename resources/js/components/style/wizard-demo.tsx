import Heading from '@/components/heading';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import WizardLayout from '@/components/shared/wizard-layout';
import { DemoState } from '@/components/style/style-section';
import { Button } from '@/components/ui/button';

type Step = { name: string; fields: { id: string; label: string }[] };

/** One fixed state of the wizard: the step shown, what is typed, and the server's errors. */
export type WizardState = {
    label: string;
    step: number;
    values: Record<string, string>;
    errors: Record<string, string>;
};

/**
 * A multi-step wizard on its own page: the steps listed on the left (one line
 * on a phone), the current step's fields, Back and Next. It shows fixed states
 * (`states`): a step as it opens, a step the server sent back with errors, the
 * last step. Nothing is saved until the last step.
 */
export default function WizardDemo({
    title,
    steps,
    states,
}: {
    title: string;
    steps: Step[];
    states: WizardState[];
}) {
    return (
        <div className="grid gap-10">
            <Heading title={title} />
            {states.map((state) => (
                <DemoState key={state.label} label={state.label}>
                    <WizardLayout
                        steps={steps.map((item) => item.name)}
                        current={state.step}
                        footer={
                            <>
                                {state.step > 0 && (
                                    <Button variant="outline">Back</Button>
                                )}
                                {state.step < steps.length - 1 ? (
                                    <Button>Next</Button>
                                ) : (
                                    <SubmitButton sending={false}>
                                        Create
                                    </SubmitButton>
                                )}
                            </>
                        }
                    >
                        {steps[state.step].fields.map((field) => (
                            <TextField
                                key={field.id}
                                label={field.label}
                                defaultValue={state.values[field.id]}
                                error={state.errors[field.id]}
                            />
                        ))}
                    </WizardLayout>
                </DemoState>
            ))}
        </div>
    );
}
