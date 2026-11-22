<?php

namespace App\Actions;

use App\Jobs\OperationJob;
use App\Models\User;
use App\Support\StoredFile;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;
use SplFileInfo;
use Throwable;

/**
 * The steps every operation runs, in this order and no other (part of the
 * operation part; Operation and UserlessOperation use it):
 *
 *   1 permission  -> 2 input shape, then the rows of each CSV file (read and checked)
 *   -> 3 records named in the input (found, permission checked)
 *   -> 4 store the uploaded files, prepare (reads from outside services)
 *   -> one transaction: lock the records -> refusal -> refusal for this input -> writes
 *   -> after the writes are saved: afterCommit
 *
 * When the operation is refused or undone, the files it stored in step 4 are deleted again.
 *
 * Every caller gets the same steps: the HTTP part, a Fortify adapter, a command,
 * a job and an operation that calls another. Only a run by the system (a
 * schedule or a command) skips the permission steps.
 */
trait OperationSteps
{
    /**
     * The Policy ability checked before the operation runs.
     */
    abstract public function ability(): string;

    /**
     * The model the operation acts on. With a record in the URL it is that
     * record (the last one); without one, the ability is checked on this class.
     *
     * @return class-string<Model>
     */
    abstract public function subjectClass(): string;

    /**
     * Shape rules for the input. Only the fields named here reach the operation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Input fields that name records, as field => [model class, ability]. The
     * part finds each record by its route key (a list of keys for a list),
     * checks the ability on it, locks it with the subject, and hands the
     * operation the record instead of the key. Each field also needs a rule.
     *
     * @return array<string, array{0: class-string<Model>, 1: string}>
     */
    public function records(): array
    {
        return [];
    }

    /**
     * Input fields that are uploaded files, as field => 'private' or 'public'
     * ('public' only for a file meant for everyone). The part stores each one
     * after the permission and input checks, under a name it makes up, and
     * hands the operation an App\Support\StoredFile instead of the upload. When
     * the operation is refused or undone, the stored file is deleted again.
     * Each field also needs a rule (a file rule, with its types and size).
     *
     * @return array<string, 'private'|'public'>
     */
    public function files(): array
    {
        return [];
    }

    /**
     * Input fields that are uploaded CSV files of rows to import, as field =>
     * the rules of one row (column => rules). The part reads each file right
     * after the input shape: its first line names the columns, exactly the
     * ones listed here, in any order; every other line is one row, checked
     * with these rules, with each cell trimmed and an empty cell null, as a
     * form's input is. A blank line is no row. When any row is wrong, the
     * operation does not run and every wrong row is an input error under
     * "<field>.<line>.row" ("Row 3: ..."). Otherwise the operation gets the
     * checked rows instead of the upload, keyed by line number, and writes them
     * in its one transaction, so an import goes in whole or not at all. Each
     * field also needs a rule (a file rule, with its types and size); it is
     * not stored, so it is not in files().
     *
     * @return array<string, array<string, mixed>>
     */
    public function csvRows(): array
    {
        return [];
    }

    /**
     * True only when running it twice does no harm (it reads, or it checks
     * whether it was already done). A background job then tries it up to 3
     * times; otherwise a failed job is not tried again (App\Jobs\OperationJob).
     */
    public function repeatable(): bool
    {
        return false;
    }

    /**
     * Starts $operation as a background job once this operation's writes are
     * saved, and never when they are undone. It runs as $user; null means the
     * system, which only a UserlessOperation accepts. The input holds plain
     * values, as a user would type them: the records are found again when it runs.
     *
     * @param  class-string<Operation|UserlessOperation>  $operation
     * @param  array<string, mixed>  $input
     */
    protected function later(?User $user, string $operation, ?Model $subject = null, array $input = []): void
    {
        OperationJob::dispatch($operation, $user, $subject, $input)->afterCommit();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Closure(?Model, array<string, mixed>): array<string, mixed>  $prepare
     * @param  Closure(?Model): ?array<string, string>  $refusal
     * @param  Closure(?Model, array<string, mixed>): ?array<string, string>  $inputRefusal
     * @param  Closure(?Model, array<string, mixed>): mixed  $perform
     * @param  Closure(?Model, array<string, mixed>, mixed): void  $afterCommit
     */
    private function runSteps(?User $user, bool $checkPermission, ?Model $subject, array $input, Closure $prepare, Closure $refusal, Closure $inputRefusal, Closure $perform, Closure $afterCommit): mixed
    {
        if ($checkPermission) {
            Gate::forUser($user)->authorize($this->ability(), $subject ?? $this->subjectClass());
        }

        $input = Validator::make($input, $this->rules())->validate();
        $input = $this->readCsvRows($input);
        $input = $this->findRecords($user, $checkPermission, $input);
        $stored = [];

        try {
            foreach ($this->files() as $field => $visibility) {
                if (! array_key_exists($field, $this->rules())) {
                    throw new LogicException(static::class." names the file field [{$field}] in files() without a rule for it in rules().");
                }

                if (($input[$field] ?? null) !== null) {
                    $input[$field] = $stored[] = StoredFile::store($input[$field], $visibility);
                }
            }

            $input = [...$input, ...$prepare($subject, $input)];

            $result = DB::transaction(function () use ($subject, $input, $refusal, $inputRefusal, $perform): array {
                $subject = $subject === null ? null : self::lock($subject);
                $input = $this->lockRecords($input);

                self::refuseWhen($refusal($subject));
                self::refuseWhen($inputRefusal($subject, $input));

                return [$subject, $input, $perform($subject, $input)];
            });
        } catch (Throwable $error) {
            foreach ($stored as $file) {
                $file->deleteNow($error);
            }

            throw $error;
        }

        // Right now, or, when this operation runs inside another one, once the outer one is saved.
        DB::afterCommit(fn () => $afterCommit(...$result));

        return $result[2];
    }

    /**
     * Replaces each CSV file named by csvRows() with its checked rows, keyed by line number.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function readCsvRows(array $input): array
    {
        foreach ($this->csvRows() as $field => $rules) {
            if (! array_key_exists($field, $this->rules())) {
                throw new LogicException(static::class." names the CSV field [{$field}] in csvRows() without a rule for it in rules().");
            }

            if (array_key_exists($field, $this->files())) {
                throw new LogicException(static::class." names [{$field}] in both files() and csvRows(): a CSV file is read, not stored.");
            }

            $file = $input[$field] ?? null;

            if ($file === null) {
                continue;
            }

            if (! $file instanceof SplFileInfo) {
                throw new LogicException(static::class." gets [{$field}] as ".get_debug_type($file).': its rule in rules() must make it a file.');
            }

            $input[$field] = self::csvRowsOf($file, $field, $rules);
        }

        return $input;
    }

    /**
     * The rows of one CSV file, checked with the row rules, keyed by line number.
     *
     * @param  array<string, mixed>  $rules
     * @return array<int, array<string, mixed>>
     */
    private static function csvRowsOf(SplFileInfo $file, string $field, array $rules): array
    {
        $handle = fopen($file->getPathname(), 'r')
            ?: throw new LogicException("The CSV file of [{$field}] cannot be opened.");
        $columns = array_keys($rules);
        $headings = null;
        $rows = [];
        $errors = [];
        $line = 0;

        try {
            while (($cells = fgetcsv($handle, escape: '')) !== false) {
                $line++;

                if (! mb_check_encoding(implode('', array_map(strval(...), $cells)), 'UTF-8')) {
                    throw ValidationException::withMessages([$field => __('The file must be saved as CSV UTF-8.')]);
                }

                if ($headings === null) {
                    $headings = array_map(trim(...), array_map(strval(...), $cells));
                    $headings[0] = (string) preg_replace('/^\x{FEFF}/u', '', $headings[0]);
                    $sorted = $headings;
                    sort($sorted);
                    $wanted = $columns;
                    sort($wanted);

                    if ($sorted !== $wanted) {
                        throw ValidationException::withMessages([$field => __('The first line must name these columns: :columns.', ['columns' => implode(', ', $columns)])]);
                    }

                    continue;
                }

                if ($cells === [null]) {
                    continue;
                }

                if (count($cells) !== count($headings)) {
                    $errors["{$field}.{$line}.row"] = __('Row :line: it has :cells cells, but the first line names :columns columns.', ['line' => $line, 'cells' => count($cells), 'columns' => count($headings)]);

                    continue;
                }

                $row = array_combine($headings, array_map(fn (?string $cell): ?string => $cell === null || trim($cell) === '' ? null : trim($cell), $cells));
                $validator = Validator::make($row, $rules);

                if ($validator->fails()) {
                    $errors["{$field}.{$line}.row"] = __('Row :line: :message', ['line' => $line, 'message' => $validator->errors()->first()]);
                } else {
                    $rows[$line] = $validator->validated();
                }
            }
        } finally {
            fclose($handle);
        }

        if ($headings === null) {
            throw ValidationException::withMessages([$field => __('The first line must name these columns: :columns.', ['columns' => implode(', ', $columns)])]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($rows === []) {
            throw ValidationException::withMessages([$field => __('The file has no rows.')]);
        }

        return $rows;
    }

    /**
     * Replaces each key named by records() with its record, after checking the ability on it.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function findRecords(?User $user, bool $checkPermission, array $input): array
    {
        foreach ($this->records() as $field => [$class, $ability]) {
            if (! array_key_exists($field, $this->rules())) {
                throw new LogicException(static::class." names the record field [{$field}] in records() without a rule for it in rules().");
            }

            if (! array_key_exists($field, $input) || $input[$field] === null) {
                continue;
            }

            $keys = is_array($input[$field]) ? array_values($input[$field]) : [$input[$field]];
            $model = new $class;
            $found = $model->newQuery()->whereIn($model->getRouteKeyName(), $keys)->get()
                ->keyBy(fn (Model $record): string => (string) $record->getRouteKey());

            $records = array_map(fn (mixed $key): Model => $found->get(is_scalar($key) ? (string) $key : '')
                ?? throw ValidationException::withMessages([$field => __('validation.exists', ['attribute' => $field])]), $keys);

            if ($checkPermission) {
                foreach ($records as $record) {
                    Gate::forUser($user)->authorize($ability, $record);
                }
            }

            $input[$field] = is_array($input[$field]) ? $records : $records[0];
        }

        return $input;
    }

    /**
     * Locks every record handed over in the input, always in the same order
     * (field name, then key), and hands over the fresh copies.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function lockRecords(array $input): array
    {
        $fields = array_keys($this->records());
        sort($fields);

        foreach ($fields as $field) {
            $value = $input[$field] ?? null;

            if ($value instanceof Model) {
                $input[$field] = self::lock($value);
            } elseif (is_array($value)) {
                $sorted = $value;
                usort($sorted, fn (Model $a, Model $b): int => strcmp((string) $a->getKey(), (string) $b->getKey()));
                $locked = [];

                foreach ($sorted as $record) {
                    $locked[(string) $record->getKey()] = self::lock($record);
                }

                $input[$field] = array_map(fn (Model $record): Model => $locked[(string) $record->getKey()], $value);
            }
        }

        return $input;
    }

    /**
     * The record read again with a row lock, inside the transaction: the
     * refusal sees what is stored now, and nobody can change it until the
     * operation ends. A record deleted in the meantime is not found (404).
     */
    private static function lock(Model $record): Model
    {
        return $record->newQuery()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array<string, string>|null  $reasons
     */
    private static function refuseWhen(?array $reasons): void
    {
        if ($reasons !== null) {
            throw ValidationException::withMessages($reasons);
        }
    }
}
