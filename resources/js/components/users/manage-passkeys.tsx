import { router } from '@inertiajs/react';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import Heading from '@/components/heading';
import EmptyState from '@/components/shared/empty-state';
import PasskeyItem from '@/components/users/passkey-item';
import PasskeyRegistration from '@/components/users/passkey-register';
import type { Passkey } from '@/types/auth';
import type { ResourceCollection } from '@/types/resource';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: ResourceCollection<Passkey>;
};

export default function ManagePasskeys(props: Props) {
    const passkeys = props.passkeys?.data ?? [];

    const handleDelete = (id: number, onError: () => void) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            onError,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload();
    };

    if (!(props.canManagePasskeys ?? false)) {
        return null;
    }

    return (
        <div className="space-y-6">
            <Heading variant="small" title="Passkeys" />

            {passkeys.length > 0 ? (
                <div className="overflow-hidden rounded-lg border border-border">
                    {passkeys.map((passkey) => (
                        <PasskeyItem
                            key={passkey.id}
                            passkey={passkey}
                            onDelete={handleDelete}
                        />
                    ))}
                </div>
            ) : (
                <EmptyState message="You have no passkeys yet." />
            )}

            <PasskeyRegistration onSuccess={handleRegisterSuccess} />
        </div>
    );
}
