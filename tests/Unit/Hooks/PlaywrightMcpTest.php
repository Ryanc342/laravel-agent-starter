<?php

use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

function playwrightWorkspace(): string
{
    $workspace = sys_get_temp_dir().'/ezdev-playwright-'.bin2hex(random_bytes(6));
    mkdir("{$workspace}/bin", 0777, true);
    file_put_contents("{$workspace}/bin/npx", <<<'JS'
#!/usr/bin/env node
const fs = require('node:fs');
const readline = require('node:readline');
const args = process.argv.slice(2);
const output = args[args.indexOf('--output-dir') + 1];
fs.writeFileSync('arguments.json', JSON.stringify(args));
fs.writeFileSync(output + '/screenshot.png', 'temporary');
if (process.env.FAKE_EXIT === 'early-error') process.exit(5);
readline.createInterface({ input: process.stdin }).on('line', (line) => {
    const request = JSON.parse(line);
    if (request.method === 'tools/call' && request.params.name === 'browser_snapshot') {
        process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id: request.id, result: ['tool-error', 'close-error'].includes(process.env.FAKE_EXIT) ? { isError: true } : {} }) + '\n');
    }
    if (request.method === 'tools/call' && request.params.name === 'browser_close') {
        if (process.env.FAKE_EXIT === 'close-error') {
            require('node:child_process').spawn(process.execPath, ['-e', 'setTimeout(() => {}, 5000)'], { stdio: 'inherit' });
            process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id: request.id, result: { isError: true } }) + '\n');
            setTimeout(() => {}, 5000);
            return;
        }
        process.stdout.write(JSON.stringify({ jsonrpc: '2.0', id: request.id, result: {} }) + '\n');
        setTimeout(() => {
            fs.writeFileSync('after-close.json', JSON.stringify(fs.existsSync(output + '/screenshot.png')));
            process.exit(process.env.FAKE_EXIT === 'error' ? 4 : 0);
        }, 200);
    }
});
JS);
    chmod("{$workspace}/bin/npx", 0755);
    register_shutdown_function(fn () => (new Process(['rm', '-rf', $workspace]))->run());

    return $workspace;
}

function playwrightRun(string $workspace, string $exit = 'success'): Process
{
    $script = dirname(__DIR__, 3).'/scripts/playwright-mcp';
    $process = new Process(['node', $script], $workspace, [
        'PATH' => "{$workspace}/bin:".getenv('PATH'),
        'FAKE_EXIT' => $exit,
    ]);
    $process->setInput(json_encode(['jsonrpc' => '2.0', 'id' => 17, 'method' => 'tools/call', 'params' => ['name' => 'browser_close', 'arguments' => new stdClass]])."\n");
    $process->run();

    return $process;
}

test('browser checks are headless and temporary screenshots are removed when the browser closes', function () {
    $workspace = playwrightWorkspace();
    file_put_contents("{$workspace}/user.png", 'keep');

    $process = playwrightRun($workspace);
    $args = json_decode((string) file_get_contents("{$workspace}/arguments.json"), true);

    expect($process->getExitCode())->toBe(0)
        ->and($args)->toContain('--headless', '--isolated', '--idle-timeout')
        ->and($args[array_search('--idle-timeout', $args, true) + 1])->toBe('0')
        ->and(json_decode((string) file_get_contents("{$workspace}/after-close.json"), true))->toBeFalse()
        ->and(file_get_contents("{$workspace}/user.png"))->toBe('keep')
        ->and(glob("{$workspace}/.playwright-mcp/session-*"))->toBe([]);
});

test('a failed browser session removes its temporary screenshots', function () {
    $workspace = playwrightWorkspace();
    $process = playwrightRun($workspace, 'error');

    expect($process->getExitCode())->toBe(4)
        ->and(glob("{$workspace}/.playwright-mcp/session-*"))->toBe([]);
});

test('the Windows launcher runs the npm CLI with Node instead of spawning the npx command shim', function () {
    $workspace = playwrightWorkspace();
    copy("{$workspace}/bin/npx", "{$workspace}/bin/node.exe");
    chmod("{$workspace}/bin/node.exe", 0755);
    file_put_contents("{$workspace}/platform.cjs", <<<'JS'
Object.defineProperty(process, 'platform', { value: 'win32' });
Object.defineProperty(process, 'execPath', { value: process.env.TEST_NODE_EXE });
JS);
    $process = new Process(['node', dirname(__DIR__, 3).'/scripts/playwright-mcp'], $workspace, [
        'NODE_OPTIONS' => "--require={$workspace}/platform.cjs",
        'TEST_NODE_EXE' => "{$workspace}/bin/node.exe",
    ]);
    $process->setInput(json_encode(['jsonrpc' => '2.0', 'id' => 17, 'method' => 'tools/call', 'params' => ['name' => 'browser_close', 'arguments' => new stdClass]])."\n");
    $process->run();
    $args = json_decode((string) file_get_contents("{$workspace}/arguments.json"), true);

    expect($process->getExitCode())->toBe(0)
        ->and($args[0])->toBe("{$workspace}/bin/node_modules/npm/bin/npx-cli.js")
        ->and($args)->toContain('--headless', '--isolated');
});

test('a browser server that fails before close still removes its temporary screenshots', function () {
    $workspace = playwrightWorkspace();
    $process = playwrightRun($workspace, 'early-error');

    expect($process->getExitCode())->toBe(5)
        ->and(glob("{$workspace}/.playwright-mcp/session-*"))->toBe([]);
});

test('a failed browser check closes and cleans itself without an explicit close request', function () {
    $workspace = playwrightWorkspace();
    $input = new InputStream;
    $process = new Process(['node', dirname(__DIR__, 3).'/scripts/playwright-mcp'], $workspace, [
        'PATH' => "{$workspace}/bin:".getenv('PATH'),
        'FAKE_EXIT' => 'tool-error',
    ]);
    $process->setTimeout(3);
    $process->setInput($input);
    $process->start();
    $input->write(json_encode(['jsonrpc' => '2.0', 'id' => 18, 'method' => 'tools/call', 'params' => ['name' => 'browser_snapshot', 'arguments' => new stdClass]])."\n");
    $process->wait();
    $input->close();

    expect($process->getExitCode())->toBe(0)
        ->and(json_decode((string) file_get_contents("{$workspace}/after-close.json"), true))->toBeFalse()
        ->and(glob("{$workspace}/.playwright-mcp/session-*"))->toBe([]);
});

test('an active review keeps its screenshot until the browser is closed', function () {
    $workspace = playwrightWorkspace();
    $input = new InputStream;
    $process = new Process(['node', dirname(__DIR__, 3).'/scripts/playwright-mcp'], $workspace, [
        'PATH' => "{$workspace}/bin:".getenv('PATH'),
    ]);
    $process->setTimeout(3);
    $process->setInput($input);
    $process->start();
    $input->write(json_encode(['jsonrpc' => '2.0', 'id' => 20, 'method' => 'tools/call', 'params' => ['name' => 'browser_snapshot', 'arguments' => new stdClass]])."\n");
    $deadline = microtime(true) + 2;
    while (! is_file("{$workspace}/arguments.json") && microtime(true) < $deadline) {
        usleep(10000);
    }
    usleep(300000);

    expect(glob("{$workspace}/.playwright-mcp/session-*/screenshot.png"))->toHaveCount(1)
        ->and(is_file("{$workspace}/after-close.json"))->toBeFalse();

    $input->write(json_encode(['jsonrpc' => '2.0', 'id' => 21, 'method' => 'tools/call', 'params' => ['name' => 'browser_close', 'arguments' => new stdClass]])."\n");
    $process->wait();
    $input->close();

    expect($process->getExitCode())->toBe(0)
        ->and(glob("{$workspace}/.playwright-mcp/session-*"))->toBe([]);
});

test('a failed automatic close stops the browser process group and removes temporary screenshots', function () {
    $workspace = playwrightWorkspace();
    $input = new InputStream;
    $process = new Process(['node', dirname(__DIR__, 3).'/scripts/playwright-mcp'], $workspace, [
        'PATH' => "{$workspace}/bin:".getenv('PATH'),
        'FAKE_EXIT' => 'close-error',
    ]);
    $process->setTimeout(3);
    $process->setInput($input);
    $process->start();
    $input->write(json_encode(['jsonrpc' => '2.0', 'id' => 19, 'method' => 'tools/call', 'params' => ['name' => 'browser_snapshot', 'arguments' => new stdClass]])."\n");
    $process->wait();
    $input->close();

    expect($process->getExitCode())->toBe(1)
        ->and(glob("{$workspace}/.playwright-mcp/session-*"))->toBe([]);
});

test('a new session removes only stale owned sessions and preserves active and user files', function () {
    $workspace = playwrightWorkspace();
    $base = "{$workspace}/.playwright-mcp";
    mkdir("{$base}/session-99999999-deadbeefdeadbeef", 0777, true);
    file_put_contents("{$base}/session-99999999-deadbeefdeadbeef/.ezdev-playwright", 'temporary browser session');
    file_put_contents("{$base}/session-99999999-deadbeefdeadbeef/old.png", 'stale');
    mkdir("{$base}/session-".getmypid().'-aaaaaaaaaaaaaaaa');
    file_put_contents("{$base}/session-".getmypid().'-aaaaaaaaaaaaaaaa/.ezdev-playwright', 'temporary browser session');
    file_put_contents("{$base}/session-".getmypid().'-aaaaaaaaaaaaaaaa/live.png', 'active');
    mkdir("{$base}/user-files");
    file_put_contents("{$base}/user-files/keep.png", 'user');
    mkdir("{$base}/session-99999998-bbbbbbbbbbbbbbbb");
    file_put_contents("{$base}/session-99999998-bbbbbbbbbbbbbbbb/keep.png", 'unmarked');

    expect(playwrightRun($workspace)->getExitCode())->toBe(0)
        ->and(is_dir("{$base}/session-99999999-deadbeefdeadbeef"))->toBeFalse()
        ->and(file_get_contents("{$base}/session-".getmypid().'-aaaaaaaaaaaaaaaa/live.png'))->toBe('active')
        ->and(file_get_contents("{$base}/session-99999998-bbbbbbbbbbbbbbbb/keep.png"))->toBe('unmarked')
        ->and(file_get_contents("{$base}/user-files/keep.png"))->toBe('user');
});
