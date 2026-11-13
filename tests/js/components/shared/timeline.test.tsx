import { render, screen } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import Timeline from '@/components/shared/timeline';

const events = [
    { id: 1, actor: 'Mei', action: 'submitted', when: '12 Nov, 09:30' },
    {
        id: 2,
        actor: 'Ahmad',
        action: 'rejected',
        when: '12 Nov, 14:05',
        note: 'The amount is missing.',
    },
];

test('each event says who did what and when, in the order given', () => {
    render(<Timeline events={events} />);

    const rows = screen.getAllByRole('listitem');

    expect(rows).toHaveLength(2);
    expect(rows[0].textContent).toBe('Mei submitted · 12 Nov, 09:30');
    expect(rows[1].textContent).toContain('Ahmad rejected · 12 Nov, 14:05');
});

test('a reason shows under its row, and a row without one shows nothing more', () => {
    render(<Timeline events={events} />);

    const rows = screen.getAllByRole('listitem');

    expect(rows[1].textContent).toContain('The amount is missing.');
    expect(rows[0].querySelectorAll('p')).toHaveLength(1);
});

test('the line joins the events and stops after the last one', () => {
    const { container } = render(<Timeline events={events} />);

    expect(container.querySelectorAll('li > span.w-px')).toHaveLength(1);
});
