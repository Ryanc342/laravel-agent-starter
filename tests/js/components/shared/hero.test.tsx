import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { Bell } from 'lucide-react';
import { expect, test, vi } from 'vite-plus/test';
import FeatureGrid from '@/components/shared/feature-grid';
import Hero from '@/components/shared/hero';

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children }: { href: unknown; children: ReactNode }) => (
        <a
            href={
                typeof href === 'string' ? href : (href as { url: string }).url
            }
        >
            {children}
        </a>
    ),
}));

test('the hero has one heading in the display size, one sentence and its two buttons', () => {
    render(
        <Hero
            title="Start building"
            summary="One sentence."
            primary={{ label: 'Get started', href: '/register' }}
            secondary={{ label: 'Log in', href: '/login' }}
        />,
    );

    const heading = screen.getByRole('heading', { level: 1 });

    expect(heading.textContent).toBe('Start building');
    expect(heading.className).toContain('text-display');
    expect(heading.className).toContain('md:text-display-lg');
    expect(screen.getByText('One sentence.')).toBeTruthy();
    expect(
        screen.getByRole('link', { name: 'Get started' }).getAttribute('href'),
    ).toBe('/register');
    expect(
        screen.getByRole('link', { name: 'Log in' }).getAttribute('href'),
    ).toBe('/login');
});

test('the second button is optional', () => {
    render(
        <Hero
            title="Start building"
            summary="One sentence."
            primary={{ label: 'Open dashboard', href: '/dashboard' }}
        />,
    );

    expect(screen.getAllByRole('link')).toHaveLength(1);
});

test('the feature grid lists each feature as a title and one line, with no card around it', () => {
    const { container } = render(
        <FeatureGrid
            features={[
                { icon: Bell, title: 'Notifications', description: 'A bell.' },
                { icon: Bell, title: 'Settings', description: 'Your account.' },
            ]}
        />,
    );

    expect(screen.getAllByRole('listitem')).toHaveLength(2);
    expect(screen.getByRole('heading', { name: 'Notifications' })).toBeTruthy();
    expect(screen.getByText('Your account.')).toBeTruthy();
    expect(container.querySelector('.border')).toBeNull();
});
