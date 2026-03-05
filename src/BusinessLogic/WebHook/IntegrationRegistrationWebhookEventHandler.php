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
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    public function handle($input) //TODO: yet to be tested when unblocked
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

        if ($payload->status === 'DELETED') {
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
    protected function handleDisconnection($integrationId) //TODO MUST TEST THIS - never got here due to packlink disconnecting not working!
    {
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $storedIntegrationId = $configService->getIntegrationId();

        if ($integrationId !== $storedIntegrationId) {
            Logger::logWarning(
                'Packlink registration webhook: integration_id mismatch.',
                'Core',
                array(
                    'received' => $integrationId,
                    'stored'   => $storedIntegrationId,
                )
            );

            return;
        }

        /** @var ModuleResetServiceInterface $resetService */
        $resetService = ServiceRegister::getService(ModuleResetServiceInterface::CLASS_NAME);

        if (!$resetService->resetModule()) {
            Logger::logError('Packlink registration webhook: module reset failed.');
        }
    }
}
