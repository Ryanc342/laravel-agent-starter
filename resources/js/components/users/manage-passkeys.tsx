import { router } from '@inertiajs/react';
import EmptyState from '@/components/shared/empty-state';
import Section from '@/components/shared/section';
import PasskeyItem from '@/components/users/passkey-item';
import PasskeyRegistration from '@/components/users/passkey-register';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: App.Http.Resources.PasskeyResource[];
};

export default function ManagePasskeys(props: Props) {
    const passkeys = props.passkeys ?? [];

    const handleRegisterSuccess = () => {
        router.reload();
    };

    if (!(props.canManagePasskeys ?? false)) {
        return null;
    }

    return (
        <Section title="Passkeys">
            {passkeys.length > 0 ? (
                <div className="overflow-hidden rounded-lg border border-border">
                    {passkeys.map((passkey) => (
                        <PasskeyItem key={passkey.id} passkey={passkey} />
                    ))}
                </div>
            ) : (
                <EmptyState message="You have no passkeys yet." />
            )}

            <PasskeyRegistration onSuccess={handleRegisterSuccess} />
        </Section>
    );
}
