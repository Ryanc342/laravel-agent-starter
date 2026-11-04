<?php

namespace App\Console\Commands;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

final class InitializeProject extends Command
{
    protected $signature = 'starter:initialize';

    protected $description = 'Prepare a new starter-kit project with its own MySQL database and frontend assets';

    /** @var array<string, string> */
    private array $processEnvironment = [];

    public function handle(): int
    {
        if (config('starter-kit.defer_initialization')) {
            return self::SUCCESS;
        }

        $file = $this->laravel->environmentFilePath();

        if (! File::exists($file)) {
            File::copy(base_path('.env.example'), $file);
        }

        $environment = Dotenv::createArrayBacked($this->laravel->environmentPath())->load();
        $configuration = config('database.connections.mysql');
        $connection = (new ConfigurationUrlParser)->parseConfiguration($configuration);
        $database = (string) $connection['database'];

        if (($database === '' || $database === 'laravel') && empty($configuration['url'])) {
            $database = Str::slug(basename(base_path()), '_');
        }

        if ($database === '' || strlen($database) > 49 || preg_match('/^[a-zA-Z0-9_]+$/', $database) !== 1) {
            $this->components->error('Use a MySQL database name of at most 49 letters, digits or underscores so test databases remain independent.');

            return self::FAILURE;
        }

        $settings = [
            'DB_HOST' => (string) $connection['host'],
            'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => $database,
            'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'],
        ];
        $this->writeEnvironment($settings);

        if (empty($environment['APP_KEY'])) {
            config()->set('app.key', '');

            if ($this->call('key:generate', ['--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $readyEnvironment = Dotenv::createArrayBacked($this->laravel->environmentPath())->load();
        $this->processEnvironment = $settings + ['APP_KEY' => $readyEnvironment['APP_KEY']];

        foreach ([[PHP_BINARY, 'artisan', 'migrate', '--force'], ['npm', 'install'], ['npm', 'run', 'build']] as $command) {
            if (! $this->runProcess($command)) {
                return self::FAILURE;
            }
        }

        if (! File::exists(base_path('.git')) && ! $this->runProcess(['git', 'init', '--initial-branch=main'])) {
            return self::FAILURE;
        }

        $this->components->info('Project ready. Run composer dev to start development.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function writeEnvironment(array $values): void
    {
        $file = $this->laravel->environmentFilePath();
        $contents = File::get($file);

        foreach ($values as $key => $value) {
            $encoded = '"'.strtr($value, [
                '\\' => '\\\\',
                '"' => '\\"',
                '$' => '\\$',
                "\n" => '\n',
                "\r" => '\r',
                "\t" => '\t',
            ]).'"';
            $line = $key.'='.($key === 'DB_DATABASE' ? $value : $encoded);
            $pattern = '/^'.preg_quote($key, '/').'=(?:"(?:\\\\.|[^"\\\\])*"|\'[^\']*\'|[^\r\n]*)(?:[ \t]*#[^\r\n]*)?/ms';
            $contents = preg_match($pattern, $contents) === 1
                ? preg_replace_callback($pattern, static fn (): string => $line, $contents)
                : rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        File::put($file, $contents);
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command): bool
    {
        return Process::forever()->path(base_path())->env($this->processEnvironment)->run(
            $command,
            function (string $type, string $output): void {
                $this->output->write($output);
            },
        )->successful();
    }
}
