import type { PublicLink } from '@/components/shared/public-header';
import PricingPlans, { type Plan } from '@/components/shared/pricing-plans';
import PublicFrame from '@/components/style/public-frame';
import TextLink from '@/components/text-link';

/**
 * The pricing page: the title (drawn by
 * `PricingPlans`), the billing toggle (in the URL) and the plans, then a link to the help
 * page. Prices are the backend's, sent as minor units with a currency.
 */
export default function PricingDemo({
    links,
    title,
    plans,
    labels,
    helpLabel,
}: {
    links: PublicLink[];
    title: string;
    plans: Plan[];
    labels: React.ComponentProps<typeof PricingPlans>['labels'];
    helpLabel: string;
}) {
    return (
        <PublicFrame name="Pricing" links={links}>
            <div className="grid gap-8">
                <PricingPlans title={title} plans={plans} labels={labels} />
                <p className="text-sm">
                    <TextLink href="#help">{helpLabel}</TextLink>
                </p>
            </div>
        </PublicFrame>
    );
}
