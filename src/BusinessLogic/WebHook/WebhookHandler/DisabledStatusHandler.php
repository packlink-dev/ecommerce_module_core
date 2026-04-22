<?php

namespace Packlink\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Infrastructure\Logger\Logger;

/**
 * Class DisabledStatusHandler.
 *
 * Handles the DISABLED integration status by saving the flag to configuration.
 *
 * @package Packlink\BusinessLogic\WebHook\WebhookHandler
 */
class DisabledStatusHandler extends AbstractIntegrationEventHandler
{
    /**
     * @inheritdoc
     */
    public function handle($integrationId)
    {
        if (!$this->isIntegrationIdValid($integrationId, 'deactivation')) {
            return;
        }

        $configService = $this->getConfigService();
        $configService->setIntegrationStatus(IntegrationEventStatuses::STATUS_DISABLED);

        Logger::logInfo(
            'Packlink integration has been disabled.',
            'Core',
            array('integrationId' => $integrationId, 'status' => $configService->getIntegrationStatus())
        );
    }
}
