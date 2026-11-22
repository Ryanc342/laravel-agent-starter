export type StatTile = { label: string; value: string };

/**
 * A row of number tiles: a label and one
 * figure each, in a bordered box. The figures are the backend's, already
 * formatted; there are no placeholders, and a tile shows no chart of its own.
 */
export default function StatTiles({ tiles }: { tiles: StatTile[] }) {
    return (
        <dl className="grid grid-cols-2 gap-4 md:grid-cols-4">
            {tiles.map((tile) => (
                <div
                    key={tile.label}
                    className="grid gap-1 rounded-lg border p-4"
                >
                    <dt className="text-sm text-muted-foreground">
                        {tile.label}
                    </dt>
                    <dd className="text-xl font-semibold tabular-nums">
                        {tile.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
