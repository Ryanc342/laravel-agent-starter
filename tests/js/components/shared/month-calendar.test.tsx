import { fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import MonthCalendar from '@/components/shared/month-calendar';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-mobile';

const state = vi.hoisted(() => ({
    url: '/calendar?month=2026-11',
    get: vi.fn(),
}));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    router: { get: state.get },
    usePage: () => ({
        url: state.url,
        props: { today: { date: '2026-11-14', year: 2026 } },
    }),
}));

const events = [
    { id: 1, date: '2026-11-04', title: 'Standup' },
    { id: 2, date: '2026-11-04', title: 'Quote review' },
    { id: 3, date: '2026-11-20', title: 'Delivery' },
    { id: 4, date: '2026-12-01', title: 'Year end' },
];

beforeEach(() => {
    state.url = '/calendar?month=2026-11';
    state.get.mockReset();
    vi.mocked(useIsMobile).mockReturnValue(false);
});

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

function calendar(list = events) {
    const onEvent = vi.fn();
    const onMore = vi.fn();

    render(
        <TooltipProvider>
            <MonthCalendar
                events={list}
                onEvent={onEvent}
                onMore={onMore}
                today="2026-11-14"
            />
        </TooltipProvider>,
    );

    return { onEvent, onMore };
}

function visited(): URL {
    const calls = state.get.mock.calls;

    return new URL(calls[calls.length - 1][0], 'http://localhost');
}

function cell(date: string): HTMLElement {
    return document.querySelector(`[data-date="${date}"]`) as HTMLElement;
}

test('the header names the month and the grid places each event on its day', () => {
    calendar();

    expect(screen.getByRole('heading', { name: 'November 2026' })).toBeTruthy();
    expect(within(cell('2026-11-04')).getByText('Standup')).toBeTruthy();
    expect(within(cell('2026-11-20')).getByText('Delivery')).toBeTruthy();
});

test("without a today prop the calendar marks the backend's today", () => {
    render(
        <TooltipProvider>
            <MonthCalendar events={[]} onEvent={vi.fn()} onMore={vi.fn()} />
        </TooltipProvider>,
    );

    const marked = document.querySelectorAll('[aria-current="date"]');

    expect(marked).toHaveLength(1);
    expect(marked[0].textContent).toBe('14');
});

test('the days are listed under their weekday names, today marked', () => {
    calendar();

    expect(screen.getByText('Sun')).toBeTruthy();
    expect(screen.getByText('Sat')).toBeTruthy();
    expect(
        within(cell('2026-11-14')).getByText('14').getAttribute('aria-current'),
    ).toBe('date');
});

test('an event is handed to the page, which opens it in a dialog', () => {
    const { onEvent } = calendar();

    fireEvent.click(screen.getByRole('button', { name: 'Delivery' }));

    expect(onEvent).toHaveBeenCalledWith(events[2]);
});

test('past three events a day shows "+N more", which hands the day to the page', () => {
    const many = ['A', 'B', 'C', 'D', 'E'].map((title, index) => ({
        id: index,
        date: '2026-11-09',
        title,
    }));
    const { onMore } = calendar(many);

    expect(within(cell('2026-11-09')).queryByText('D')).toBeNull();
    fireEvent.click(screen.getByRole('button', { name: '+2 more' }));

    expect(onMore).toHaveBeenCalledWith('2026-11-09');
});

test('Previous month, Next month and Today change only the month in the URL', () => {
    state.url = '/calendar?month=2026-11&view=a';
    calendar();

    fireEvent.click(screen.getByRole('button', { name: 'Next month' }));
    expect(visited().searchParams.get('month')).toBe('2026-12');
    expect(visited().searchParams.get('view')).toBe('a');

    fireEvent.click(screen.getByRole('button', { name: 'Previous month' }));
    expect(visited().searchParams.get('month')).toBe('2026-10');

    state.url = '/calendar?month=2027-03';
    fireEvent.click(screen.getByRole('button', { name: 'Today' }));
    expect(visited().searchParams.get('month')).toBe('2026-11');
});

test("with no month in the URL it shows the month of the backend's today", () => {
    state.url = '/calendar';
    calendar([]);

    expect(screen.getByRole('heading', { name: 'November 2026' })).toBeTruthy();
});

test('on a phone the month is a list ordered by date, without the days that have nothing', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    calendar([events[2], events[1], events[0], events[3]]);

    const days = screen
        .getAllByRole('heading', { level: 2 })
        .map((heading) => heading.textContent);

    expect(days).toEqual(['November 2026', '4 Nov 2026', '20 Nov 2026']);
    expect(screen.queryByText('Sun')).toBeNull();
    expect(screen.queryByText('Year end')).toBeNull();
});

test('on a phone a month with no events says so', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    calendar([]);

    expect(screen.getByText('Nothing this month.')).toBeTruthy();
});

test('a page can set the month to show when the URL has none, and the URL still wins', () => {
    state.url = '/calendar';
    const { unmount } = render(
        <TooltipProvider>
            <MonthCalendar
                events={[]}
                onEvent={vi.fn()}
                onMore={vi.fn()}
                defaultMonth="2027-03"
            />
        </TooltipProvider>,
    );

    expect(screen.getByRole('heading', { name: 'March 2027' })).toBeTruthy();
    unmount();

    state.url = '/calendar?month=2026-11';
    render(
        <TooltipProvider>
            <MonthCalendar
                events={[]}
                onEvent={vi.fn()}
                onMore={vi.fn()}
                defaultMonth="2027-03"
            />
        </TooltipProvider>,
    );
    expect(screen.getByRole('heading', { name: 'November 2026' })).toBeTruthy();
});
