import { fireEvent, render, screen } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import FilePicker from '@/components/shared/file-picker';

function picker() {
    render(
        <form aria-label="Import">
            <label htmlFor="file-field">CSV file</label>
            <FilePicker id="file-field" name="file" accept=".csv" required />
        </form>,
    );

    return document.querySelector<HTMLInputElement>('input[type="file"]')!;
}

test("the field is a button named by its label, with the app's own words", () => {
    const input = picker();
    const button = screen.getByRole('button', { name: 'CSV file' });

    expect(button.textContent).toBe('Choose file');
    expect(screen.getByText('No file chosen')).toBeTruthy();
    expect(button.getAttribute('aria-describedby')).toBe('file-field-chosen');
    expect(input.getAttribute('name')).toBe('file');
    expect(input.getAttribute('accept')).toBe('.csv');
    expect(input.className).toContain('sr-only');
    // Screen readers announce it as required, on the button they reach.
    expect(button.getAttribute('aria-required')).toBe('true');
    expect(input.required).toBe(true);
});

test("the button opens the browser's file chooser", () => {
    const input = picker();
    let opened = 0;
    input.addEventListener('click', () => opened++);

    fireEvent.click(screen.getByRole('button', { name: 'CSV file' }));

    expect(opened).toBe(1);
});

test('the chosen file is named', () => {
    const input = picker();
    const file = new File(['Title\n'], 'tasks.csv', { type: 'text/csv' });

    fireEvent.change(input, { target: { files: [file] } });

    expect(screen.getByText('tasks.csv')).toBeTruthy();
    expect(screen.queryByText('No file chosen')).toBeNull();
});

test('focusing the field by its name, as a failed submit does, focuses the button', () => {
    const input = picker();

    input.focus();

    expect(document.activeElement).toBe(
        screen.getByRole('button', { name: 'CSV file' }),
    );
});
