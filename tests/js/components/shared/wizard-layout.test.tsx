import { render, screen } from '@testing-library/react';
import { afterEach, expect, test, vi } from 'vite-plus/test';
import WizardLayout from '@/components/shared/wizard-layout';
import { useIsMobile } from '@/hooks/use-mobile';

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: vi.fn() }));

afterEach(() => {
    vi.mocked(useIsMobile).mockReset();
});

function wizard() {
    return render(
        <WizardLayout
            steps={['Team', 'Members', 'Review']}
            current={1}
            footer={<button>Next</button>}
        >
            <p>The fields</p>
        </WizardLayout>,
    );
}

test('on a computer the steps are listed, the current one marked and a finished one checked', () => {
    vi.mocked(useIsMobile).mockReturnValue(false);
    wizard();

    const steps = screen.getAllByRole('listitem');

    expect(steps).toHaveLength(3);
    expect(steps[1].getAttribute('aria-current')).toBe('step');
    expect(steps[0].getAttribute('aria-current')).toBeNull();
    expect(steps[1].className).toContain('bg-accent-pressed');
    expect(steps[0].querySelector('[aria-label="Done"]')).not.toBeNull();
    expect(steps[2].textContent).toBe('3Review');
});

test('on a phone the list is one line: "Step 2 of 3 · Members"', () => {
    vi.mocked(useIsMobile).mockReturnValue(true);
    wizard();

    expect(screen.getByText('Step 2 of 3 · Members')).toBeTruthy();
    expect(screen.queryByRole('list')).toBeNull();
});

test('the fields and the buttons show on both', () => {
    for (const mobile of [false, true]) {
        vi.mocked(useIsMobile).mockReturnValue(mobile);
        const { unmount } = wizard();

        expect(screen.getByText('The fields')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Next' })).toBeTruthy();
        unmount();
    }
});
