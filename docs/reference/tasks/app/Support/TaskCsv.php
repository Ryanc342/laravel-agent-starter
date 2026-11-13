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

    /**
     * Writes the file to the response body, line by line, so a long list never sits in memory.
     *
     * @param  iterable<Task>  $tasks
     */
    public static function output(iterable $tasks): void
    {
        $stream = fopen('php://output', 'w');

        if ($stream === false) {
            throw new RuntimeException('The response body could not be opened.');
        }

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
     * A cell that starts with = + - or @ would run as a formula in a spreadsheet; a leading quote makes it text.
     */
    private static function text(string $value): string
    {
        return $value !== '' && str_contains('=+-@', $value[0]) ? "'".$value : $value;
    }
}
