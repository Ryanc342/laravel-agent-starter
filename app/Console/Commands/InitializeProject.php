<?php

namespace App\Console\Commands;

use Dotenv\Dotenv;
use Dotenv\Parser\Lines;
use Dotenv\Parser\Parser;
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

        if ($database === '' && empty($configuration['url'])) {
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
        $contents = str_replace(["\r\n", "\r"], "\n", File::get($file));
        $replacements = [];
        $protectCredentials = array_any($values, static fn (string $value): bool => str_contains($value, 'DB_CONNECTION='));

        foreach ($values as $key => $value) {
            $encoded = '"'.strtr($value, [
                // Native Installer rewrites this literal anywhere in .env.
                'DB_CONNECTION=' => 'DB_CONNECTION${LARAVEL_STARTER_LITERAL_EQUALS}',
                '\\' => '\\\\',
                '"' => '\\"',
                '$' => '\\$',
                "\n" => '\n',
                "\r" => '\r',
                "\t" => '\t',
            ]).'"';
            $replacements[$key] = $key.'='.($key === 'DB_DATABASE' ? $value : $encoded);
        }

        $parser = new Parser;
        $entries = $parser->parse($contents);
        $lines = explode("\n", $contents);
        $output = [];
        $written = [];
        $cursor = 0;

        foreach (Lines::process($lines) as $index => $raw) {
            $entryLines = explode("\n", $raw);

            while ($lines[$cursor] !== $entryLines[0]) {
                $output[] = $lines[$cursor++];
            }

            $cursor += count($entryLines);
            $name = $entries[$index]->getName();

            if ($protectCredentials && $name === 'LARAVEL_STARTER_LITERAL_EQUALS') {
                continue;
            }

            $output[] = $replacements[$name] ?? $raw;
            $written[$name] = true;
        }

        $updated = implode("\n", [...$output, ...array_slice($lines, $cursor)]);
        $missing = array_diff_key($replacements, $written);

        if ($missing !== []) {
            $updated = rtrim($updated)."\n".implode("\n", $missing)."\n";
        }

        if ($protectCredentials) {
            $updated = 'LARAVEL_STARTER_LITERAL_EQUALS="="'."\n".$updated;
        }

        $parser->parse($updated);
        File::put($file, $updated);
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
