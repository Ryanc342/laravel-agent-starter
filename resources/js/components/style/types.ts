/** Example data the style overview page passes to the demos. It is not backend data. */
export type ExampleTask = {
    id: number;
    title: string;
    status: string;
    overdue: boolean;
    due_on: string | null;
    /** In cents; shown through `formatMoney`. */
    amount: number;
};

export type ExampleNotification = {
    id: string;
    message: string;
    time: string;
    unread: boolean;
};
