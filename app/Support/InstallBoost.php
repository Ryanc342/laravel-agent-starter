<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Console\InstallCommand;

final class InstallBoost extends InstallCommand
{
    public function handle(): int
    {
        $status = parent::handle();

        if ($status !== self::SUCCESS) {
            return $status;
        }

        $files = array_values(array_filter(
            ['.mcp.json', 'boost.json'],
            fn (string $file): bool => File::exists(base_path($file)),
        ));

        if ($files === []) {
            return self::SUCCESS;
        }

        return Process::path(base_path())->run(
            ['npm', 'exec', '--no', '--', 'vp', 'fmt', ...$files],
            function (string $type, string $output): void {
                $this->output->write($output);
            },
        )->exitCode();
    }
}
