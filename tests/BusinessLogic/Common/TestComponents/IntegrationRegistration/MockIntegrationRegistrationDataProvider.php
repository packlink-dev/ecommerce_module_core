<?php

namespace Logeecom\Tests\BusinessLogic\Common\TestComponents\IntegrationRegistration;

use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Packlink\BusinessLogic\IntegrationRegistration\AbstractIntegrationDataProvider;

class MockIntegrationRegistrationDataProvider extends AbstractIntegrationDataProvider
{

    public function __construct()
    {
        parent::__construct(new TestShopConfiguration());
    }

    public function getIntegrationGuid()
    {
        return 'mock_generated_guid';
    }

    public function getWebhookSecret()
    {
        return 'mock_webhook_secret';
    }

    public function getIntegrationType()
    {
        return 'mock_integration_type';
    }

    public function getIntegrationName()
    {
        return 'mockIntegration';
    }

    public function getIntegrationWebhookStatusUpdateUrl()
    {
        return 'https://mock.url/webhook';
    }

    public function deleteIntegrationData()
    {
    }
}
