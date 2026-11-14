import { usePage } from '@inertiajs/react';

/** Today in the app's timezone, from the backend: `date` is `YYYY-MM-DD`. React never reads the clock. */
export function useToday(): { date: string; year: number } {
    return usePage().props.today;
}
