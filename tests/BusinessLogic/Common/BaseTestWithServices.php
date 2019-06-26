<?php

namespace Logeecom\Tests\BusinessLogic\Common;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;

/**
 * Class BaseTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Common
 */
abstract class BaseTestWithServices extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestShopConfiguration
     */
    public $shopConfig;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $instance = $this;

        $this->shopConfig = new TestShopConfiguration();

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($instance) {
                return $instance->shopConfig;
            }
        );
    }
}
