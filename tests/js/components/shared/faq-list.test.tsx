import { fireEvent, render, screen } from '@testing-library/react';
import { expect, test } from 'vite-plus/test';
import FaqList, { type FaqTopic } from '@/components/shared/faq-list';

const topics: FaqTopic[] = [
    {
        id: 'orders',
        title: 'Orders',
        items: [
            {
                id: 'o1',
                question: 'Where is my order?',
                answer: 'Open Orders to see its status.',
            },
            {
                id: 'o2',
                question: 'Can I change an order?',
                answer: 'Until it ships.',
            },
        ],
    },
    {
        id: 'billing',
        title: 'Billing',
        items: [
            {
                id: 'b1',
                question: 'How do I get an invoice?',
                answer: 'Download the PDF from the order.',
            },
            {
                id: 'b2',
                question: '发票可以改抬头吗？',
                answer: '发货前可以修改。',
            },
        ],
    },
];

test('the topics and their questions show, and the answers stay closed', () => {
    render(<FaqList topics={topics} />);

    expect(screen.getByRole('heading', { name: 'Orders' })).toBeTruthy();
    expect(screen.getByRole('heading', { name: 'Billing' })).toBeTruthy();
    expect(screen.getAllByRole('button')).toHaveLength(4);
    expect(screen.queryByText('Until it ships.')).toBeNull();
});

test('a question opens its answer, and several can be open at once', () => {
    render(<FaqList topics={topics} />);

    fireEvent.click(
        screen.getByRole('button', { name: 'Can I change an order?' }),
    );
    fireEvent.click(
        screen.getByRole('button', { name: 'How do I get an invoice?' }),
    );

    expect(screen.getByText('Until it ships.')).toBeTruthy();
    expect(screen.getByText('Download the PDF from the order.')).toBeTruthy();
    expect(
        screen
            .getByRole('button', { name: 'Can I change an order?' })
            .getAttribute('aria-expanded'),
    ).toBe('true');

    fireEvent.click(
        screen.getByRole('button', { name: 'Can I change an order?' }),
    );
    expect(screen.queryByText('Until it ships.')).toBeNull();
});

test('typing narrows the questions by question and answer, whatever the case, and hides a topic with no match', () => {
    render(<FaqList topics={topics} />);
    const box = screen.getByRole('searchbox', { name: 'Search help' });

    fireEvent.change(box, { target: { value: 'INVOICE' } });
    expect(screen.getAllByRole('button')).toHaveLength(1);
    expect(
        screen.getByRole('button', { name: 'How do I get an invoice?' }),
    ).toBeTruthy();
    expect(screen.queryByRole('heading', { name: 'Orders' })).toBeNull();

    // "order" is in two questions and in one answer (the invoice's).
    fireEvent.change(box, { target: { value: 'order' } });
    expect(screen.getAllByRole('button')).toHaveLength(3);
    expect(screen.getByRole('heading', { name: 'Billing' })).toBeTruthy();

    fireEvent.change(box, { target: { value: 'pdf' } });
    expect(screen.getAllByRole('button')).toHaveLength(1);
    expect(screen.queryByRole('heading', { name: 'Orders' })).toBeNull();
});

test('Chinese text is searched the same way', () => {
    render(<FaqList topics={topics} />);

    fireEvent.change(screen.getByRole('searchbox'), {
        target: { value: '抬头' },
    });

    expect(screen.getAllByRole('button')).toHaveLength(1);
    expect(
        screen.getByRole('button', { name: '发票可以改抬头吗？' }),
    ).toBeTruthy();
});

test('nothing matching shows one empty line, and clearing the box brings the questions back', () => {
    render(<FaqList topics={topics} />);
    const box = screen.getByRole('searchbox');

    fireEvent.change(box, { target: { value: 'zzz' } });
    expect(screen.queryAllByRole('button')).toHaveLength(0);
    expect(screen.getByText('No questions match your search.')).toBeTruthy();

    fireEvent.change(box, { target: { value: '' } });
    expect(screen.getAllByRole('button')).toHaveLength(4);
});
