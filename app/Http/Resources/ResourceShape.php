<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * A typed shape the page receives. Its constructor lists every field with its
 * PHP type; the TypeScript copy in resources/js/types/resources.d.ts is kept
 * the same by hand. A nested group of fields (a Resource's can flags) is a
 * shape of its own.
 *
 * @implements Arrayable<string, mixed>
 */
abstract class ResourceShape implements Arrayable, JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return self::plain(get_object_vars($this));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private static function plain(array $values): array
    {
        return array_map(fn (mixed $value): mixed => match (true) {
            $value instanceof self => $value->toArray(),
            is_array($value) => self::plain($value),
            default => $value,
        }, $values);
    }
}
