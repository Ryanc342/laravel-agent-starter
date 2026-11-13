import { fireEvent, render, screen, within } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import DatePicker from '@/components/shared/date-picker';

function field(props: Partial<React.ComponentProps<typeof DatePicker>> = {}) {
    return render(
        <form data-testid="form">
            <label htmlFor="due">Due date</label>
            <DatePicker id="due" name="due_on" {...props} />
        </form>,
    );
}

function sent(): string | null {
    return new FormData(screen.getByTestId('form') as HTMLFormElement).get(
        'due_on',
    ) as string | null;
}

test('it shows the date its value names, and the placeholder when there is none', () => {
    const { unmount } = field({ defaultValue: '2026-11-04' });

    expect(screen.getByLabelText('Due date').textContent).toContain(
        '4 Nov 2026',
    );
    unmount();

    field();
    expect(screen.getByLabelText('Due date').textContent).toContain(
        'Pick a date',
    );
});

test('a picked day is sent as YYYY-MM-DD and closes the calendar', () => {
    const onChange = vi.fn();
    field({ defaultValue: '2026-11-04', onChange });

    fireEvent.click(screen.getByLabelText('Due date'));
    fireEvent.click(within(screen.getByRole('grid')).getByText('20'));

    expect(sent()).toBe('2026-11-20');
    expect(onChange).toHaveBeenCalledWith('2026-11-20');
    expect(screen.queryByRole('grid')).toBeNull();
    expect(screen.getByLabelText('Due date').textContent).toContain(
        '20 Nov 2026',
    );
});

test('the calendar opens on the month of the value', () => {
    field({ defaultValue: '2027-02-10' });

    fireEvent.click(screen.getByLabelText('Due date'));

    expect(screen.getByRole('grid').getAttribute('aria-label')).toContain(
        'February 2027',
    );
});

test('Clear empties the field and is offered only when there is a date', () => {
    const { unmount } = field({ defaultValue: '2026-11-04' });

    fireEvent.click(screen.getByLabelText('Due date'));
    fireEvent.click(screen.getByRole('button', { name: 'Clear' }));

    expect(sent()).toBe('');
    unmount();

    field();
    fireEvent.click(screen.getByLabelText('Due date'));
    expect(screen.queryByRole('button', { name: 'Clear' })).toBeNull();
});

test('a controlled field shows its value and leaves the change to its owner', () => {
    const onChange = vi.fn();
    field({ value: '2026-11-04', onChange });

    fireEvent.click(screen.getByLabelText('Due date'));
    fireEvent.click(within(screen.getByRole('grid')).getByText('9'));

    expect(onChange).toHaveBeenCalledWith('2026-11-09');
    expect(screen.getByLabelText('Due date').textContent).toContain(
        '4 Nov 2026',
    );
});

test('it looks like an Input: the same border, the small size and the red border for an error', () => {
    field({ size: 'sm' });
    const trigger = screen.getByLabelText('Due date');

    expect(trigger.getAttribute('data-size')).toBe('sm');
    expect(trigger.className).toContain('border-input');
    expect(trigger.className).toContain('aria-invalid:border-destructive');
    expect(trigger.className).toContain('focus-visible:border-input-focus');
});

test('it has no hidden field without a name', () => {
    render(<DatePicker id="x" defaultValue="2026-11-04" />);

    expect(document.querySelector('input')).toBeNull();
});
