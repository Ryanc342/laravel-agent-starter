import type { LucideIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { t } from '@/lib/i18n';

export type Feature = {
    icon: LucideIcon;
    title: string;
    description: string;
};

/**
 * What a product offers (docs/design/patterns/landing.md): an icon, a title and
 * one line per feature, in three columns on a computer and one on a phone. No
 * card around a feature.
 */
export default function FeatureGrid({ features }: { features: Feature[] }) {
    return (
        <ul className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            {features.map((feature) => (
                <li key={feature.title} className="grid content-start gap-2">
                    <feature.icon className="size-6 text-primary" />
                    <Heading variant="small" title={t(feature.title)} />
                    <p className="text-sm text-muted-foreground">
                        {t(feature.description)}
                    </p>
                </li>
            ))}
        </ul>
    );
}
