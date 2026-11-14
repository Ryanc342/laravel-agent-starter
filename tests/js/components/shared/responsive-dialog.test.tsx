import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

function renderDialog() {
    return render(
        <ResponsiveDialog
            open
            onOpenChange={() => {}}
            title="Add task"
            description="Tasks you add show up on your list."
        >
            <ResponsiveDialogBody>
                <p>The fields</p>
            </ResponsiveDialogBody>
            <ResponsiveDialogFooter>
                <button type="button">Save</button>
            </ResponsiveDialogFooter>
        </ResponsiveDialog>,
    );
}

test('on desktop the dialog is centred in the page', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    renderDialog();
    const dialog = screen.getByRole('dialog');

    expect(dialog.getAttribute('data-layout')).toBe('dialog');
    expect(dialog.className).toContain('top-[50%]');
    expect(dialog.className).not.toContain('bottom-0');
    expect(screen.getByText('Add task')).toBeTruthy();
});

test('on a phone the dialog slides up from the bottom at full width', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    renderDialog();
    const dialog = screen.getByRole('dialog');

    expect(dialog.getAttribute('data-layout')).toBe('sheet');
    expect(dialog.className).toContain('bottom-0');
    expect(dialog.className).toContain('slide-in-from-bottom');
    expect(dialog.className).toContain('max-w-none');
    expect(dialog.className).not.toContain('top-[50%]');
});

test('a long form scrolls inside the body while the footer stays in view', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    renderDialog();
    const body = screen.getByText('The fields').parentElement as HTMLElement;
    const footer = screen.getByText('Save').parentElement as HTMLElement;

    expect(body.className).toContain('overflow-y-auto');
    expect(body.contains(footer)).toBe(false);
    expect(screen.getByRole('dialog').className).toContain('max-h-');
});

test('a description describes the dialog for screen readers', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    renderDialog();
    const describedBy = screen
        .getByRole('dialog')
        .getAttribute('aria-describedby');

    expect(describedBy).toBeTruthy();
    expect(document.getElementById(describedBy as string)?.textContent).toBe(
        'Tasks you add show up on your list.',
    );
});

test('without a description the dialog is not described by a missing element', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(
        <ResponsiveDialog open onOpenChange={() => {}} title="Remove passkey">
            <ResponsiveDialogFooter>
                <button type="button">Remove</button>
            </ResponsiveDialogFooter>
        </ResponsiveDialog>,
    );

    expect(screen.getByRole('dialog').hasAttribute('aria-describedby')).toBe(
        false,
    );
});

test('an icon sits above the title and centres the header', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(
        <ResponsiveDialog
            open
            onOpenChange={() => {}}
            title="Enable two-factor authentication"
            icon={<span data-testid="icon" />}
            narrow
        >
            <ResponsiveDialogFooter>
                <button type="button">Continue</button>
            </ResponsiveDialogFooter>
        </ResponsiveDialog>,
    );
    const icon = screen.getByTestId('icon');
    const title = screen.getByText('Enable two-factor authentication');

    expect(
        icon.compareDocumentPosition(title) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
    expect(icon.parentElement?.className).toContain('text-center');
    expect(screen.getByRole('dialog').className).toContain('sm:max-w-md');
});

test('the buttons sit at the right end, Cancel first and the main one last, stacked with the main one on top on a phone', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);

    render(
        <ResponsiveDialog open onOpenChange={() => {}} title="Delete task">
            <ResponsiveDialogFooter>
                <ResponsiveDialogClose asChild>
                    <button type="button">Cancel</button>
                </ResponsiveDialogClose>
                <button type="button">Delete task</button>
            </ResponsiveDialogFooter>
        </ResponsiveDialog>,
    );
    const footer = screen.getByText('Delete task', { selector: 'button' })
        .parentElement as HTMLElement;

    expect(
        Array.from(footer.querySelectorAll('button')).map(
            (button) => button.textContent,
        ),
    ).toEqual(['Cancel', 'Delete task']);
    expect(footer.className).toContain('sm:justify-end');
    expect(footer.className).not.toContain('sm:justify-start');
    expect(footer.className).toContain('sm:flex-row');
    expect(footer.className).toContain('flex-col-reverse');
    expect(footer.className).toContain('max-md:*:w-full');
});

test('the trigger opens the dialog', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);

    render(
        <ResponsiveDialog
            title="Add task"
            trigger={<button type="button">Open</button>}
        >
            <ResponsiveDialogFooter>
                <button type="button">Save</button>
            </ResponsiveDialogFooter>
        </ResponsiveDialog>,
    );

    expect(screen.queryByRole('dialog')).toBeNull();

    fireEvent.click(screen.getByText('Open'));

    expect(screen.getByRole('dialog')).toBeTruthy();
});
