import { Input } from '@/components/ui/input';
import { useUrlParam } from '@/hooks/use-url-param';

/**
 * A search box tied to one URL parameter: the list's `search` in the toolbar
 * and in a text column's menu. It asks for the list after a pause in typing or
 * at Enter.
 */
export default function UrlSearchInput({
    param,
    ...props
}: { param: string } & Omit<
    React.ComponentProps<typeof Input>,
    'value' | 'onChange' | 'onKeyDown' | 'type'
>) {
    const { inputProps } = useUrlParam(param);

    return <Input type="search" {...props} {...inputProps} />;
}
