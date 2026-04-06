<?php

namespace Packlink\BusinessLogic\IntegrationRegistration;

use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\IntegrationRegistration\Interfaces\IntegrationRegistrationDataProviderInterface;

/**
 * Abstract class AbstractIntegrationRegistrationDataProvider. Shared registration data among all integrations.
 */
abstract class AbstractIntegrationDataProvider implements IntegrationRegistrationDataProviderInterface
{
    /**
     * @var string|null integration identifier
     */
    private $integrationId = null;

    /**
     * @var Configuration $configService
     */
    private $configService;

    public function __construct($configService)
    {
        $this->configService = $configService;
    }

    /**
     * Returns the persisted integration GUID.
     *
     * @return string Integration GUID.
     */
    public function getIntegrationGuid()
    {
        $guid = $this->configService->getIntegrationGuid();
        if (!$guid) {
            $guid = \Logeecom\Infrastructure\Utility\GuidProvider::getInstance()->generateGuid();
            $this->configService->setIntegrationGuid($guid);
        }

        return $guid;
    }

    /**
     * Returns the persisted webhook secret.
     *
     * @return string Webhook secret used for authentication.
     */
    public function getWebhookSecret()
    {
        $secret = $this->configService->getWebhookSecret();
        if (!$secret) {
            $cryptoStrong = false;
            $bytes32 = openssl_random_pseudo_bytes(32, $cryptoStrong);

            if ($bytes32 === false || $cryptoStrong === false) {
                throw new \RuntimeException('Unable to generate a secure webhook secret.');
            }

            $secret = rtrim(strtr(base64_encode($bytes32), '+/', '-_'), '=');
            $this->configService->setWebhookSecret($secret);
        }

        return $secret;
    }

    /**
     * Returns the persisted integration ID if present as class variable,
     * otherwise, if returns the ID from database if present.
     *
     * @return string|null Integration ID.
     */
    public function getIntegrationId()
    {
        if ($this->integrationId) {
            return $this->integrationId;
        }

        $result = $this->configService->getIntegrationId();

        if ($result) {
            $this->integrationId = $result;
            return $this->integrationId;
        }

        return null;
    }

    /**
     * Saves Integration Identifier to database
     *
     * @param string $integrationId
     *
     * @return void
     */
    public function setIntegrationId($integrationId)
    {
        $this->integrationId = $integrationId;
        $this->configService->setIntegrationId($integrationId);
    }

    /**
     * Removes integration registration data from the database.
     *
     * @return void
     */
    public function deleteIntegrationData()
    {
        $this->integrationId = null;
        $this->configService->deleteIntegrationData();
    }

}
