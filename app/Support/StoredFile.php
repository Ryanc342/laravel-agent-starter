<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonSerializable;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * The file part: a file a user uploaded, stored by the system. It is the only
 * code that touches file storage (PHPStan). An operation names its file
 * fields in files(); the operation part stores each upload after the
 * permission and input checks, removes it again when the operation is
 * refused or undone, and hands the operation this value instead of the upload.
 *
 * Files are stored under a name the system makes up, on private storage
 * (the local disk), unless the field is meant for everyone ('public').
 * A model keeps one in a text column with the cast StoredFile::class; a
 * download goes through Route::download(), which checks the Policy first.
 */
final class StoredFile implements Castable, JsonSerializable
{
    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly string $name,
        public readonly string $type,
        public readonly int $size,
    ) {}

    /**
     * @param  'private'|'public'  $visibility
     */
    public static function store(mixed $upload, string $visibility): self
    {
        if (! $upload instanceof UploadedFile) {
            throw new LogicException('A file field holds an uploaded file; give it a file rule in rules().');
        }

        $disk = $visibility === 'public' ? 'public' : 'local';
        $path = Storage::disk($disk)->putFileAs('files', $upload, Str::uuid()->toString().'.'.($upload->extension() ?: 'bin'));

        if ($path === false) {
            throw new RuntimeException("The uploaded file {$upload->getClientOriginalName()} could not be stored.");
        }

        return new self($disk, $path, $upload->getClientOriginalName(), (string) $upload->getMimeType(), (int) $upload->getSize());
    }

    /**
     * Deletes the file once the operation's writes are saved; when they are undone, the file stays.
     */
    public function deleteAfterCommit(): void
    {
        DB::afterCommit(fn () => $this->deleteNow());
    }

    /**
     * Deletes the file now. Used by the operation part to remove an upload of a refused operation.
     */
    public function deleteNow(?Throwable $while = null): void
    {
        if (! Storage::disk($this->disk)->delete($this->path)) {
            throw new RuntimeException("The stored file {$this->path} could not be deleted.", 0, $while);
        }
    }

    /**
     * The file as a download under its original name (App\Http\Controllers\DownloadController).
     */
    public function download(): StreamedResponse
    {
        $stream = Storage::disk($this->disk)->readStream($this->path)
            ?? throw new RuntimeException("The stored file {$this->path} is missing.");

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, $this->name, ['Content-Type' => $this->type]);
    }

    /**
     * @return array{disk: string, path: string, name: string, type: string, size: int}
     */
    public function jsonSerialize(): array
    {
        return ['disk' => $this->disk, 'path' => $this->path, 'name' => $this->name, 'type' => $this->type, 'size' => $this->size];
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @return CastsAttributes<StoredFile, mixed>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new /** @implements CastsAttributes<StoredFile, mixed> */ class implements CastsAttributes
        {
            public function get(Model $model, string $key, mixed $value, array $attributes): ?StoredFile
            {
                if ($value === null) {
                    return null;
                }

                $data = json_decode(is_string($value) ? $value : '', true, flags: JSON_THROW_ON_ERROR);

                if (! is_array($data) || ! is_string($data['disk'] ?? null) || ! is_string($data['path'] ?? null)
                    || ! is_string($data['name'] ?? null) || ! is_string($data['type'] ?? null) || ! is_int($data['size'] ?? null)) {
                    throw new LogicException("The column {$key} does not hold a stored file.");
                }

                return new StoredFile($data['disk'], $data['path'], $data['name'], $data['type'], $data['size']);
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): ?string
            {
                if ($value === null) {
                    return null;
                }

                if (! $value instanceof StoredFile) {
                    throw new LogicException("The column {$key} takes a StoredFile.");
                }

                return json_encode($value, JSON_THROW_ON_ERROR);
            }
        };
    }
}
