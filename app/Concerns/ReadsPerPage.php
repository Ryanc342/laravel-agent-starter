<?php

namespace App\Concerns;

use App\Enums\PerPage;

trait ReadsPerPage
{
    /**
     * Rules for the `per_page` query value of a list page. It is never an
     * error: a value that is not offered counts as 20 (PerPage::fromQuery).
     *
     * @return array<string, array<int, string>>
     */
    protected function perPageRules(): array
    {
        return ['per_page' => ['nullable']];
    }

    /**
     * The number of rows the list shows on a page.
     */
    public function perPage(): int
    {
        return PerPage::fromQuery($this->validated('per_page'))->value;
    }
}
