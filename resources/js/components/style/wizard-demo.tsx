import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import WizardLayout from '@/components/shared/wizard-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Step = { name: string; fields: { id: string; label: string }[] };

/**
 * A multi-step wizard on its own page: the steps listed on the left (one line
 * on a phone), the current step's fields, Back and Next. "Next" checks the
 * fields of this step: here by the demo, in a real page by the server. Nothing
 * is saved until the last step.
 */
export default function WizardDemo({
    title,
    steps,
    submitLabel,
}: {
    title: string;
    steps: Step[];
    submitLabel: string;
}) {
    const [current, setCurrent] = useState(0);
    const [values, setValues] = useState<Record<string, string>>({});
    const [invalid, setInvalid] = useState<string[]>([]);
    const step = steps[current];
    const last = current === steps.length - 1;

    const next = () => {
        const missing = step.fields.filter(
            (field) => !values[field.id]?.trim(),
        );

        setInvalid(missing.map((field) => field.id));

        if (missing.length > 0) {
            missing.forEach((field) =>
                toast.error(`Enter ${field.label.toLowerCase()}.`, {
                    id: `wizard-${field.id}`,
                }),
            );

            return;
        }

        if (last) {
            toast.success('Team created.');
            setCurrent(0);
            setValues({});

            return;
        }

        setCurrent(current + 1);
    };

    return (
        <div className="grid gap-6">
            <Heading title={title} />
            <WizardLayout
                steps={steps.map((item) => item.name)}
                current={current}
                footer={
                    <>
                        <Button onClick={next}>
                            {last ? submitLabel : 'Next'}
                        </Button>
                        {current > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setInvalid([]);
                                    setCurrent(current - 1);
                                }}
                            >
                                Back
                            </Button>
                        )}
                    </>
                }
            >
                {step.fields.map((field) => (
                    <FormField
                        key={field.id}
                        label={field.label}
                        htmlFor={`wizard-${field.id}`}
                        error={
                            invalid.includes(field.id)
                                ? `Enter ${field.label.toLowerCase()}.`
                                : undefined
                        }
                    >
                        <Input
                            id={`wizard-${field.id}`}
                            value={values[field.id] ?? ''}
                            onChange={(event) =>
                                setValues({
                                    ...values,
                                    [field.id]: event.target.value,
                                })
                            }
                        />
                    </FormField>
                ))}
            </WizardLayout>
        </div>
    );
}
