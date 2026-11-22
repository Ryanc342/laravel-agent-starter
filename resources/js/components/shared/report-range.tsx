import DatePicker from '@/components/shared/date-picker';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useListQuery } from '@/hooks/use-list-query';
import { listParam } from '@/lib/list-query';

const presets = [
    { value: '7d', label: '7 days' },
    { value: '30d', label: '30 days' },
    { value: '90d', label: '90 days' },
    { value: 'custom', label: 'Custom' },
] as const;

export const defaultRange = '30d';

/**
 * The range of a report: a row of presets (7 days, 30 days, 90 days, Custom)
 * and, for Custom, two date pickers. It lives only in the URL
 * (`?range=30d`, or `?range=custom&from=2026-10-01&to=2026-10-31`); 30 days is
 * the default. The backend turns the range into the figures; this only sets it.
 */
export default function ReportRange() {
    const { url, change } = useListQuery();
    const asked = listParam(url, 'range');
    const range = presets.some((preset) => preset.value === asked)
        ? asked
        : defaultRange;

    return (
        <div className="flex flex-wrap items-center gap-3">
            <ToggleGroup
                type="single"
                variant="outline"
                value={range}
                aria-label="Range"
                className="max-w-full overflow-x-auto"
                onValueChange={(value) => {
                    if (value) {
                        change({ range: value, from: null, to: null });
                    }
                }}
            >
                {presets.map((preset) => (
                    <ToggleGroupItem key={preset.value} value={preset.value}>
                        {preset.label}
                    </ToggleGroupItem>
                ))}
            </ToggleGroup>
            {range === 'custom' ? (
                <div className="flex flex-wrap items-center gap-3">
                    <DatePicker
                        label="From"
                        className="w-44"
                        value={listParam(url, 'from')}
                        onChange={(value) => change({ from: value })}
                    />
                    <DatePicker
                        label="To"
                        className="w-44"
                        value={listParam(url, 'to')}
                        onChange={(value) => change({ to: value })}
                    />
                </div>
            ) : null}
        </div>
    );
}
