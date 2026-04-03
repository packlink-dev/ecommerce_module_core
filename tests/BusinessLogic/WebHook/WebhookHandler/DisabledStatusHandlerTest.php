<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\WebHook\WebhookHandler\DisabledStatusHandler;
use Packlink\BusinessLogic\WebHook\WebhookHandler\IntegrationEventStatuses;

/**
 * Class DisabledStatusHandlerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler
 */
class DisabledStatusHandlerTest extends BaseTestWithServices
{
    const STORED_INTEGRATION_ID = 'test-integration-id';

    /**
     * @var TestShopConfiguration
     */
    private $configService;

    /**
     * @var DisabledStatusHandler
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

        $this->handler = new DisabledStatusHandler();
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        parent::after();
    }

    public function testHandleSetsDisabledStatus()
    {
        $this->handler->handle(self::STORED_INTEGRATION_ID);

        $this->assertEquals(IntegrationEventStatuses::STATUS_DISABLED, $this->configService->getIntegrationStatus());
    }

    public function testHandleIgnoresMismatchedIntegrationId()
    {
        $this->handler->handle('wrong-id');

        $this->assertNotEquals(IntegrationEventStatuses::STATUS_DISABLED, $this->configService->getIntegrationStatus());
    }
}
