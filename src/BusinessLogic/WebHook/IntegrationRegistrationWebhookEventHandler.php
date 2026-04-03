<?php

namespace Packlink\BusinessLogic\WebHook;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\WebHook\DTO\WebhookStatusPayload;
use Packlink\BusinessLogic\WebHook\Exceptions\WebhookAuthorizationException;
use Packlink\BusinessLogic\WebHook\Exceptions\WebhookPayloadValidationException;
use Packlink\BusinessLogic\WebHook\WebhookHandler\IntegrationEventHandlerFactory;

/**
 * Class IntegrationRegistrationWebhookEventHandler.
 *
 * Entry point for incoming integration status webhooks.
 * Validates payload and auth, then delegates to the appropriate status handler
 * resolved via IntegrationEventHandlerFactory.
 *
 * @package Packlink\BusinessLogic\WebHook
 */
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

    /**
     * Validates and dispatches an incoming webhook payload.
     *
     * @param string $input Raw JSON input from the webhook request.
     *
     * @return void
     *
     * @throws WebhookPayloadValidationException When the payload is invalid.
     * @throws WebhookAuthorizationException When the webhook secret header is missing or invalid.
     */
    public function handle($input)
    {
        Logger::logDebug(
            'Packlink registration webhook received.',
            'Core',
            array('payload' => $input)
        );

        $payload = $this->parseAndValidatePayload($input);
        $this->validateAuthHeader();

        $handler = IntegrationEventHandlerFactory::create($payload->getStatus());

        if ($handler === null) {
            Logger::logWarning(
                'Packlink registration webhook: unknown status.',
                'Core',
                array('status' => $payload->getStatus())
            );

            return;
        }

        $handler->handle($payload->getIntegrationId());
    }

    /**
     * Parses raw JSON input and validates the resulting payload.
     *
     * @param string $input Raw JSON string.
     *
     * @return WebhookStatusPayload
     *
     * @throws WebhookPayloadValidationException
     */
    protected function parseAndValidatePayload($input)
    {
        $decoded = json_decode($input, true);

        if (!is_array($decoded)) {
            Logger::logWarning('Packlink registration webhook: invalid payload.');
            throw new WebhookPayloadValidationException('Webhook payload is not valid JSON.');
        }

        $payload = WebhookStatusPayload::fromArray($decoded);

        if (!$payload->isValid()) {
            Logger::logWarning('Packlink registration webhook: invalid payload.');
            throw new WebhookPayloadValidationException(
                'Webhook payload is missing required fields (integration_id, status).'
            );
        }

        return $payload;
    }

    /**
     * Validates the X-Packlink-Webhook-Secret header against
     * the stored webhook secret in config.
     *
     * @return void
     *
     * @throws WebhookAuthorizationException
     */
    protected function validateAuthHeader()
    {
        $incomingSecret = isset($_SERVER[self::WEBHOOK_SECRET_HEADER])
            ? $_SERVER[self::WEBHOOK_SECRET_HEADER]
            : null;

        if (empty($incomingSecret)) {
            Logger::logWarning('Packlink registration webhook: unauthorized request.');
            throw new WebhookAuthorizationException('Webhook secret header is missing.');
        }

        /** @var \Packlink\BusinessLogic\Configuration $configService */
        $configService = ServiceRegister::getService(\Packlink\BusinessLogic\Configuration::CLASS_NAME);
        $storedSecret = $configService->getWebhookSecret();

        if (empty($storedSecret) || $incomingSecret !== $storedSecret) {
            Logger::logWarning('Packlink registration webhook: unauthorized request.');
            throw new WebhookAuthorizationException('Webhook secret does not match.');
        }
    }
}
