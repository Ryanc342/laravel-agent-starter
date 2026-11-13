import { Link } from '@inertiajs/react';
import type {
    PublicHeaderLabels,
    PublicLink,
} from '@/components/shared/public-header';
import FeatureGrid, { type Feature } from '@/components/shared/feature-grid';
import Hero from '@/components/shared/hero';
import PublicFrame from '@/components/style/public-frame';
import { Button } from '@/components/ui/button';

/**
 * The home / landing page (docs/design/patterns/landing.md): the hero in the
 * display size, the feature grid and one closing line with a button, inside
 * the public layout. The text is example text; the real page is `/`.
 */
export default function LandingDemo({
    links,
    barLabels,
    title,
    summary,
    primary,
    secondary,
    features,
    closing,
}: {
    links: PublicLink[];
    barLabels?: PublicHeaderLabels;
    title: string;
    summary: string;
    primary: string;
    secondary: string;
    features: Feature[];
    closing: string;
}) {
    return (
        <PublicFrame links={links} barLabels={barLabels}>
            <div className="grid gap-12 md:gap-16">
                <Hero
                    title={title}
                    summary={summary}
                    primary={{ label: primary, href: '#landing' }}
                    secondary={{ label: secondary, href: '#landing' }}
                />
                <FeatureGrid features={features} />
                <section className="flex flex-col gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-base font-medium">{closing}</p>
                    <Button asChild>
                        <Link href="#landing">{primary}</Link>
                    </Button>
                </section>
            </div>
        </PublicFrame>
    );
}
