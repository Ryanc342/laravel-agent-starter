<?php

namespace App\Concerns;

trait TaskValidationRules
{
    /**
     * Field rules shared by StoreTaskRequest and UpdateTaskRequest.
     *
     * @return array<string, array<int, string>>
     */
    protected function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'due_on' => ['nullable', 'date'],
        ];
    }
}
