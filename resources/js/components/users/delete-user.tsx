import Actions from '@/components/shared/actions';
import ConfirmButton from '@/components/shared/confirm-button';
import PasswordField from '@/components/shared/password-field';
import Section from '@/components/shared/section';
import { destroy } from '@/routes/profile';

export default function DeleteUser() {
    return (
        <Section title="Delete account">
            <Actions>
                <ConfirmButton
                    variant="destructive"
                    form={destroy.form()}
                    title="Delete your account?"
                    consequence="Your account and all of its data will be deleted. This cannot be undone. Enter your password to confirm."
                    fields={(errors) => (
                        <PasswordField
                            label="Password"
                            id="password"
                            name="password"
                            autoComplete="current-password"
                            error={errors.password}
                        />
                    )}
                >
                    Delete
                </ConfirmButton>
            </Actions>
        </Section>
    );
}
