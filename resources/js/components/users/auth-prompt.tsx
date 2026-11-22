import type { InertiaLinkProps } from '@inertiajs/react';
import TextLink from '@/components/text-link';

/**
 * A centred line under a sign-in form's button that leads elsewhere: "Don't
 * have an account? Sign up", "Forgot your password?" (a link with no text
 * before it).
 */
export default function AuthPrompt({
    text,
    link,
    href,
}: {
    text?: string;
    link: string;
    href: NonNullable<InertiaLinkProps['href']>;
}) {
    return (
        <p className="text-center text-sm text-muted-foreground">
            {text && `${text} `}
            <TextLink href={href}>{link}</TextLink>
        </p>
    );
}
