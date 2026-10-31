<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\TaskValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape checks only. "A completed task cannot be edited" is in UpdateTask.
 */
class UpdateTaskRequest extends FormRequest
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
