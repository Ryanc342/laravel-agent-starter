<?php

namespace App\Http\Resources;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Which notification buttons the bell and the notifications page show; each flag asks OperationGate.
 */
#[TypeScript]
final class NotificationSummaryCan extends ResourceShape
{
    public function __construct(
        public bool $mark_all_read,
    ) {}
}
