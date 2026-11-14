import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import WizardLayout from '@/components/shared/wizard-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';

type Step = { name: string; fields: { id: string; label: string }[] };

/**
 * A multi-step wizard on its own page: the steps listed on the left (one line
 * on a phone), the current step's fields, Back and Next. "Next" sends the
 * fields of this step to `answer`, which returns an error message per field id
 * (a real page gets them from the server's FormRequest; here the page's
 * example data stands in for it). Nothing is saved until the last step.
 */
export default function WizardDemo({
    title,
    steps,
    submitLabel,
    answer,
}: {
    title: string;
    steps: Step[];
    submitLabel: string;
    answer: (
        fields: Step['fields'],
        values: Record<string, string>,
    ) => Record<string, string>;
}) {
    const [current, setCurrent] = useState(0);
    const [values, setValues] = useState<Record<string, string>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const step = steps[current];
    const last = current === steps.length - 1;

    const next = () => {
        const found = answer(step.fields, values);

        setErrors(found);

        if (Object.keys(found).length > 0) {
            Object.entries(found).forEach(([id, message]) =>
                toast.error(message, { id: `wizard-${id}` }),
            );

            return;
        }

        if (last) {
            toast.success(t('Team created.'));
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
                        {current > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setErrors({});
                                    setCurrent(current - 1);
                                }}
                            >
                                {t('Back')}
                            </Button>
                        )}
                        <Button onClick={next}>
                            {last ? t(submitLabel) : t('Next')}
                        </Button>
                    </>
                }
            >
                {step.fields.map((field) => (
                    <FormField
                        key={field.id}
                        label={t(field.label)}
                        htmlFor={`wizard-${field.id}`}
                        error={errors[field.id]}
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
