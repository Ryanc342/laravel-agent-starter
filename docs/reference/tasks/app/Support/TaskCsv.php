<?php

namespace App\Support;

use App\Models\Task;
use RuntimeException;

/**
 * The CSV file of tasks: its columns in one place, for the export (and the
 * import's template). UTF-8 with a byte order mark so Excel shows non-English
 * text correctly.
 */
final class TaskCsv
{
    /** The column names of the first line. The file format is a contract, so they are not translated. */
    public const array HEADERS = ['Title', 'Status', 'Due date'];

    /** The columns the import reads. Other columns (the export's Status) are ignored. */
    public const array IMPORT_HEADERS = ['Title', 'Due date'];

    /**
     * Writes the import template to the response body: the byte order mark and the column names, ready to fill in.
     */
    public static function outputTemplate(): void
    {
        $stream = self::open();

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::IMPORT_HEADERS, escape: '');
        fclose($stream);
    }

    /**
     * Reads an uploaded file: whether it is UTF-8, its column names (lower case, trimmed) and its lines
     * below the first, keyed by the spreadsheet's row number (the first line is row 1) so an error can
     * say "Row 3". Blank lines are skipped but still counted. A missing or blank value is null.
     *
     * @return array{utf8: bool, columns: list<string>, rows: array<int, array{title: string|null, due_on: string|null}>}
     */
    public static function read(string $path): array
    {
        $content = (string) file_get_contents($path);
        $utf8 = mb_check_encoding($content, 'UTF-8');
        $content = str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;

        $stream = fopen('php://memory', 'r+');

        if ($stream === false) {
            throw new RuntimeException('The file could not be read.');
        }

        fwrite($stream, $content);
        rewind($stream);

        $header = fgetcsv($stream, escape: '');
        $columns = $header === false ? [] : array_map(fn (?string $name): string => mb_strtolower(trim((string) $name)), $header);
        $title = array_search('title', $columns, true);
        $due = array_search('due date', $columns, true);

        $rows = [];
        $number = 1;

        while (($record = fgetcsv($stream, escape: '')) !== false) {
            $number++;

            if (trim(implode('', array_map(strval(...), $record))) === '') {
                continue;
            }

            $rows[$number] = [
                'title' => $title === false ? null : self::value($record[$title] ?? null),
                'due_on' => $due === false ? null : self::value($record[$due] ?? null),
            ];
        }

        fclose($stream);

        return ['utf8' => $utf8, 'columns' => $columns, 'rows' => $rows];
    }

    /**
     * Writes the file to the response body, line by line, so a long list never sits in memory.
     *
     * @param  iterable<Task>  $tasks
     */
    public static function output(iterable $tasks): void
    {
        $stream = self::open();

        self::write($tasks, $stream);
        fclose($stream);
    }

    /**
     * Writes the whole file: the byte order mark, the column names, then one line per task.
     *
     * @param  iterable<Task>  $tasks
     * @param  resource  $stream
     */
    public static function write(iterable $tasks, $stream): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS, escape: '');

        foreach ($tasks as $task) {
            fputcsv($stream, [
                self::text($task->title),
                $task->status->label(),
                $task->due_on?->toDateString() ?? '',
            ], escape: '');
        }
    }

    /**
     * @return resource
     */
    private static function open()
    {
        $stream = fopen('php://output', 'w');

        if ($stream === false) {
            throw new RuntimeException('The response body could not be opened.');
        }

        return $stream;
    }

    private static function value(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * A cell that starts with = + - or @ would run as a formula in a spreadsheet; a leading quote makes it text.
     */
    private static function text(string $value): string
    {
        return $value !== '' && str_contains('=+-@', $value[0]) ? "'".$value : $value;
    }
}
