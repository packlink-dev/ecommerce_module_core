<?php

namespace Packlink\BusinessLogic\IntegrationRegistration;

use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\IntegrationRegistration\DTO\IntegrationRegistrationPayload;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationDataProviderInterface;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;

/**
 * Class IntegrationRegistrationService.
 *
 * @package Packlink\BusinessLogic\IntegrationRegistration
 */
class IntegrationRegistrationService implements IntegrationRegistrationServiceInterface
{
    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * @var IntegrationRegistrationDataProviderInterface
     */
    private $dataProvider;

    /**
     * @var Configuration
     */
    private $configService;

    /**
     * IntegrationRegistrationService constructor.
     *
     * @param Proxy $proxy
     * @param IntegrationRegistrationDataProviderInterface $dataProvider
     * @param Configuration $configService
     */
    public function __construct($proxy, $dataProvider, $configService)
    {
        $this->proxy = $proxy;
        $this->dataProvider = $dataProvider;
        $this->configService = $configService;
    }

    /**
     * Registers the integration with Packlink and saves integration ID from the response into ConfigEntity.
     *
     * @return null|string Integration identifier or null if request fails
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function registerIntegration()
    {
        $existingId = $this->configService->getIntegrationId();
        if (!empty($existingId)) {
            return $existingId;
        }

        $payload = new IntegrationRegistrationPayload(
            $this->dataProvider->getIntegrationType(),
            $this->dataProvider->getIntegrationGuid(),
            $this->dataProvider->getIntegrationName(),
            'X-Packlink-Webhook-Secret',
            $this->dataProvider->getWebhookSecret(),
            $this->dataProvider->getIntegrationWebhookStatusUpdateUrl()
        );

        $integrationId = $this->proxy->registerIntegration($payload);
        $this->configService->setIntegrationId($integrationId);

        return $integrationId;
    }

    /**
     * Disconnects the integration from Packlink.
     *
     * @return bool|void
     */
    public function disconnectIntegration()
    {
        $integrationId = $this->configService->getIntegrationId();

        // Must have a check for legacy merchants that uninstall without ever registering
        if (empty($integrationId)) {
            return;
        }

        try {
            $success = $this->proxy->disconnectIntegration($integrationId);
            if (!$success) {
                Logger::logError(
                    'Packlink integration disconnect failed: API returned false'
                );

                return false;
            }
        } catch (Exception $e) {
            Logger::logError(
                'Packlink integration disconnect failed: ' . $e->getMessage()
            );

            return false;
        }

        return true;
    }

    /**
     * Returns the persisted integration ID.
     *
     * @return string|null Integration ID.
     */
    public function getIntegrationId()
    {
        return $this->configService->getIntegrationId();
    }

    /**
     * Updates the integration URL. Packlink team will consider adding a dedicated endpoint
     * for webhook URL update in the future. For now, re-registering integration will do.
     *
     * @return null|string Integration identifier or null if re-registration fails
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function updateIntegrationUrl()
    {
        if (!$this->disconnectIntegration()) {
            return null;
        }
        $this->dataProvider->deleteIntegrationData();
        $this->configService->setIntegrationId(null);

        return $this->registerIntegration();
    }

}
