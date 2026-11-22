<?php

namespace App\Http\Resources;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Which buttons a notification row shows; each flag asks OperationGate.
 */
#[TypeScript]
final class NotificationCan extends ResourceShape
{
    public function __construct(
        public bool $mark_read,
    ) {}
}
