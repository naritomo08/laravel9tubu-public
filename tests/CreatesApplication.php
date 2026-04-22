<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    private const TEST_APP_KEY = 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
    private const TEST_CONFIG_CACHE = __DIR__.'/../bootstrap/cache/testing-config.php';
    private const TEST_ENV = 'testing';
    private const TEST_DB_CONNECTION = 'mysql';
    private const TEST_DB_HOST = 'db.test';
    private const TEST_DB_PORT = '3306';
    private const TEST_DB_DATABASE = 'laravel_test';
    private const TEST_DB_USERNAME = 'phper';
    private const TEST_DB_PASSWORD = 'secret';

    private function setTestEnvironmentVariable(string $key, string $value): void
    {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $this->setTestEnvironmentVariable('APP_ENV', self::TEST_ENV);
        $this->setTestEnvironmentVariable('APP_KEY', self::TEST_APP_KEY);
        $this->setTestEnvironmentVariable('APP_CONFIG_CACHE', self::TEST_CONFIG_CACHE);
        $this->setTestEnvironmentVariable('DB_CONNECTION', self::TEST_DB_CONNECTION);
        $this->setTestEnvironmentVariable('DB_HOST', self::TEST_DB_HOST);
        $this->setTestEnvironmentVariable('DB_PORT', self::TEST_DB_PORT);
        $this->setTestEnvironmentVariable('DB_DATABASE', self::TEST_DB_DATABASE);
        $this->setTestEnvironmentVariable('DB_USERNAME', self::TEST_DB_USERNAME);
        $this->setTestEnvironmentVariable('DB_PASSWORD', self::TEST_DB_PASSWORD);

        if (file_exists(self::TEST_CONFIG_CACHE)) {
            unlink(self::TEST_CONFIG_CACHE);
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('app.key', self::TEST_APP_KEY);

        return $app;
    }
}
