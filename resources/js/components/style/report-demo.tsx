import Heading from '@/components/heading';
import ReportChart, {
    type ChartSeries,
} from '@/components/shared/report-chart';
import ReportRange from '@/components/shared/report-range';
import StatTiles, { type StatTile } from '@/components/shared/stat-tiles';
import { t } from '@/lib/i18n';

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
    tiles: StatTile[];
    daily: Row[];
    dailySeries: ChartSeries[];
    teams: Row[];
    teamSeries: ChartSeries[];
}) {
    return (
        <div className="grid gap-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading title={t('Task report')} />
                <ReportRange />
            </div>

            <StatTiles tiles={tiles} />

            <div className="grid gap-3">
                <Heading variant="small" title={t('Tasks done per day')} />
                <div className="rounded-lg border p-4">
                    <ReportChart
                        data={daily}
                        xKey="day"
                        series={dailySeries}
                        ariaLabel={t('Tasks done per day')}
                    />
                </div>
            </div>

            <div className="grid gap-3">
                <Heading variant="small" title={t('Tasks by team')} />
                <div className="rounded-lg border p-4">
                    <ReportChart
                        kind="bar"
                        data={teams}
                        xKey="team"
                        series={teamSeries}
                        ariaLabel={t('Tasks by team')}
                    />
                </div>
            </div>
        </div>
    );
}
