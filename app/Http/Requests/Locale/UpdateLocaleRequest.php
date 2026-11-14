<?php

namespace App\Http\Requests\Locale;

use App\Enums\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }

    public function locale(): Locale
    {
        return $this->enum('locale', Locale::class) ?? Locale::En;
    }
}
