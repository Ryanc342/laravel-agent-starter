<?php

/** One validation attempt, held for the lifetime of its runner, per worktree. */
final class CheckRun
{
    private $lock;

    public readonly string $id;

    public function __construct(private readonly string $directory)
    {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true)) {
            throw new RuntimeException("cannot create {$directory}");
        }
        // Close on exec: an orphaned child must not keep its dead parent's lock.
        $this->lock = fopen("{$directory}/check.lock", 'c+e');
        if ($this->lock === false || ! flock($this->lock, LOCK_EX | LOCK_NB)) {
            $this->close();
            throw new RuntimeException('A validation check is already running in this worktree. Wait for it to finish.');
        }
        $this->id = bin2hex(random_bytes(16));
        self::write("{$directory}/run.json", ['id' => $this->id, 'status' => 'running']);
        foreach (['checked', 'milestone-checked', 'checking.json', 'milestone-checking.json'] as $file) {
            if (is_file("{$directory}/{$file}") && ! unlink("{$directory}/{$file}")) {
                throw new RuntimeException("cannot invalidate {$file}");
            }
        }
    }

    public function succeed(): void
    {
        if ((self::read("{$this->directory}/run.json")['id'] ?? null) !== $this->id) {
            throw new RuntimeException('The validation run identity changed. Run the check again.');
        }
        self::write("{$this->directory}/run.json", ['id' => $this->id, 'status' => 'passed']);
    }

    public function close(): void
    {
        if (is_resource($this->lock)) {
            fclose($this->lock);
        }
        $this->lock = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function owner(string $directory): string
    {
        $id = getenv('EZDEV_CHECK_RUN_ID');
        $run = self::read("{$directory}/run.json");
        if (! is_string($id) || preg_match('/^[a-f0-9]{32}$/', $id) !== 1
            || ($run['id'] ?? null) !== $id || ($run['status'] ?? null) !== 'running' || ! self::busy($directory)) {
            throw new RuntimeException('No matching active validation run. Use composer check or composer check:milestone.');
        }

        return $id;
    }

    public static function passed(string $directory, string $fingerprint, bool $milestone = false): bool
    {
        $prefix = $milestone ? 'milestone-' : '';
        $record = self::read("{$directory}/{$prefix}checked");
        $run = self::read("{$directory}/run.json");

        return ($record['version'] ?? null) === 2
            && is_string($record['id'] ?? null)
            && ($record['fingerprint'] ?? null) === $fingerprint
            && ($run['id'] ?? null) === $record['id']
            && ($run['status'] ?? null) === 'passed'
            && ! self::busy($directory);
    }

    private static function busy(string $directory): bool
    {
        $lock = fopen("{$directory}/check.lock", 'c+e');
        if ($lock === false) {
            throw new RuntimeException('Cannot read the validation lock.');
        }
        try {
            return ! flock($lock, LOCK_EX | LOCK_NB);
        } finally {
            fclose($lock);
        }
    }

    public static function read(string $path): array
    {
        $value = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($value) ? $value : [];
    }

    public static function write(string $path, array $value): void
    {
        $temporary = $path.'.'.bin2hex(random_bytes(6));
        try {
            if (file_put_contents($temporary, json_encode($value, JSON_THROW_ON_ERROR)) === false || ! rename($temporary, $path)) {
                throw new RuntimeException("cannot write {$path}");
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
