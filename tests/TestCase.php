<?php

namespace Vandet\ApiResponse\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Provide a minimal container so trans() and config() calls in
        // Handler/ResponseFactory work in plain-PHPUnit unit tests.
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'api-response', require __DIR__.'/../lang/en/messages.php');

        $container = new Container();
        $container->instance('translator', new Translator($loader, 'en'));
        $container->instance('config', new ConfigRepository(['app' => ['debug' => false]]));
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }
}
