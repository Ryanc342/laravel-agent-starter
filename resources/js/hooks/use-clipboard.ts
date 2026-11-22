import { useState } from 'react';
import { toast } from 'sonner';

export type CopiedValue = string | null;
export type CopyFn = (text: string) => Promise<boolean>;
export type UseClipboardReturn = [CopiedValue, CopyFn];

/**
 * Copies text to the clipboard and remembers what was copied, so a button can
 * show a tick. When the browser cannot copy (no clipboard, or it refused), the
 * user is told in a toast, so they know to copy the text by hand.
 */
export function useClipboard(): UseClipboardReturn {
    const [copiedText, setCopiedText] = useState<CopiedValue>(null);

    const copy: CopyFn = async (text) => {
        try {
            if (!navigator.clipboard) {
                throw new Error('This browser has no clipboard.');
            }

            await navigator.clipboard.writeText(text);
            setCopiedText(text);

            return true;
        } catch {
            setCopiedText(null);
            toast.error(
                'Could not copy. Select the text and copy it yourself.',
                {
                    id: 'clipboard-error',
                },
            );

            return false;
        }
    };

    return [copiedText, copy];
}
