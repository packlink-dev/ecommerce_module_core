<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\WebHook\WebhookHandler\IntegrationEventHandlerFactory;
use Packlink\BusinessLogic\WebHook\WebhookHandler\IntegrationEventStatuses;

/**
 * Class IntegrationEventHandlerFactoryTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler
 */
class IntegrationEventHandlerFactoryTest extends BaseTestWithServices
{
    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        parent::after();
    }

    public function testCreateReturnsEnabledHandler()
    {
        $handler = IntegrationEventHandlerFactory::create(IntegrationEventStatuses::STATUS_ENABLED);

        $this->assertInstanceOf(
            'Packlink\BusinessLogic\WebHook\WebhookHandler\EnabledStatusHandler',
            $handler
        );
    }

    public function testCreateReturnsDisabledHandler()
    {
        $handler = IntegrationEventHandlerFactory::create(IntegrationEventStatuses::STATUS_DISABLED);

        $this->assertInstanceOf(
            'Packlink\BusinessLogic\WebHook\WebhookHandler\DisabledStatusHandler',
            $handler
        );
    }

    public function testCreateReturnsDeletedHandler()
    {
        $handler = IntegrationEventHandlerFactory::create(IntegrationEventStatuses::STATUS_DELETED);

        $this->assertInstanceOf(
            'Packlink\BusinessLogic\WebHook\WebhookHandler\DeletedStatusHandler',
            $handler
        );
    }

    public function testCreateReturnsNullForUnknownStatus()
    {
        $handler = IntegrationEventHandlerFactory::create('UNKNOWN');

        $this->assertNull($handler);
    }

    public function testCreateReturnsAbstractIntegrationEventHandlerInstance()
    {
        $handler = IntegrationEventHandlerFactory::create(IntegrationEventStatuses::STATUS_ENABLED);

        $this->assertInstanceOf(
            'Packlink\BusinessLogic\WebHook\WebhookHandler\AbstractIntegrationEventHandler',
            $handler
        );
    }
}
