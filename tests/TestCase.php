<?php

namespace Nickpoulos\SvelteDirect\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Nickpoulos\SvelteDirect\SvelteDirectServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            SvelteDirectServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
