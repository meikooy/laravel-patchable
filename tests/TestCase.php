<?php

namespace Meiko\Patchable\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('patchable.namespace', 'Meiko\Patchable\Tests\Models');
    }
}
