import { KeyRound } from 'lucide-react';
import ConfirmButton from '@/components/shared/confirm-button';
import { Badge } from '@/components/ui/badge';
import { destroy } from '@/routes/passkeys';

type Props = {
    passkey: App.Http.Resources.PasskeyResource;
};

export default function PasskeyItem({ passkey }: Props) {
    return (
        <div className="flex items-center justify-between border-b p-4 last:border-b-0">
            <div className="flex items-center gap-4">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                    <KeyRound className="h-5 w-5 text-muted-foreground" />
                </div>
                <div className="space-y-1">
                    <div className="flex items-center gap-2.5">
                        <p className="font-medium">{passkey.name}</p>
                        {passkey.authenticator && (
                            <Badge variant="secondary">
                                {passkey.authenticator}
                            </Badge>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {`Added ${passkey.created_at_diff}`}
                        {passkey.last_used_at_diff && (
                            <>
                                <span className="mx-1 text-muted-foreground/50">
                                    /
                                </span>
                                {`Last used ${passkey.last_used_at_diff}`}
                            </>
                        )}
                    </p>
                </div>
            </div>

            <ConfirmButton
                variant="outline"
                size="sm"
                form={destroy.form(passkey.id)}
                title="Delete this passkey?"
                consequence={`“${passkey.name}” will be deleted and can no longer be used to sign in.`}
            >
                Delete
            </ConfirmButton>
        </div>
    );
}
