<?php

namespace Logeecom\Tests\BusinessLogic\IntegrationRegistration;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\IntegrationRegistration\TestModuleResetService;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;
use Packlink\BusinessLogic\WebHook\IntegrationRegistrationWebhookEventHandler;

class IntegrationRegistrationWebhookHandlerTest extends BaseTestWithServices
{
    /**
     * The integration ID stored in config, shared across tests.
     */
    const STORED_INTEGRATION_ID = 'test-integration-id';

    /**
     * The webhook secret stored in config, shared across tests.
     */
    const WEBHOOK_SECRET = 'test-webhook-secret';

    /**
     * @var TestShopConfiguration
     */
    private $configService;

    /**
     * @var TestModuleResetService
     */
    private $moduleResetService;

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
        $this->configService->setWebhookSecret(self::WEBHOOK_SECRET);

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

        // Simulate the webhook secret header being present by default
        $_SERVER[IntegrationRegistrationWebhookEventHandler::WEBHOOK_SECRET_HEADER] = self::WEBHOOK_SECRET;
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        unset($_SERVER[IntegrationRegistrationWebhookEventHandler::WEBHOOK_SECRET_HEADER]);
        IntegrationRegistrationWebhookEventHandler::resetInstance();

        parent::after();
    }

    // -------------------------------------------------------------------------
    // Payload validation tests
    // -------------------------------------------------------------------------

    /**
     * A completely empty payload should be rejected and return false.
     */
    public function testHandleReturnsFalseForEmptyPayload()
    {
        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle('');

        $this->assertFalse($result);
    }

    /**
     * A payload missing integration_id should be rejected.
     */
    public function testHandleReturnsFalseWhenIntegrationIdMissing()
    {
        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            json_encode(array('status' => 'ENABLED'))
        );

        $this->assertFalse($result);
    }

    /**
     * A payload missing status should be rejected.
     */
    public function testHandleReturnsFalseWhenStatusMissing()
    {
        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            json_encode(array('integration_id' => self::STORED_INTEGRATION_ID))
        );

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // Auth header validation tests
    // -------------------------------------------------------------------------

    /**
     * A request with no webhook secret header should be rejected.
     */
    public function testHandleReturnsFalseWhenSecretHeaderMissing()
    {
        unset($_SERVER[IntegrationRegistrationWebhookEventHandler::WEBHOOK_SECRET_HEADER]);

        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('ENABLED', self::STORED_INTEGRATION_ID)
        );

        $this->assertFalse($result);
    }

    /**
     * A request with an incorrect webhook secret should be rejected.
     */
    public function testHandleReturnsFalseWhenSecretHeaderIsWrong()
    {
        $_SERVER[IntegrationRegistrationWebhookEventHandler::WEBHOOK_SECRET_HEADER] = 'wrong-secret';

        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('ENABLED', self::STORED_INTEGRATION_ID)
        );

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // ENABLED status tests
    // -------------------------------------------------------------------------

    /**
     * A valid ENABLED event with the correct integration ID should set status
     * to ENABLED and return true.
     */
    public function testHandleEnabledSetsIntegrationStatus()
    {
        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('ENABLED', self::STORED_INTEGRATION_ID)
        );

        $this->assertTrue($result);
        $this->assertEquals('ENABLED', $this->configService->getIntegrationStatus());
    }

    /**
     * An ENABLED event with a mismatched integration ID should not update status.
     */
    public function testHandleEnabledIgnoresMismatchedIntegrationId()
    {
        IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('ENABLED', 'wrong-integration-id')
        );

        $this->assertNotEquals('ENABLED', $this->configService->getIntegrationStatus());
    }

    // -------------------------------------------------------------------------
    // DISABLED status tests
    // -------------------------------------------------------------------------

    /**
     * A valid DISABLED event with the correct integration ID should set status
     * to DISABLED and return true.
     */
    public function testHandleDisabledSetsIntegrationStatus()
    {
        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('DISABLED', self::STORED_INTEGRATION_ID)
        );

        $this->assertTrue($result);
        $this->assertEquals('DISABLED', $this->configService->getIntegrationStatus());
    }

    /**
     * A DISABLED event with a mismatched integration ID should not update status.
     */
    public function testHandleDisabledIgnoresMismatchedIntegrationId()
    {
        IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('DISABLED', 'wrong-integration-id')
        );

        $this->assertNotEquals('DISABLED', $this->configService->getIntegrationStatus());
    }

    // -------------------------------------------------------------------------
    // DELETED status tests
    // -------------------------------------------------------------------------

    /**
     * A valid DELETED event with the correct integration ID should trigger
     * a module reset and return true.
     */
    public function testHandleDeletedTriggersModuleReset()
    {
        $result = IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('DELETED', self::STORED_INTEGRATION_ID)
        );

        $this->assertTrue($result);
        $this->assertTrue($this->moduleResetService->wasResetCalled());
    }

    /**
     * A DELETED event with a mismatched integration ID should not trigger reset.
     */
    public function testHandleDeletedIgnoresMismatchedIntegrationId()
    {
        IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('DELETED', 'wrong-integration-id')
        );

        $this->assertFalse($this->moduleResetService->wasResetCalled());
    }

    /**
     * When module reset fails, handle() should still return true (event was
     * handled) but log an error — verified via the shopLogger.
     */
    public function testHandleDeletedLogsErrorWhenResetFails()
    {
        $this->moduleResetService->setShouldFail(true);

        IntegrationRegistrationWebhookEventHandler::getInstance()->handle(
            $this->buildPayload('DELETED', self::STORED_INTEGRATION_ID)
        );

        $this->assertNotEmpty($this->shopLogger->loggedMessages);
        $lastMessage = end($this->shopLogger->loggedMessages);
        $this->assertContains('module reset failed', strtolower($lastMessage->getMessage()));
    }

    /**
     * Builds a mock JSON webhook payload string.
     *
     * @param string $status
     * @param string $integrationId
     *
     * @return string
     */
    private function buildPayload($status, $integrationId)
    {
        return json_encode(array(
            'integration_id' => $integrationId,
            'status'         => $status,
        ));
    }
}