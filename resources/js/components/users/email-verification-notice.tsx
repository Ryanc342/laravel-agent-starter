import TextLink from '@/components/text-link';
import { send } from '@/routes/verification';

/**
 * Under the email field of the profile, while the address is not verified: a
 * link that sends the verification email again, and the line saying it was
 * sent.
 */
export default function EmailVerificationNotice({
    linkSent,
}: {
    linkSent: boolean;
}) {
    return (
        <div className="grid gap-2 text-sm">
            <p className="text-muted-foreground">
                Your email address is unverified.{' '}
                <TextLink href={send()} as="button" type="button">
                    Send the verification email again.
                </TextLink>
            </p>
            {linkSent && (
                <p role="status" className="font-medium text-success">
                    A new verification link has been sent to your email address.
                </p>
            )}
        </div>
    );
}
