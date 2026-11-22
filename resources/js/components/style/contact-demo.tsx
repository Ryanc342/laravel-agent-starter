import Heading from '@/components/heading';
import ContactForm from '@/components/shared/contact-form';
import type { PublicLink } from '@/components/shared/public-header';
import PublicFrame from '@/components/style/public-frame';
import { DemoState } from '@/components/style/style-section';

/**
 * The contact page, in two fixed states: empty, and as the server sent it back
 * with what was typed and the errors (`typed` and `errors` are demo data). On
 * a real page the errors are also toasts and the first field with one gets the
 * focus.
 */
export default function ContactDemo({
    links,
    title,
    typed,
    errors,
}: {
    links: PublicLink[];
    title: string;
    typed: Record<string, string>;
    errors: Record<string, string>;
}) {
    return (
        <PublicFrame name="Contact" links={links}>
            <div className="grid gap-8">
                <Heading title={title} />
                <div className="grid gap-10 md:grid-cols-2">
                    <DemoState label="Empty">
                        <form noValidate onSubmit={(e) => e.preventDefault()}>
                            <ContactForm sending={false} />
                        </form>
                    </DemoState>
                    <DemoState label="Sent back with errors">
                        <form noValidate onSubmit={(e) => e.preventDefault()}>
                            <ContactForm
                                sending={false}
                                defaultValues={typed}
                                errors={errors}
                            />
                        </form>
                    </DemoState>
                </div>
            </div>
        </PublicFrame>
    );
}
