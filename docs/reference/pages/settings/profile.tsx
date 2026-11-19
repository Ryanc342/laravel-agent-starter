import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/users/delete-user';
import Heading from '@/components/heading';
import FormField from '@/components/shared/form-field';
import TextLink from '@/components/text-link';
import Actions from '@/components/shared/actions';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';
import { msg, t } from '@/lib/i18n';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    emailNeedsVerification,
    verificationLinkSent,
    can,
}: {
    emailNeedsVerification: boolean;
    verificationLinkSent: boolean;
    can: {
        delete_account: boolean;
    };
}) {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title={t('Profile settings')} />

            <h1 className="sr-only">{t('Profile settings')}</h1>

            <div className="space-y-6">
                <Heading variant="small" title={t('Profile')} />

                <Form
                    noValidate
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormField
                                label={t('Name')}
                                htmlFor="name"
                                error={errors.name}
                            >
                                <Input
                                    id="name"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder={t('Full name')}
                                />
                            </FormField>

                            <FormField
                                label={t('Email address')}
                                htmlFor="email"
                                error={errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder={t('Email address')}
                                />
                            </FormField>

                            {emailNeedsVerification && (
                                <div>
                                    <p className="-mt-4 text-sm text-muted-foreground">
                                        {t('Your email address is unverified.')}{' '}
                                        <TextLink href={send()} as="button">
                                            {t(
                                                'Click here to re-send the verification email.',
                                            )}
                                        </TextLink>
                                    </p>

                                    {verificationLinkSent && (
                                        <div className="mt-2 text-sm font-medium text-success">
                                            {t(
                                                'A new verification link has been sent to your email address.',
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            <Actions>
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    {t('Save')}
                                </Button>
                            </Actions>
                        </>
                    )}
                </Form>
            </div>

            {can.delete_account && <DeleteUser />}
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: msg('Profile settings'),
            href: edit(),
        },
    ],
};
