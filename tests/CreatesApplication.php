<?php

namespace Tests;

use Dotenv\Dotenv;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    private const TEST_ENV_FILE = '.env.testing';

    private function loadTestingEnvironment(): void
    {
        Dotenv::createUnsafeMutable(dirname(__DIR__), self::TEST_ENV_FILE)->safeLoad();
    }

    private function testingConfigCachePath(): ?string
    {
        $path = $_ENV['APP_CONFIG_CACHE'] ?? $_SERVER['APP_CONFIG_CACHE'] ?? getenv('APP_CONFIG_CACHE');

        if ($path === false || $path === null || $path === '') {
            return null;
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : dirname(__DIR__).DIRECTORY_SEPARATOR.$path;
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->loadTestingEnvironment();

        if (($configCache = $this->testingConfigCachePath()) !== null && file_exists($configCache)) {
            unlink($configCache);
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
