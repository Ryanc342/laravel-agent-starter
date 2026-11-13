import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useListQuery } from '@/hooks/use-list-query';
import { formatMoney } from '@/lib/format';
import { listParam } from '@/lib/list-query';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

type Billing = 'monthly' | 'yearly';

export type Plan = {
    id: string;
    name: string;
    recommended: boolean;
    currency: string;
    /** In minor units (cents), as the backend sends them. */
    price: Record<Billing, number>;
    features: string[];
    action: { label: string; href: NavItem['href'] };
};

const periods: Record<Billing, string> = {
    monthly: '/ month',
    yearly: '/ year',
};

/**
 * Plans side by side (docs/design/patterns/pricing.md), with the billing choice
 * above them. The choice lives only in the URL (`?billing=yearly`; monthly is
 * the default and has no query), so a link shares it, and it only picks which
 * of the backend's two prices to show: nothing is calculated here. One plan is
 * recommended: a primary border and a badge. The plans stack on a phone.
 */
export default function PricingPlans({ plans }: { plans: Plan[] }) {
    const { url, change } = useListQuery();
    const billing: Billing =
        listParam(url, 'billing') === 'yearly' ? 'yearly' : 'monthly';

    return (
        <div className="grid gap-8">
            <ToggleGroup
                type="single"
                variant="outline"
                value={billing}
                onValueChange={(value) => {
                    // Clicking the selected one gives '': keep the choice.
                    if (value === 'monthly' || value === 'yearly') {
                        change({ billing: value === 'yearly' ? value : null });
                    }
                }}
                aria-label="Billing period"
                className="w-fit"
            >
                <ToggleGroupItem value="monthly">Monthly</ToggleGroupItem>
                <ToggleGroupItem value="yearly">Yearly</ToggleGroupItem>
            </ToggleGroup>

            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {plans.map((plan) => (
                    <Card
                        key={plan.id}
                        className={cn(
                            'content-start gap-6 p-6',
                            plan.recommended && 'border-primary',
                            // The recommended plan comes first on a phone.
                            plan.recommended && 'order-first md:order-none',
                        )}
                    >
                        <div className="flex items-center justify-between gap-2">
                            <Heading variant="small" title={plan.name} />
                            {plan.recommended && <Badge>Recommended</Badge>}
                        </div>
                        <p className="flex flex-wrap items-baseline gap-x-2">
                            <span className="text-display">
                                {formatMoney(
                                    plan.price[billing],
                                    plan.currency,
                                )}
                            </span>
                            <span className="text-sm text-muted-foreground">
                                {periods[billing]}
                            </span>
                        </p>
                        <ul className="grid gap-2 text-sm">
                            {plan.features.map((feature) => (
                                <li key={feature} className="flex gap-2">
                                    <Check
                                        aria-hidden="true"
                                        className="mt-0.5 size-4 shrink-0 text-primary"
                                    />
                                    <span className="min-w-0">{feature}</span>
                                </li>
                            ))}
                        </ul>
                        <Button
                            asChild
                            variant={plan.recommended ? 'default' : 'outline'}
                        >
                            <Link href={plan.action.href}>
                                {plan.action.label}
                            </Link>
                        </Button>
                    </Card>
                ))}
            </div>
        </div>
    );
}
