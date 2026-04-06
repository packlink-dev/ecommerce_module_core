<?php

namespace Packlink\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Infrastructure\Logger\Logger;

/**
 * Class EnabledStatusHandler.
 *
 * Handles the ENABLED integration status by saving the flag to configuration.
 *
 * @package Packlink\BusinessLogic\WebHook\WebhookHandler
 */
class EnabledStatusHandler extends AbstractIntegrationEventHandler
{
    /**
     * @inheritdoc
     */
    public function handle($integrationId)
    {
        if (!$this->isIntegrationIdValid($integrationId, 'activation')) {
            return;
        }

        $configService = $this->getConfigService();
        $configService->setIntegrationStatus(IntegrationEventStatuses::STATUS_ENABLED);

        Logger::logInfo(
            'Packlink integration has been enabled.',
            'Core',
            array('integrationId' => $integrationId, 'status' => $configService->getIntegrationStatus())
        );
    }
}
