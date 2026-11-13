import {
    type ChangeEvent,
    type KeyboardEvent,
    useEffect,
    useRef,
    useState,
} from 'react';
import { useListQuery } from '@/hooks/use-list-query';
import { listParam } from '@/lib/list-query';

/**
 * A text box tied to one URL parameter (the search, a range end). Typing
 * updates the box at once and asks for the list after a short pause (`delay`;
 * with 0, as for a picked date, at once); Enter asks at once. When the address
 * changes some other way (Clear), the box follows it. `inputProps` go straight
 * onto an `Input`.
 */
export function useUrlParam(param: string, delay = 400) {
    const { url, change } = useListQuery();
    const fromUrl = listParam(url, param);
    const [value, setValue] = useState(fromUrl);
    const sent = useRef(fromUrl);
    const timer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    useEffect(() => {
        if (fromUrl !== sent.current) {
            sent.current = fromUrl;
            setValue(fromUrl);
        }
    }, [fromUrl]);

    useEffect(() => () => clearTimeout(timer.current), []);

    const send = (next: string) => {
        clearTimeout(timer.current);
        sent.current = next;
        change({ [param]: next });
    };

    const commit = (next: string) => {
        setValue(next);
        send(next);
    };

    return {
        value,
        /** Set the value and ask for the list at once (a picked date). */
        commit,
        inputProps: {
            value,
            onChange: (event: ChangeEvent<HTMLInputElement>) => {
                const next = event.target.value;

                if (delay === 0) {
                    commit(next);

                    return;
                }

                setValue(next);
                clearTimeout(timer.current);
                timer.current = setTimeout(() => send(next), delay);
            },
            onKeyDown: (event: KeyboardEvent<HTMLInputElement>) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    commit(value);
                }
            },
        },
    };
}
