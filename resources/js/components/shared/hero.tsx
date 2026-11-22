import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { NavItem } from '@/types';

type HeroButton = { label: string; href: NavItem['href'] };

/**
 * The opening of a landing page: the heading
 * in the display size, one sentence, and one or two buttons. Left-aligned, with
 * no picture and no background. On a phone the buttons stack at full width.
 */
export default function Hero({
    title,
    summary,
    primary,
    secondary,
}: {
    title: string;
    summary: string;
    primary: HeroButton;
    secondary?: HeroButton;
}) {
    return (
        <section className="grid max-w-3xl gap-6 py-8 md:py-16">
            <h1 className="text-display md:text-display-lg">{title}</h1>
            <p className="text-base text-muted-foreground">{summary}</p>
            <div className="flex flex-col gap-3 sm:flex-row">
                <Button asChild size="lg">
                    <Link href={primary.href}>{primary.label}</Link>
                </Button>
                {secondary && (
                    <Button asChild size="lg" variant="outline">
                        <Link href={secondary.href}>{secondary.label}</Link>
                    </Button>
                )}
            </div>
        </section>
    );
}
