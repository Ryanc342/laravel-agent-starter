import ClosingCall from '@/components/shared/closing-call';
import type { PublicLink } from '@/components/shared/public-header';
import FeatureGrid, { type Feature } from '@/components/shared/feature-grid';
import Hero from '@/components/shared/hero';
import PublicFrame from '@/components/style/public-frame';

/**
 * The home / landing page: the hero in the
 * display size, the feature grid and one closing line with a button, inside
 * the public layout. The text is example text; the real page is `/`.
 */
export default function LandingDemo({
    links,
    title,
    summary,
    primary,
    secondary,
    features,
    closing,
}: {
    links: PublicLink[];
    title: string;
    summary: string;
    primary: string;
    secondary: string;
    features: Feature[];
    closing: string;
}) {
    return (
        <PublicFrame name="Landing" links={links}>
            <Hero
                title={title}
                summary={summary}
                primary={{ label: primary, href: '#landing' }}
                secondary={{ label: secondary, href: '#landing' }}
            />
            <FeatureGrid features={features} />
            <ClosingCall
                text={closing}
                action={{ label: primary, href: '#landing' }}
            />
        </PublicFrame>
    );
}
