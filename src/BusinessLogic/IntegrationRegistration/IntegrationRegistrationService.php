<?php

namespace Packlink\BusinessLogic\IntegrationRegistration;

use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationDataProviderInterface;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationServiceInterface;

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

    public function __construct($proxy, $dataProvider)
    {
        $this->proxy = $proxy;
        $this->dataProvider = $dataProvider;
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
        $existingId = $this->dataProvider->getIntegrationId();
        if (!empty($existingId)) {
            return $existingId;
        }

        $payload = $this->dataProvider->getRegistrationPayload();

        $integrationId = $this->proxy->registerIntegration($payload);
        $this->dataProvider->setIntegrationId($integrationId);

        return $integrationId;
    }

    /**
     * Disconnects the integration from Packlink.
     *
     * @return bool|void
     */
    public function disconnectIntegration()
    {
        $integrationId = $this->dataProvider->getIntegrationId();

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
     * Returns the persisted integration ID if present as class variable,
     * otherwise, if returns the ID from database if present.
     *
     * @return string|null Integration ID.
     */
    public function getIntegrationId()
    {
        return $this->dataProvider->getIntegrationId();
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

        return $this->registerIntegration();
    }
}
