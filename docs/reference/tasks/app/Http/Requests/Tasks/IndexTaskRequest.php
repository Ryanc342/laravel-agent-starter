<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\ReadsPerPage;
use Illuminate\Foundation\Http\FormRequest;

final class IndexTaskRequest extends FormRequest
{
    use ReadsPerPage;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return $this->perPageRules();
    }
}
