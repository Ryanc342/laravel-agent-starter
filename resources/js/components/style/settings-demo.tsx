import Actions from '@/components/shared/actions';
import Section from '@/components/shared/section';
import StackedForm from '@/components/shared/stacked-form';
import SubmitButton from '@/components/shared/submit-button';
import TextField from '@/components/shared/text-field';
import SettingsLayout from '@/layouts/settings/layout';
import { style } from '@/routes';

/**
 * A settings page: the settings layout (the real one, with its nav) around a
 * form of the user's own data. Every settings page uses the same layout. The
 * demo's form goes nowhere: it stops before it is sent.
 */
export default function SettingsDemo({
    name,
    email,
}: {
    name: string;
    email: string;
}) {
    return (
        <SettingsLayout>
            <Section title="Profile">
                <StackedForm {...style.form()} onBefore={() => false}>
                    <TextField label="Name" name="name" defaultValue={name} />
                    <TextField
                        label="Email address"
                        name="email"
                        type="email"
                        defaultValue={email}
                    />
                    <Actions>
                        <SubmitButton>Save</SubmitButton>
                    </Actions>
                </StackedForm>
            </Section>
        </SettingsLayout>
    );
}
