import { fireEvent, render, screen } from '@testing-library/react';
import { expect, test, vi } from 'vite-plus/test';
import ConfirmDialog, {
    ConfirmDialogActions,
} from '@/components/shared/confirm-dialog';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => false }));

function confirmDialog(onCancel?: () => void, onConfirm = vi.fn()) {
    render(
        <ConfirmDialog
            open
            onOpenChange={() => {}}
            title="Delete this task?"
            consequence="“Write the report” will be deleted. This cannot be undone."
        >
            <ConfirmDialogActions onCancel={onCancel}>
                <button onClick={onConfirm}>Delete task</button>
            </ConfirmDialogActions>
        </ConfirmDialog>,
    );

    return onConfirm;
}

test('the title names the action and the consequence says what will happen', () => {
    confirmDialog();

    expect(screen.getByText('Delete this task?')).toBeTruthy();
    const describedBy = screen
        .getByRole('dialog')
        .getAttribute('aria-describedby') as string;

    expect(document.getElementById(describedBy)?.textContent).toBe(
        '“Write the report” will be deleted. This cannot be undone.',
    );
});

test('Cancel comes first and the confirm button last', () => {
    confirmDialog();

    expect(
        screen
            .getAllByRole('button')
            .map((button) => button.textContent)
            .filter((text) => text === 'Cancel' || text === 'Delete task'),
    ).toEqual(['Cancel', 'Delete task']);
});

test('the confirm button runs its own action', () => {
    const onConfirm = confirmDialog();

    fireEvent.click(screen.getByRole('button', { name: 'Delete task' }));

    expect(onConfirm).toHaveBeenCalledTimes(1);
});

test('Cancel runs its callback', () => {
    const onCancel = vi.fn();
    confirmDialog(onCancel);

    fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    expect(onCancel).toHaveBeenCalledTimes(1);
});
