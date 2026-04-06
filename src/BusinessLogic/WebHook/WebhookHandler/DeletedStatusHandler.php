<?php

namespace Packlink\BusinessLogic\WebHook\WebhookHandler;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;

/**
 * Class DeletedStatusHandler.
 *
 * Handles the DELETED integration status by triggering a full module reset.
 *
 * @package Packlink\BusinessLogic\WebHook\WebhookHandler
 */
class DeletedStatusHandler extends AbstractIntegrationEventHandler
{
    /**
     * @inheritdoc
     */
    public function handle($integrationId)
    {
        if (!$this->isIntegrationIdValid($integrationId, 'disconnect')) {
            return;
        }

        /** @var ModuleResetServiceInterface $resetService */
        $resetService = ServiceRegister::getService(ModuleResetServiceInterface::CLASS_NAME);

        if (!$resetService->resetModule()) {
            Logger::logError('Packlink registration webhook: module reset failed.');
        }
    }
}
