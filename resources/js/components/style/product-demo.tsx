import DetailList from '@/components/shared/detail-list';
import { Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import type { PublicLink } from '@/components/shared/public-header';
import PublicFrame from '@/components/style/public-frame';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';

/**
 * A product or service page: the title and
 * one line, sections that alternate text and a neutral picture block (they
 * stack on a phone), the key facts as a list and one call to action. The
 * picture block is where the project's own picture goes.
 */
export default function ProductDemo({
    links,
    title,
    summary,
    sections,
    facts,
    price,
    action,
}: {
    links: PublicLink[];
    title: string;
    summary: string;
    sections: { title: string; text: string }[];
    facts: { label: string; value: string }[];
    price: { label: string; amount: number; currency: string };
    action: string;
}) {
    return (
        <PublicFrame name="Product" links={links}>
            <div className="grid gap-10">
                <div className="grid gap-2">
                    <Heading title={title} />
                    <p className="text-sm text-muted-foreground">{summary}</p>
                </div>

                {sections.map((section, index) => (
                    <section
                        key={section.title}
                        className="grid items-center gap-6 md:grid-cols-2 md:gap-10"
                    >
                        <div
                            className={cn(
                                'grid gap-2',
                                index % 2 === 1 && 'md:order-last',
                            )}
                        >
                            <Heading variant="small" title={section.title} />
                            <p className="text-sm text-muted-foreground">
                                {section.text}
                            </p>
                        </div>
                        <div
                            role="img"
                            aria-label="Picture"
                            className="aspect-video rounded-lg bg-muted"
                        />
                    </section>
                ))}

                <DetailList
                    layout="beside"
                    details={[
                        ...facts,
                        {
                            label: price.label,
                            value: formatMoney(price.amount, price.currency),
                        },
                    ]}
                />

                <div>
                    <Button asChild>
                        <Link href="#product">{action}</Link>
                    </Button>
                </div>
            </div>
        </PublicFrame>
    );
}
