<?php

namespace App\Http\Resources;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * `today` in every page's shared data: the date and year in the app's
 * timezone, so the web page never reads the clock.
 */
#[TypeScript]
final class Today extends ResourceShape
{
    public function __construct(
        public string $date,
        public int $year,
    ) {}
}
