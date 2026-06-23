<?php

namespace Packlink\DemoUI\Services\BusinessLogic;

use Packlink\BusinessLogic\IntegrationRegistration\AbstractIntegrationDataProvider;
use Packlink\DemoUI\Services\Integration\UrlService;

/**
 * Class IntegrationRegistrationDataProvider.
 *
 * @package Packlink\DemoUI\Services\BusinessLogic
 */
class IntegrationRegistrationDataProvider extends AbstractIntegrationDataProvider
{
    public function getIntegrationType()
    {
        return 'demo_ui_module';
    }

    public function getIntegrationName()
    {
        return 'DemoUI';
    }

    public function getIntegrationWebhookStatusUpdateUrl()
    {
        return UrlService::getEndpointUrl('Webhook', 'handle');
    }
}
