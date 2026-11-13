import type { PublicLink } from '@/components/shared/public-header';
import PricingPlans, { type Plan } from '@/components/shared/pricing-plans';
import PublicFrame from '@/components/style/public-frame';
import TextLink from '@/components/text-link';

/**
 * The pricing page (docs/design/patterns/pricing.md): the title in the display
 * size, the billing toggle (in the URL) and the plans, then a link to the help
 * page. Prices are the backend's, sent as minor units with a currency.
 */
export default function PricingDemo({
    links,
    title,
    plans,
    helpLabel,
}: {
    links: PublicLink[];
    title: string;
    plans: Plan[];
    helpLabel: string;
}) {
    return (
        <PublicFrame links={links}>
            <div className="grid gap-8">
                <h1 className="text-display md:text-display-lg">{title}</h1>
                <PricingPlans plans={plans} />
                <p className="text-sm">
                    <TextLink href="#help">{helpLabel}</TextLink>
                </p>
            </div>
        </PublicFrame>
    );
}
