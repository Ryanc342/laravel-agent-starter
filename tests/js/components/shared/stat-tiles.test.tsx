import { render, screen } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import StatTiles from '@/components/shared/stat-tiles';

test('each tile shows its label and the figure the backend sent, as it came', () => {
    render(
        <StatTiles
            tiles={[
                { label: 'Done', value: '128' },
                { label: 'Overdue', value: '7' },
            ]}
        />,
    );

    expect(screen.getByText('Done').nextElementSibling?.textContent).toBe(
        '128',
    );
    expect(screen.getByText('Overdue').nextElementSibling?.textContent).toBe(
        '7',
    );
    expect(screen.getAllByRole('term')).toHaveLength(2);
});

test('no tiles, nothing drawn but the list', () => {
    render(<StatTiles tiles={[]} />);

    expect(screen.queryByRole('term')).toBeNull();
});
