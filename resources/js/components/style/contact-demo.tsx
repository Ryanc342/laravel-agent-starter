import { type FormEvent, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import ContactForm from '@/components/shared/contact-form';
import type { PublicLink } from '@/components/shared/public-header';
import PublicFrame from '@/components/style/public-frame';

/**
 * The contact page (docs/design/patterns/contact.md). A real page posts to the
 * server, which validates; here `answer` stands in for it and returns the
 * errors by field name. A failed send fires the same event Inertia fires, so
 * the real toasts and the focus on the first field with an error show.
 */
export default function ContactDemo({
    links,
    title,
    answer,
    sent,
}: {
    links: PublicLink[];
    title: string;
    answer: (values: Record<string, string>) => Record<string, string>;
    sent: string;
}) {
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [values, setValues] = useState<Record<string, string>>({});
    const [formKey, setFormKey] = useState(0);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const typed = Object.fromEntries(
            [...new FormData(event.currentTarget)].flatMap(([key, value]) =>
                typeof value === 'string' ? [[key, value]] : [],
            ),
        );
        const found = answer(typed);

        setErrors(found);
        setValues(typed);

        if (Object.keys(found).length > 0) {
            document.dispatchEvent(
                new CustomEvent('inertia:error', {
                    detail: { errors: found },
                }),
            );

            return;
        }

        toast.success(sent);
        setValues({});
        setFormKey(formKey + 1);
    };

    return (
        <PublicFrame links={links}>
            <div className="grid max-w-2xl gap-8">
                <Heading title={title} />
                <form key={formKey} onSubmit={submit} noValidate>
                    <ContactForm errors={errors} defaultValues={values} />
                </form>
            </div>
        </PublicFrame>
    );
}
