<?php

namespace Packlink\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;

/**
 * Class AbstractIntegrationEventHandler.
 *
 * Base class for concrete webhook status handlers. Provides shared
 * integration ID validation logic.
 *
 * @package Packlink\BusinessLogic\WebHook\WebhookHandler
 */
abstract class AbstractIntegrationEventHandler
{
    /**
     * Handles the webhook event for the given integration ID.
     *
     * @param string $integrationId The integration_id from the webhook payload.
     *
     * @return void
     */
    abstract public function handle($integrationId);

    /**
     * Validates the integration ID received in the webhook payload
     * against the stored one.
     *
     * @param string $integrationId
     * @param string $action Human-readable action name for logging.
     *
     * @return bool
     */
    protected function isIntegrationIdValid($integrationId, $action)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $storedIntegrationId = $configService->getIntegrationId();

        if ($integrationId !== $storedIntegrationId) {
            Logger::logWarning(
                'Packlink registration webhook: integration_id mismatch on ' . $action . '.',
                'Core',
                array(
                    'received' => $integrationId,
                    'stored'   => $storedIntegrationId,
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Returns the Configuration service instance.
     *
     * @return Configuration
     */
    protected function getConfigService()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        return $configService;
    }
}
