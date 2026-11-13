<?php

namespace App\Http\Requests\Notifications;

use App\Concerns\ReadsPerPage;
use Illuminate\Foundation\Http\FormRequest;

final class IndexNotificationRequest extends FormRequest
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
