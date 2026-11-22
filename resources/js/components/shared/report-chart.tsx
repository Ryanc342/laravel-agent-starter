import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    XAxis,
    YAxis,
} from 'recharts';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';

export type ChartSeries = { key: string; label: string };

/** One series is `chart-1`; several take `chart-1` to `chart-5` in this order. */
export function seriesColor(index: number): string {
    return `var(--chart-${index + 1})`;
}

/**
 * A chart of numbers over time or across categories: a line or a bar chart on
 * the theme's chart colours. Several series always show a legend; a single one
 * needs none. The exact figures are also in a table for screen readers. The
 * values arrive from the backend for the chosen range; nothing here adds up
 * or averages. The chart is drawn complete at once, with no animation, so
 * every look at it (and every screenshot) shows the same figures.
 */
export default function ReportChart({
    data,
    xKey,
    series,
    kind = 'line',
    ariaLabel,
    formatValue,
}: {
    data: Record<string, string | number>[];
    xKey: string;
    series: ChartSeries[];
    kind?: 'line' | 'bar';
    ariaLabel: string;
    /** Turns a figure into text (`formatMoney`, `formatQuantity` from `lib/format.ts`). */
    formatValue?: (value: number) => string;
}) {
    if (series.length > 5) {
        throw new Error(
            'A chart has at most five series (chart-1 to chart-5).',
        );
    }

    const config: ChartConfig = Object.fromEntries(
        series.map((item, index) => [
            item.key,
            { label: item.label, color: seriesColor(index) },
        ]),
    );
    const format = formatValue ?? ((value: number) => String(value));
    const Chart = kind === 'line' ? LineChart : BarChart;

    return (
        <div>
            <ChartContainer config={config} role="img" aria-label={ariaLabel}>
                <Chart data={data} margin={{ left: 0, right: 8, top: 8 }}>
                    <CartesianGrid vertical={false} />
                    <XAxis dataKey={xKey} tickLine={false} axisLine={false} />
                    <YAxis
                        tickLine={false}
                        axisLine={false}
                        width={48}
                        tickFormatter={(value: number) => format(value)}
                    />
                    <ChartTooltip
                        content={<ChartTooltipContent formatValue={format} />}
                    />
                    {series.length > 1 ? (
                        <ChartLegend content={<ChartLegendContent />} />
                    ) : null}
                    {series.map((item) =>
                        kind === 'line' ? (
                            <Line
                                key={item.key}
                                dataKey={item.key}
                                type="monotone"
                                stroke={`var(--color-${item.key})`}
                                strokeWidth={2}
                                dot={false}
                                isAnimationActive={false}
                            />
                        ) : (
                            <Bar
                                key={item.key}
                                dataKey={item.key}
                                fill={`var(--color-${item.key})`}
                                radius={4}
                                isAnimationActive={false}
                            />
                        ),
                    )}
                </Chart>
            </ChartContainer>
            <table className="sr-only">
                <caption>{ariaLabel}</caption>
                <thead>
                    <tr>
                        <th scope="col">{xKey}</th>
                        {series.map((item) => (
                            <th key={item.key} scope="col">
                                {item.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {data.map((row) => (
                        <tr key={String(row[xKey])}>
                            <th scope="row">{row[xKey]}</th>
                            {series.map((item) => (
                                <td key={item.key}>
                                    {format(Number(row[item.key]))}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
