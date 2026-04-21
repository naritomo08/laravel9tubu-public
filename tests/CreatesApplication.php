<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    private const TEST_APP_KEY = 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
    private const TEST_CONFIG_CACHE = __DIR__.'/../bootstrap/cache/testing-config.php';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_KEY='.self::TEST_APP_KEY);
        putenv('APP_CONFIG_CACHE='.self::TEST_CONFIG_CACHE);
        $_ENV['APP_KEY'] = self::TEST_APP_KEY;
        $_SERVER['APP_KEY'] = self::TEST_APP_KEY;
        $_ENV['APP_CONFIG_CACHE'] = self::TEST_CONFIG_CACHE;
        $_SERVER['APP_CONFIG_CACHE'] = self::TEST_CONFIG_CACHE;

        if (file_exists(self::TEST_CONFIG_CACHE)) {
            unlink(self::TEST_CONFIG_CACHE);
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('app.key', self::TEST_APP_KEY);

        return $app;
    }
}
