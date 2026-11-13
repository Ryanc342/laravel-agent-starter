import Heading from '@/components/heading';
import ReportChart, {
    type ChartSeries,
} from '@/components/shared/report-chart';
import ReportRange from '@/components/shared/report-range';

type Row = Record<string, string | number>;

/**
 * Numbers over time: the range in the header (7, 30 or 90 days, or two dates;
 * in the URL), a row of number tiles, then charts. One series is `chart-1`;
 * several take `chart-1` to `chart-5` in order and show a legend. The figures
 * are the backend's for the chosen range; the demo's do not change with it.
 */
export default function ReportDemo({
    tiles,
    daily,
    dailySeries,
    teams,
    teamSeries,
}: {
    tiles: { label: string; value: string }[];
    daily: Row[];
    dailySeries: ChartSeries[];
    teams: Row[];
    teamSeries: ChartSeries[];
}) {
    return (
        <div className="grid gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title="Task report" />
                <ReportRange />
            </div>

            <dl className="grid grid-cols-2 gap-4 md:grid-cols-4">
                {tiles.map((tile) => (
                    <div
                        key={tile.label}
                        className="grid gap-1 rounded-lg border p-4"
                    >
                        <dt className="text-sm text-muted-foreground">
                            {tile.label}
                        </dt>
                        <dd className="text-xl font-semibold tabular-nums">
                            {tile.value}
                        </dd>
                    </div>
                ))}
            </dl>

            <div className="grid gap-3">
                <Heading variant="small" title="Tasks done per day" />
                <div className="rounded-lg border p-4">
                    <ReportChart
                        data={daily}
                        xKey="day"
                        series={dailySeries}
                        ariaLabel="Tasks done per day"
                    />
                </div>
            </div>

            <div className="grid gap-3">
                <Heading variant="small" title="Tasks by team" />
                <div className="rounded-lg border p-4">
                    <ReportChart
                        kind="bar"
                        data={teams}
                        xKey="team"
                        series={teamSeries}
                        ariaLabel="Tasks by team"
                    />
                </div>
            </div>
        </div>
    );
}
