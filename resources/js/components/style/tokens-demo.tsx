import Heading from '@/components/heading';
import { Specimen } from '@/components/style/style-section';
import { cn } from '@/lib/utils';

// Each swatch is a token and the text colour that goes on it.
const surfaces = [
    { name: 'background', classes: 'bg-background text-foreground' },
    { name: 'card', classes: 'bg-card text-card-foreground' },
    { name: 'muted', classes: 'bg-muted text-muted-foreground' },
    { name: 'accent', classes: 'bg-accent text-accent-foreground' },
    { name: 'sidebar', classes: 'bg-sidebar text-sidebar-foreground' },
];

const fills = [
    { name: 'primary', classes: 'bg-primary' },
    { name: 'secondary', classes: 'bg-secondary' },
    { name: 'destructive', classes: 'bg-destructive' },
    { name: 'success', classes: 'bg-success' },
    { name: 'warning', classes: 'bg-warning' },
];

const charts = [
    'bg-chart-1',
    'bg-chart-2',
    'bg-chart-3',
    'bg-chart-4',
    'bg-chart-5',
];

function Swatch({ name, classes }: { name: string; classes: string }) {
    return (
        <div
            className={cn(
                'flex h-16 w-32 items-end rounded-lg border p-2 text-xs',
                classes,
            )}
        >
            {name}
        </div>
    );
}

// A fill has no text of its own here: the Button, Badge or status that uses it
// sets the text on top (destructive has no -foreground token for that).
function FillSwatch({ name, classes }: { name: string; classes: string }) {
    return (
        <div className="grid gap-1.5">
            <div className={cn('h-16 w-32 rounded-lg border', classes)} />
            <span className="text-xs text-muted-foreground">{name}</span>
        </div>
    );
}

/** Colour, radius and type: the tokens every other example is made of. */
export default function TokensDemo() {
    return (
        <div className="grid gap-8">
            <Specimen label="Surfaces, with the text that goes on them">
                {surfaces.map((token) => (
                    <Swatch key={token.name} {...token} />
                ))}
            </Specimen>

            <Specimen label="Fills: a button, a badge, a status. The component sets the text on top">
                {fills.map((token) => (
                    <FillSwatch key={token.name} {...token} />
                ))}
            </Specimen>

            <Specimen label="Text on a surface">
                <span className="text-sm text-foreground">{'foreground'}</span>
                <span className="text-sm text-muted-foreground">
                    {'muted-foreground'}
                </span>
                <span className="text-sm text-destructive-foreground">
                    destructive-foreground: an error line
                </span>
                <span className="text-sm text-success">{'success'}</span>
                <span className="text-sm text-primary">primary: a link</span>
            </Specimen>

            <Specimen label="Lines and focus">
                <div className="flex h-16 w-32 items-end rounded-lg border-2 border-border p-2 text-xs">
                    {'border'}
                </div>
                <div className="flex h-16 w-32 items-end rounded-lg border-2 border-input-focus p-2 text-xs">
                    {'input-focus'}
                </div>
                <div className="flex h-16 w-32 items-end rounded-lg p-2 text-xs ring-2 ring-ring">
                    {'ring'}
                </div>
            </Specimen>

            <Specimen label="Chart colours, in order">
                {charts.map((chart) => (
                    <div
                        key={chart}
                        className={cn('size-10 rounded-md', chart)}
                    />
                ))}
            </Specimen>

            <Specimen label="Radius: sm, md, lg. Nothing is rounder than lg except a dot or an avatar">
                <div className="size-16 rounded-sm border bg-muted" />
                <div className="size-16 rounded-md border bg-muted" />
                <div className="size-16 rounded-lg border bg-muted" />
            </Specimen>

            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    Type: one family, four sizes
                </p>
                <Heading title="Page title: Heading, the largest text" />
                <Heading variant="small" title="Section title: Heading small" />
                <p className="text-sm">Body: text-sm</p>
                <p className="text-sm text-muted-foreground">
                    Secondary text: text-sm text-muted-foreground
                </p>
                <p className="text-xs text-muted-foreground">
                    Caption: text-xs
                </p>
            </div>
        </div>
    );
}
