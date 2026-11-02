<?php

use Dotenv\Dotenv;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->dockerDirectory = sys_get_temp_dir().'/ecommerce docker '.bin2hex(random_bytes(8));
    $filesystem = new Filesystem;
    $filesystem->makeDirectory($this->dockerDirectory.'/bin', 0755, true);
    $filesystem->makeDirectory($this->dockerDirectory.'/storage');
    $filesystem->copyDirectory(dirname(__DIR__, 2).'/deploy', $this->dockerDirectory.'/deploy');
    $filesystem->copy(dirname(__DIR__, 2).'/.env.docker.example', $this->dockerDirectory.'/.env.docker.example');

    file_put_contents($this->dockerDirectory.'/bin/docker', <<<'BASH'
#!/usr/bin/env bash
set -eu
printf '%s\0' "$@" >> "$DOCKER_TEST_LOG"
if [[ -n "${DOCKER_TEST_FAIL_COMMAND:-}" && "$*" == *"$DOCKER_TEST_FAIL_COMMAND"* ]]; then
    exit 9
fi
if [[ "$*" == *key:generate* ]]; then
    printf '%s\n' "$DOCKER_TEST_APP_KEY"
fi
BASH);
    chmod($this->dockerDirectory.'/bin/docker', 0755);

    file_put_contents($this->dockerDirectory.'/bin/php', <<<'BASH'
#!/usr/bin/env bash
set -eu
printf '%s\n' "$*" >> "$PHP_TEST_LOG"
if [[ "${PHP_TEST_FAIL_COMMAND:-}" == "$2" ]]; then
    exit 9
fi
BASH);
    chmod($this->dockerDirectory.'/bin/php', 0755);

    $this->runDockerScript = function (string $script, array $environment = []): Process {
        $process = new Process(['bash', $this->dockerDirectory.'/deploy/'.$script], sys_get_temp_dir(), array_merge([
            'PATH' => $this->dockerDirectory.'/bin:'.getenv('PATH'),
            'ECOMMERCE_ENV_FILE' => false,
            'DOCKER_TEST_LOG' => $this->dockerDirectory.'/docker.log',
            'DOCKER_TEST_APP_KEY' => 'base64:'.base64_encode(str_repeat('d', 32)),
            'PHP_TEST_LOG' => $this->dockerDirectory.'/php.log',
        ], $environment));
        $process->run();

        return $process;
    };
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->dockerDirectory);
});

it('creates a Docker environment with unique credentials without touching the local environment', function (): void {
    file_put_contents($this->dockerDirectory.'/.env', 'APP_KEY=existing-local-key');

    $process = ($this->runDockerScript)('setup.sh');
    $environment = Dotenv::parse(file_get_contents($this->dockerDirectory.'/.env.docker'));

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
        ->and($environment['APP_KEY'])->toBe('base64:'.base64_encode(str_repeat('d', 32)))
        ->and($environment['DB_PASSWORD'])->toMatch('/^[a-f0-9]{64}$/')
        ->and($environment['MYSQL_ROOT_PASSWORD'])->toMatch('/^[a-f0-9]{64}$/')
        ->and($environment['DB_PASSWORD'])->not->toBe($environment['MYSQL_ROOT_PASSWORD'])
        ->and(file_get_contents($this->dockerDirectory.'/.env'))->toBe('APP_KEY=existing-local-key')
        ->and(fileperms($this->dockerDirectory.'/.env.docker') & 0777)->toBe(0600);
});

it('keeps existing Docker credentials and app key when setup is repeated', function (): void {
    $initialSetup = ($this->runDockerScript)('setup.sh');
    expect($initialSetup->isSuccessful())->toBeTrue($initialSetup->getErrorOutput());
    $environment = file_get_contents($this->dockerDirectory.'/.env.docker');
    unlink($this->dockerDirectory.'/docker.log');

    $process = ($this->runDockerScript)('setup.sh');

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
        ->and(file_get_contents($this->dockerDirectory.'/.env.docker'))->toBe($environment)
        ->and(file_get_contents($this->dockerDirectory.'/docker.log'))->not->toContain('key:generate');
});

it('stops before starting services when the image build fails', function (): void {
    $process = ($this->runDockerScript)('setup.sh', ['DOCKER_TEST_FAIL_COMMAND' => 'build']);

    expect($process->getExitCode())->toBe(9)
        ->and(file_get_contents($this->dockerDirectory.'/docker.log'))->not->toContain('key:generate', "up\0")
        ->and(Dotenv::parse(file_get_contents($this->dockerDirectory.'/.env.docker'))['APP_KEY'])->toBe('');
});

it('does not create an environment when Docker Compose is unavailable', function (): void {
    $process = ($this->runDockerScript)('setup.sh', ['DOCKER_TEST_FAIL_COMMAND' => 'version']);

    expect($process->getExitCode())->toBe(9)
        ->and(file_exists($this->dockerDirectory.'/.env.docker'))->toBeFalse();
});

it('refuses to run Compose before the Docker environment exists', function (): void {
    $process = ($this->runDockerScript)('compose.sh');

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('bash deploy/setup.sh')
        ->and(file_exists($this->dockerDirectory.'/docker.log'))->toBeFalse();
});

it('resolves project paths from another directory and forwards arguments unchanged', function (): void {
    $environmentFile = $this->dockerDirectory.'/custom environment';
    file_put_contents($environmentFile, 'APP_KEY=custom-key');
    $process = new Process([
        'bash', $this->dockerDirectory.'/deploy/compose.sh', 'exec', 'app', 'php', 'artisan', 'test', '--filter=some test',
    ], sys_get_temp_dir(), [
        'PATH' => $this->dockerDirectory.'/bin:'.getenv('PATH'),
        'ECOMMERCE_ENV_FILE' => $environmentFile,
        'DOCKER_TEST_LOG' => $this->dockerDirectory.'/docker.log',
    ]);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
        ->and(explode("\0", file_get_contents($this->dockerDirectory.'/docker.log')))->toBe([
            'compose', '--project-directory', $this->dockerDirectory, '--env-file', $environmentFile,
            '-f', $this->dockerDirectory.'/deploy/compose.yml', 'exec', 'app', 'php', 'artisan', 'test', '--filter=some test', '',
        ]);
});

it('migrates on each initialization and seeds only once after successful initialization', function (): void {
    $process = new Process(['sh', 'deploy/init.sh'], $this->dockerDirectory, [
        'PATH' => $this->dockerDirectory.'/bin:'.getenv('PATH'),
        'PHP_TEST_LOG' => $this->dockerDirectory.'/php.log',
    ]);
    $process->mustRun();
    $process->mustRun();

    expect(file_exists($this->dockerDirectory.'/storage/.docker-initialized'))->toBeTrue()
        ->and(file($this->dockerDirectory.'/php.log', FILE_IGNORE_NEW_LINES))->toBe([
            'artisan migrate --force --no-interaction',
            'artisan db:seed --force --no-interaction',
            'artisan migrate --force --no-interaction',
        ]);
});

it('does not mark a failed initialization as complete and allows a later retry', function (string $command): void {
    $environment = [
        'PATH' => $this->dockerDirectory.'/bin:'.getenv('PATH'),
        'PHP_TEST_LOG' => $this->dockerDirectory.'/php.log',
        'PHP_TEST_FAIL_COMMAND' => $command,
    ];
    $process = new Process(['sh', 'deploy/init.sh'], $this->dockerDirectory, $environment);
    $process->run();

    expect($process->getExitCode())->toBe(9)
        ->and(file_exists($this->dockerDirectory.'/storage/.docker-initialized'))->toBeFalse();

    $environment['PHP_TEST_FAIL_COMMAND'] = false;
    (new Process(['sh', 'deploy/init.sh'], $this->dockerDirectory, $environment))->mustRun();

    expect(file_exists($this->dockerDirectory.'/storage/.docker-initialized'))->toBeTrue();
})->with(['migration' => 'migrate', 'seeding' => 'db:seed']);
