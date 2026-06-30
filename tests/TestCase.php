<?php

namespace Vandet\ApiResponse\Tests;

use Illuminate\Container\Container;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Provide a minimal translator so trans() calls in Handler/ResponseFactory
        // work in plain-PHPUnit unit tests (no Orchestra Testbench bootstrapping).
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'api-response', require __DIR__.'/../lang/en/messages.php');

        $translator = new Translator($loader, 'en');

        $container = new Container();
        $container->instance('translator', $translator);
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }
}
