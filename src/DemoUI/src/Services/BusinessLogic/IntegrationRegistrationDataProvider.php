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
        // The Packlink API only accepts a fixed set of platform types
        // (prestashop_module, woocommerce_module, ...), so the demo registers as one of them.
        return 'prestashop_module';
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
