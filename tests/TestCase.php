<?php

namespace RenokiCo\Ec2Metadata\Test;

use Orchestra\Testbench\TestCase as Orchestra;
use RenokiCo\Ec2Metadata\Ec2Metadata;

abstract class TestCase extends Orchestra
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        Ec2Metadata::deleteToken();
        Ec2Metadata::version('latest');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            //
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');
    }
}
