import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, expect, test, vi } from 'vite-plus/test';
import ReportChart, { seriesColor } from '@/components/shared/report-chart';
import ReportRange from '@/components/shared/report-range';
import { ChartContainer, ChartLegendContent } from '@/components/ui/chart';

const state = vi.hoisted(() => ({ url: '/reports', get: vi.fn() }));

vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({
        url: state.url,
        props: { today: { date: '2026-11-14', year: 2026 } },
    }),
}));

beforeEach(() => {
    state.url = '/reports';
    state.get.mockReset();
});

function visited(): URL {
    const calls = state.get.mock.calls;

    return new URL(calls[calls.length - 1][0], 'http://localhost');
}

function selected(): string {
    return (
        screen
            .getAllByRole('radio')
            .find((radio) => radio.getAttribute('aria-checked') === 'true')
            ?.textContent ?? ''
    );
}

test('the presets are 7 days, 30 days, 90 days and Custom, and 30 days is the default', () => {
    render(<ReportRange />);

    expect(
        screen.getAllByRole('radio').map((radio) => radio.textContent),
    ).toEqual(['7 days', '30 days', '90 days', 'Custom']);
    expect(selected()).toBe('30 days');
});

test('the range in the URL is the selected one, and an unknown value means the default', () => {
    state.url = '/reports?range=90d';
    const { unmount } = render(<ReportRange />);

    expect(selected()).toBe('90 days');
    unmount();

    state.url = '/reports?range=forever';
    render(<ReportRange />);
    expect(selected()).toBe('30 days');
});

test('choosing a preset sets only the range and drops a custom from and to', async () => {
    state.url = '/reports?range=custom&from=2026-10-01&to=2026-10-31&tab=a';
    render(<ReportRange />);

    fireEvent.click(screen.getByRole('radio', { name: '7 days' }));

    await waitFor(() => expect(state.get).toHaveBeenCalled());
    const address = visited();

    expect(address.searchParams.get('range')).toBe('7d');
    expect(address.searchParams.has('from')).toBe(false);
    expect(address.searchParams.has('to')).toBe(false);
    expect(address.searchParams.get('tab')).toBe('a');
});

test('Custom shows two date pickers with the dates of the URL; the presets do not', () => {
    const { unmount } = render(<ReportRange />);

    expect(screen.queryByLabelText('From')).toBeNull();
    unmount();

    state.url = '/reports?range=custom&from=2026-10-01&to=2026-10-31';
    render(<ReportRange />);

    expect(screen.getByLabelText('From').textContent).toContain('1 Oct 2026');
    expect(screen.getByLabelText('To').textContent).toContain('31 Oct 2026');
});

test('one series is chart-1 and several take chart-1 to chart-5 in order', () => {
    expect([0, 1, 2, 3, 4].map(seriesColor)).toEqual([
        'var(--chart-1)',
        'var(--chart-2)',
        'var(--chart-3)',
        'var(--chart-4)',
        'var(--chart-5)',
    ]);
});

const data = [
    { day: 'Mon', done: 3, added: 5 },
    { day: 'Tue', done: 4, added: 2 },
];

test('a chart has a name and the same figures in a table for screen readers', () => {
    render(
        <ReportChart
            data={data}
            xKey="day"
            series={[
                { key: 'done', label: 'Done' },
                { key: 'added', label: 'Added' },
            ]}
            ariaLabel="Tasks per day"
            formatValue={(value) => `${value} tasks`}
        />,
    );

    expect(screen.getByRole('img', { name: 'Tasks per day' })).toBeTruthy();
    expect(screen.getAllByRole('row').map((row) => row.textContent)).toEqual([
        'dayDoneAdded',
        'Mon3 tasks5 tasks',
        'Tue4 tasks2 tasks',
    ]);
});

test('each series is coloured by its token through a variable of its own', () => {
    render(
        <ReportChart
            data={data}
            xKey="day"
            series={[
                { key: 'done', label: 'Done' },
                { key: 'added', label: 'Added' },
            ]}
            ariaLabel="Tasks per day"
        />,
    );

    const chart = screen.getByRole('img');

    expect(chart.style.getPropertyValue('--color-done')).toBe('var(--chart-1)');
    expect(chart.style.getPropertyValue('--color-added')).toBe(
        'var(--chart-2)',
    );
});

test('more than five series is refused: there are only five chart colours', () => {
    const series = Array.from({ length: 6 }, (_, index) => ({
        key: `s${index}`,
        label: `S${index}`,
    }));
    const quiet = vi.spyOn(console, 'error').mockImplementation(() => {});

    expect(() =>
        render(
            <ReportChart
                data={[]}
                xKey="day"
                series={series}
                ariaLabel="Too many"
            />,
        ),
    ).toThrow('at most five series');
    quiet.mockRestore();
});

test('the legend lists the series in the order of the config, not the order Recharts gives', () => {
    render(
        <ChartContainer
            config={{
                open: { label: 'Open', color: 'var(--chart-1)' },
                done: { label: 'Done', color: 'var(--chart-2)' },
                overdue: { label: 'Overdue', color: 'var(--chart-3)' },
            }}
        >
            <ChartLegendContent
                payload={[
                    { dataKey: 'done', value: 'done' },
                    { dataKey: 'open', value: 'open' },
                    { dataKey: 'overdue', value: 'overdue' },
                ]}
            />
        </ChartContainer>,
    );

    expect(
        document.querySelector('[data-slot="chart-legend"]')?.textContent,
    ).toBe('OpenDoneOverdue');
});
