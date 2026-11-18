import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { useState } from 'react';
import { expect, test, vi } from 'vite-plus/test';
import RowActions, { type RowAction } from '@/components/shared/row-actions';
import ResponsiveDialog from '@/components/shared/responsive-dialog';
import { TooltipProvider } from '@/components/ui/tooltip';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

function open(actions: RowAction[]) {
    render(
        <TooltipProvider>
            <RowActions actions={actions} />
        </TooltipProvider>,
    );

    fireEvent.pointerDown(
        screen.getByRole('button', { name: 'More actions' }),
        {
            button: 0,
            ctrlKey: false,
        },
    );
}

test('a row shows one icon button, named "More actions", and no other button', () => {
    render(
        <TooltipProvider>
            <RowActions
                actions={[
                    { label: 'Edit', onSelect: vi.fn() },
                    { label: 'Delete', onSelect: vi.fn(), destructive: true },
                ]}
            />
        </TooltipProvider>,
    );

    expect(screen.getAllByRole('button')).toHaveLength(1);
    expect(
        screen.getByRole('button', { name: 'More actions' }).textContent,
    ).toBe('');
});

test('the menu lists the actions in their order, the destructive one last and set apart', async () => {
    open([
        { label: 'Delete', onSelect: vi.fn(), destructive: true },
        { label: 'Edit', onSelect: vi.fn() },
        { label: 'Duplicate', onSelect: vi.fn() },
    ]);

    const items = await screen.findAllByRole('menuitem');

    expect(items.map((item) => item.textContent)).toEqual([
        'Edit',
        'Duplicate',
        'Delete',
    ]);
    expect(items[2].getAttribute('data-variant')).toBe('destructive');
    expect(items[0].getAttribute('data-variant')).toBe('default');
    const separators = screen.getAllByRole('separator');
    expect(separators).toHaveLength(1);
    // The line sits right before the destructive action.
    expect(separators[0].nextElementSibling).toBe(items[2]);
    expect(separators[0].previousElementSibling).toBe(items[1]);
});

test('the button shows its name in a tooltip', async () => {
    // jsdom has no ResizeObserver; the tooltip only needs it to exist.
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            unobserve() {}
            disconnect() {}
        },
    );
    render(
        <TooltipProvider delayDuration={0}>
            <RowActions actions={[{ label: 'Edit', onSelect: vi.fn() }]} />
        </TooltipProvider>,
    );

    fireEvent.focus(screen.getByRole('button', { name: 'More actions' }));

    expect((await screen.findByRole('tooltip')).textContent).toBe(
        'More actions',
    );
});

test.each(['Edit', 'Delete'])('choosing %s runs it', async (name) => {
    const actions = {
        Edit: vi.fn(),
        Delete: vi.fn(),
    };
    open([
        { label: 'Edit', onSelect: actions.Edit },
        { label: 'Delete', onSelect: actions.Delete, destructive: true },
    ]);

    fireEvent.click(await screen.findByRole('menuitem', { name }));

    // It runs once the menu has closed.
    await waitFor(() =>
        expect(actions[name as keyof typeof actions]).toHaveBeenCalledOnce(),
    );
});

test('a single action is in the menu too', async () => {
    open([{ label: 'Edit', onSelect: vi.fn() }]);

    expect(
        (await screen.findAllByRole('menuitem')).map(
            (item) => item.textContent,
        ),
    ).toEqual(['Edit']);
    expect(screen.queryByRole('separator')).toBeNull();
});

test('a menu with only a destructive action has no separator', async () => {
    open([{ label: 'Delete', onSelect: vi.fn(), destructive: true }]);

    expect(
        (await screen.findAllByRole('menuitem')).map(
            (item) => item.textContent,
        ),
    ).toEqual(['Delete']);
    expect(screen.queryByRole('separator')).toBeNull();
});

test('a ResponsiveDialog opened from the menu gives focus back to the three-dot button when it closes', async () => {
    function Page() {
        const [open, setOpen] = useState(false);

        return (
            <TooltipProvider>
                <RowActions
                    actions={[{ label: 'Edit', onSelect: () => setOpen(true) }]}
                />
                <ResponsiveDialog
                    open={open}
                    onOpenChange={setOpen}
                    title="Edit"
                >
                    <p>Fields</p>
                </ResponsiveDialog>
            </TooltipProvider>
        );
    }

    render(<Page />);
    const trigger = screen.getByRole('button', { name: 'More actions' });
    fireEvent.pointerDown(trigger, { button: 0, ctrlKey: false });
    fireEvent.click(await screen.findByRole('menuitem', { name: 'Edit' }));

    const dialog = await screen.findByRole('dialog');
    fireEvent.keyDown(dialog, { key: 'Escape' });

    await waitFor(() => {
        expect(screen.queryByRole('dialog')).toBeNull();
        expect(document.activeElement).toBe(trigger);
    });
});
