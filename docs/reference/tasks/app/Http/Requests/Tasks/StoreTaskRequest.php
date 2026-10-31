<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\TaskValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape checks only. Business rules (for example the open-task limit) are in CreateTask.
 */
class StoreTaskRequest extends FormRequest
{
    use TaskValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->taskRules();
    }
}
