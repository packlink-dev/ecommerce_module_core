<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\WebHook\WebhookHandler\EnabledStatusHandler;
use Packlink\BusinessLogic\WebHook\WebhookHandler\IntegrationEventStatuses;

/**
 * Class EnabledStatusHandlerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler
 */
class EnabledStatusHandlerTest extends BaseTestWithServices
{
    const STORED_INTEGRATION_ID = 'test-integration-id';

    /**
     * @var TestShopConfiguration
     */
    private $configService;

    /**
     * @var EnabledStatusHandler
     */
    private $handler;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        $me = $this;

        $this->configService = new TestShopConfiguration();
        $this->configService->setIntegrationId(self::STORED_INTEGRATION_ID);

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($me) {
                return $me->configService;
            }
        );

        $this->handler = new EnabledStatusHandler();
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        parent::after();
    }

    public function testHandleSetsEnabledStatus()
    {
        $this->handler->handle(self::STORED_INTEGRATION_ID);

        $this->assertEquals(IntegrationEventStatuses::STATUS_ENABLED, $this->configService->getIntegrationStatus());
    }

    public function testHandleIgnoresMismatchedIntegrationId()
    {
        $this->handler->handle('wrong-id');

        $this->assertNotEquals(IntegrationEventStatuses::STATUS_ENABLED, $this->configService->getIntegrationStatus());
    }
}
