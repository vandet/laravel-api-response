<?php

namespace Vandet\ApiResponse\Tests;

use Vandet\ApiResponse\ApiResponseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ApiResponseServiceProvider::class,
        ];
    }
}
