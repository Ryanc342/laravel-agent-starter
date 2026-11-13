import { useEffect, useRef, useState } from 'react';
import { useListQuery } from '@/hooks/use-list-query';
import { listParam } from '@/lib/list-query';

/**
 * A text box tied to one URL parameter (the search, a range end). Typing
 * updates the box at once and asks for the list after a short pause; `commit`
 * asks at once (Enter, a picked date). When the address changes some other way
 * (Clear), the box follows it.
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

    return {
        value,
        onType: (next: string) => {
            setValue(next);
            clearTimeout(timer.current);
            timer.current = setTimeout(() => send(next), delay);
        },
        commit: (next: string = value) => {
            setValue(next);
            send(next);
        },
    };
}
