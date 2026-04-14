<?php

namespace Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\IntegrationRegistration\TestModuleResetService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;
use Packlink\BusinessLogic\WebHook\WebhookHandler\DeletedStatusHandler;

/**
 * Class DeletedStatusHandlerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\WebHook\WebhookHandler
 */
class DeletedStatusHandlerTest extends BaseTestWithServices
{
    const STORED_INTEGRATION_ID = 'test-integration-id';

    /**
     * @var TestShopConfiguration
     */
    private $configService;

    /**
     * @var TestModuleResetService
     */
    private $moduleResetService;

    /**
     * @var DeletedStatusHandler
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

        $this->moduleResetService = new TestModuleResetService();
        TestServiceRegister::registerService(
            ModuleResetServiceInterface::CLASS_NAME,
            function () use ($me) {
                return $me->moduleResetService;
            }
        );

        $this->handler = new DeletedStatusHandler();
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        parent::after();
    }

    public function testHandleTriggersModuleReset()
    {
        $this->handler->handle(self::STORED_INTEGRATION_ID);

        $this->assertTrue($this->moduleResetService->wasResetCalled());
    }

    public function testHandleIgnoresMismatchedIntegrationId()
    {
        $this->handler->handle('wrong-id');

        $this->assertFalse($this->moduleResetService->wasResetCalled());
    }

    public function testHandleLogsErrorWhenResetFails()
    {
        $this->moduleResetService->setShouldFail(true);

        $this->handler->handle(self::STORED_INTEGRATION_ID);

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $lastMessage = end($this->shopLogger->loggedMessages);
        $this->assertTrue(
            strpos(strtolower($lastMessage->getMessage()), 'module reset failed') !== false
        );
    }
}
