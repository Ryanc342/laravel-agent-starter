import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

function tabs(onValueChange = vi.fn()) {
    render(
        <Tabs defaultValue="overview" onValueChange={onValueChange}>
            <TabsList>
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="history">History</TabsTrigger>
            </TabsList>
            <TabsContent value="overview">The facts</TabsContent>
            <TabsContent value="history">The events</TabsContent>
        </Tabs>,
    );

    return onValueChange;
}

test('the first tab is selected and only its content shows', () => {
    tabs();

    expect(screen.getByRole('tab', { name: 'Overview' })).toHaveProperty(
        'ariaSelected',
        'true',
    );
    expect(screen.getByText('The facts')).toBeTruthy();
    expect(screen.queryByText('The events')).toBeNull();
});

test('the arrow keys move the selection and report the new value', async () => {
    const onValueChange = tabs();
    const first = screen.getByRole('tab', { name: 'Overview' });

    first.focus();
    fireEvent.keyDown(first, { key: 'ArrowRight' });

    await waitFor(() =>
        expect(screen.getByRole('tab', { name: 'History' })).toHaveProperty(
            'ariaSelected',
            'true',
        ),
    );
    expect(onValueChange).toHaveBeenCalledWith('history');
    expect(screen.getByText('The events')).toBeTruthy();
});

test('the strip is grey, scrolls sideways and a tab has hover and press states', () => {
    tabs();

    const list = screen.getByRole('tablist');
    const tab = screen.getByRole('tab', { name: 'History' });

    expect(list.className).toContain('bg-muted');
    expect(list.className).toContain('overflow-x-auto');
    expect(tab.className).toContain('hover:bg-accent');
    expect(tab.className).toContain('active:bg-accent-pressed');
    expect(tab.className).toContain('data-[state=active]:bg-background');
});
