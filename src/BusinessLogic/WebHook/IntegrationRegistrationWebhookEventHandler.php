<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\ModuleResetServiceInterface;

class IntegrationRegistrationWebhookEventHandler extends BaseService
{
    /**
     * Hardcoded header name Packlink uses to send the webhook secret.
     */
    const WEBHOOK_SECRET_HEADER = 'HTTP_X_PACKLINK_WEBHOOK_SECRET';

    /**
     * Integration statuses received from Packlink webhook.
     */
    const STATUS_ENABLED = 'ENABLED';
    const STATUS_DISABLED = 'DISABLED';
    const STATUS_DELETED = 'DELETED';

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    public function handle($input)
    {
        Logger::logDebug(
            'Packlink registration webhook received.',
            'Core',
            array('payload' => $input)
        );

        $payload = json_decode($input, false);

        if (!$this->validatePayload($payload)) {
            Logger::logWarning('Packlink registration webhook: invalid payload.');
            return false;
        }

        if (!$this->validateAuthHeader()) {
            Logger::logWarning('Packlink registration webhook: unauthorized request.');
            return false;
        }

        if ($payload->status === self::STATUS_ENABLED) {
            $this->handleActivation($payload->integration_id);
        }

        if ($payload->status === self::STATUS_DISABLED) {
            $this->handleDeactivation($payload->integration_id);
        }

        if ($payload->status === self::STATUS_DELETED) {
            $this->handleDisconnection($payload->integration_id);
        }

        return true;
    }

    /**
     * Validates the incoming JSON payload structure.
     *
     * @param \stdClass|null $payload
     *
     * @return bool
     */
    protected function validatePayload($payload)
    {
        return $payload !== null
            && !empty($payload->integration_id)
            && !empty($payload->status);
    }

    /**
     * Validates the X-Packlink-Webhook-Secret header against
     * the stored webhook secret in config.
     *
     * @return bool
     */
    protected function validateAuthHeader()
    {
        $incomingSecret = isset($_SERVER[self::WEBHOOK_SECRET_HEADER])
            ? $_SERVER[self::WEBHOOK_SECRET_HEADER]
            : null;

        if (empty($incomingSecret)) {
            return false;
        }

        $configService = ServiceRegister::getService(\Packlink\BusinessLogic\Configuration::CLASS_NAME);
        $storedSecret = $configService->getWebhookSecret();

        if (!empty($storedSecret) && $incomingSecret === $storedSecret) {
            return true;
        }

        return false;
    }

    /**
     * Handles the DELETED status by validating the integration ID
     * and triggering a full module reset.
     *
     * @param string $integrationId The integration_id from the webhook payload.
     */
    protected function handleDisconnection($integrationId)
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

    /**
     * Handles the ENABLED status by validating the integration ID
     * and saving the enabled status flag to the database.
     *
     * @param string $integrationId The integration_id from the webhook payload.
     */
    protected function handleActivation($integrationId)
    {
        if (!$this->isIntegrationIdValid($integrationId, 'activation')) {
            return;
        }

        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setIntegrationStatus(self::STATUS_ENABLED);

        Logger::logInfo(
            'Packlink integration has been enabled.',
            'Core',
            array('integrationId' => $integrationId, 'status' => $configService->getIntegrationStatus())
        );
    }

    /**
     * Handles the DISABLED status by validating the integration ID
     * and saving the disabled status flag to the database.
     *
     * @param string $integrationId The integration_id from the webhook payload.
     */
    protected function handleDeactivation($integrationId)
    {
        if (!$this->isIntegrationIdValid($integrationId, 'deactivation')) {
            return;
        }

        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setIntegrationStatus(self::STATUS_DISABLED);

        Logger::logInfo(
            'Packlink integration has been disabled.',
            'Core',
            array('integrationId' => $integrationId, 'status' => $configService->getIntegrationStatus())
        );
    }

    /**
     * Validates the integration ID received in the webhook payload.
     *
     * @param string $integrationId
     * @param string $action
     *
     * @return bool
     */
    protected function isIntegrationIdValid($integrationId, $action)
    {
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
}
