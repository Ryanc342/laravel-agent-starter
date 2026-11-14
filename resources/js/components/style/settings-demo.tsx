import Heading from '@/components/heading';
import Actions from '@/components/shared/actions';
import FormField from '@/components/shared/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SettingsLayout from '@/layouts/settings/layout';
import { t } from '@/lib/i18n';

/**
 * A settings page: the settings layout (the real one, with its nav) around a
 * form of the user's own data. Every settings page uses the same layout.
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
            <div className="space-y-6">
                <Heading variant="small" title={t('Profile')} />

                <form
                    noValidate
                    onSubmit={(event) => event.preventDefault()}
                    className="space-y-6"
                >
                    <FormField label={t('Name')} htmlFor="settings-demo-name">
                        <Input id="settings-demo-name" defaultValue={name} />
                    </FormField>
                    <FormField
                        label={t('Email address')}
                        htmlFor="settings-demo-email"
                    >
                        <Input
                            id="settings-demo-email"
                            type="email"
                            defaultValue={email}
                        />
                    </FormField>
                    <Actions>
                        <Button type="submit">{t('Save')}</Button>
                    </Actions>
                </form>
            </div>
        </SettingsLayout>
    );
}
