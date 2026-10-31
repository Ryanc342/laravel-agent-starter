<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The data contract with resources/js/types/task.ts. Change both together.
 * Every flag and every can.* calls an existing owner; none writes a condition.
 *
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'due_on' => $this->due_on?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'can' => [
                'update' => (bool) $user?->can('update', $this->resource) && $this->canBeEdited(),
                'delete' => (bool) $user?->can('delete', $this->resource),
                'complete' => (bool) $user?->can('complete', $this->resource) && $this->canBeCompleted(),
            ],
        ];
    }
}
