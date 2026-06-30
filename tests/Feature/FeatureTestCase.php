<?php

namespace Vandet\ApiResponse\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Vandet\ApiResponse\ApiResponseServiceProvider;

abstract class FeatureTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ApiResponseServiceProvider::class];
    }
}
