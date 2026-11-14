<?php

namespace App\Http\Requests\Tasks;

use App\Concerns\TaskValidationRules;
use App\Support\TaskCsv;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The uploaded CSV and every one of its rows. The file is read here and each row
 * is checked with the rules of the create form (TaskValidationRules), so a bad
 * row is an error that names its row. Every row error comes back, in row order;
 * the page cuts them to five toasts and one "And N more errors." line
 * (useValidationErrorToasts). Whether the user may have these tasks (the open-task limit) is
 * the Action's question.
 */
final class ImportTaskRequest extends FormRequest
{
    use TaskValidationRules;

    /** The largest file, in kilobytes. */
    private const int MAX_KILOBYTES = 1024;

    /** The most rows a file may have. */
    private const int MAX_ROWS = 1000;

    /** @var array{utf8: bool, columns: list<string>, rows: array<int, array{title: string|null, due_on: string|null}>}|null */
    private ?array $parsed = null;

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = $this->taskRules();

        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.self::MAX_KILOBYTES],
            'rows' => ['array', 'max:'.self::MAX_ROWS],
            'rows.*.title' => $rules['title'],
            'rows.*.due_on' => $rules['due_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose a CSV file.'),
            'file.file' => __('Choose a CSV file.'),
            'file.mimes' => __('The file must be a CSV file.'),
            'file.max' => __('The file can be at most 1 MB.'),
            'rows.max' => __('The file can have at most 1000 tasks.'),
            'rows.*.title.required' => __('Row :index: Title is required.'),
            'rows.*.title.string' => __('Row :index: Title must be text.'),
            'rows.*.title.max' => __('Row :index: Title can have at most :max characters.'),
            'rows.*.due_on.date' => __('Row :index: Due date is not a date.'),
        ];
    }

    /**
     * The tasks to import: one entry per row of the file.
     *
     * @return list<array{title: string, due_on: string|null}>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ((array) $this->validated('rows') as $row) {
            $rows[] = ['title' => (string) $row['title'], 'due_on' => $row['due_on'] === null ? null : (string) $row['due_on']];
        }

        return $rows;
    }

    /**
     * What the file says about the file itself: its columns and whether it holds any task.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('file') || $this->parsed === null) {
                return;
            }

            if (! in_array('title', $this->parsed['columns'], true)) {
                $validator->errors()->add('file', __('The first line must name the columns Title and Due date.'));
            } elseif (! $this->parsed['utf8']) {
                $validator->errors()->add('file', __('The file must be saved as UTF-8.'));
            } elseif ($this->parsed['rows'] === []) {
                $validator->errors()->add('file', __('The file has no tasks.'));
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $file = $this->file('file');

        if ($file === null || is_array($file) || ! $file->isValid() || $file->getSize() > self::MAX_KILOBYTES * 1024) {
            return;
        }

        $this->parsed = TaskCsv::read((string) $file->getRealPath());

        // One row more than allowed is enough for "rows.max" to refuse the file, without checking every row of a huge one.
        $this->merge(['rows' => array_slice($this->parsed['rows'], 0, self::MAX_ROWS + 1, true)]);
    }

    /**
     * A problem with the file itself is shown alone; row errors all come back, in row order. The page's
     * validation toasts show the first five and "And N more errors" (docs/rules/frontend.md).
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $errors = $validator->errors();
        $messages = $errors->messages();

        if ($errors->has('file')) {
            foreach (array_keys($messages) as $key) {
                if ($key !== 'file') {
                    $errors->forget($key);
                }
            }
        } else {
            uksort($messages, fn (string $a, string $b): int => $this->rowOf($a) <=> $this->rowOf($b));

            foreach (array_keys($messages) as $key) {
                $errors->forget($key);
            }

            foreach ($messages as $key => $list) {
                foreach ($list as $message) {
                    $errors->add($key, $message);
                }
            }
        }

        parent::failedValidation($validator);
    }

    /**
     * The spreadsheet row of an error key such as "rows.3.due_on".
     */
    private function rowOf(string $key): int
    {
        return preg_match('/^rows\.(\d+)\./', $key, $match) === 1 ? (int) $match[1] : 0;
    }
}
