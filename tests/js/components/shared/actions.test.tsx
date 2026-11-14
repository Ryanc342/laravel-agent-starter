import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, expect, test, vi } from 'vite-plus/test';
import Actions from '@/components/shared/actions';

let observed: ((entries: unknown[]) => void) | null = null;

class FakeResizeObserver {
    constructor(callback: (entries: unknown[]) => void) {
        observed = callback;
    }

    observe() {}

    disconnect() {}
}

beforeEach(() => {
    vi.stubGlobal('ResizeObserver', FakeResizeObserver);
});

afterEach(() => {
    observed = null;
    vi.unstubAllGlobals();
});

function row() {
    return render(
        <div>
            <Actions data-testid="actions">
                <button>Cancel</button>
                <button>Save changes</button>
            </Actions>
        </div>,
    );
}

/** Tell the component how wide the element it sits in is. */
function resizeTo(width: number) {
    act(() => observed?.([{ contentRect: { width } }]));
}

test('the buttons are in reading order, the main one last', () => {
    row();

    expect(
        screen.getAllByRole('button').map((button) => button.textContent),
    ).toEqual(['Cancel', 'Save changes']);
});

test('in a wide container the buttons sit in a row', () => {
    row();
    resizeTo(640);

    expect(screen.getByTestId('actions').getAttribute('data-layout')).toBe(
        'row',
    );
});

test('in a narrow container the buttons stack', () => {
    row();
    resizeTo(320);

    expect(screen.getByTestId('actions').getAttribute('data-layout')).toBe(
        'stacked',
    );
});

test('widening the container again puts the buttons back in a row', () => {
    row();
    resizeTo(320);
    resizeTo(640);

    expect(screen.getByTestId('actions').getAttribute('data-layout')).toBe(
        'row',
    );
});

test('without a ResizeObserver the buttons stay in a row', () => {
    vi.stubGlobal('ResizeObserver', undefined);
    row();

    expect(screen.getByTestId('actions').getAttribute('data-layout')).toBe(
        'row',
    );
});
