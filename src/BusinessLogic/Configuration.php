<?php

namespace Packlink\BusinessLogic;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class Configuration.
 *
 * @package Packlink\BusinessLogic
 */
abstract class Configuration extends \Logeecom\Infrastructure\Configuration\Configuration
{

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Determines whether the drop-off shipping services are system supported.
     *
     * @return bool
     */
    public function dropOffShippingServicesSupported()
    {
        return true;
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    abstract public function getWebHookUrl();

    /**
     * Returns order draft source.
     *
     * @return string Order draft source.
     */
    abstract public function getDraftSource();

    /**
     * Gets the current version of the module/integration.
     *
     * @return string The version number.
     */
    abstract public function getModuleVersion();

    /**
     * Gets the name of the integrated e-commerce system.
     * This name is related to Packlink API which can be different from the official system name.
     *
     * @return string The e-commerce name.
     */
    abstract public function getECommerceName();

    /**
     * Gets the current version of the integrated e-commerce system.
     *
     * @return string The version number.
     */
    abstract public function getECommerceVersion();

    /**
     * Returns authorization token.
     *
     * @return string|null Authorization token if found; otherwise, NULL.
     */
    public function getAuthorizationToken()
    {
        return $this->getConfigValue('authToken') ?: null;
    }

    /**
     * Sets authorization token.
     *
     * @param string $token Authorization token.
     */
    public function setAuthorizationToken($token)
    {
        $this->saveConfigValue('authToken', $token);
    }

    /**
     * Resets authorization credentials to null.
     */
    public function resetAuthorizationCredentials()
    {
        $this->setAuthorizationToken(null);
    }

    /**
     * Returns user information from integration database.
     *
     * @return User|null User info.
     */
    public function getUserInfo()
    {
        $value = $this->getConfigValue('userInfo');

        return $value && is_array($value) ? User::fromArray($value) : null;
    }

    /**
     * Save user information in integration database.
     *
     * @param User $userInfo User information.
     */
    public function setUserInfo(User $userInfo)
    {
        $this->saveConfigValue('userInfo', $userInfo->toArray());
    }

    /**
     * Returns default Parcel object.
     *
     * @return ParcelInfo|null Default parcel object.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getDefaultParcel()
    {
        $value = $this->getConfigValue('defaultParcel');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return $value && is_array($value) ? FrontDtoFactory::get(ParcelInfo::CLASS_KEY, $value) : null;
    }

    /**
     * Sets default Parcel object.
     *
     * @param ParcelInfo $parcelInfo
     */
    public function setDefaultParcel(ParcelInfo $parcelInfo)
    {
        $this->saveConfigValue('defaultParcel', $parcelInfo->toArray());
    }

    /**
     * Returns default Warehouse object.
     *
     * @return Warehouse|null Default warehouse object.
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getDefaultWarehouse()
    {
        $value = $this->getConfigValue('defaultWarehouse');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return $value && is_array($value) ? FrontDtoFactory::get(Warehouse::CLASS_KEY, $value) : null;
    }

    /**
     * Sets default Warehouse object.
     *
     * @param Warehouse $warehouse Default warehouse object.
     */
    public function setDefaultWarehouse(Warehouse $warehouse)
    {
        $this->saveConfigValue('defaultWarehouse', $warehouse->toArray());
    }

    /**
     * Sets order status mapping configuration.
     *
     * Expected mapping format:
     *
     * [
     *      'shipped' => 1,
     *      'transit' => 7,
     *      ...
     * ]
     *
     * Keys in submitted array are order statuses available on Packlink.
     * Values are system specific order status identifiers.
     *
     * @param array $mappings As described above.
     */
    public function setOrderStatusMappings(array $mappings)
    {
        $this->saveConfigValue('orderStatusMappings', $mappings);
    }

    /**
     * Retrieves order status mappings.
     *
     * @return array | null  Order status mapping configuration.
     */
    public function getOrderStatusMappings()
    {
        return $this->getConfigValue('orderStatusMappings');
    }

    /**
     * Sets customs mappings.
     *
     * @param CustomsMapping $mapping
     *
     * @return void
     */
    public function setCustomsMappings(CustomsMapping $mapping)
    {
        $this->saveConfigValue('customsMappings', $mapping->toArray());
    }

    /**
     * @return CustomsMapping|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getCustomsMappings()
    {
        $value = $this->getConfigValue('customsMappings');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return $value && is_array($value) ? FrontDtoFactory::get(CustomsMapping::CLASS_KEY, $value) : null;
    }

    /**
     * Sets a flag that module setup is finished.
     */
    public function setSetupFinished()
    {
        $this->saveConfigValue('setupFinished', true);
    }

    /**
     * Gets a flag about finished setup.
     *
     * @return bool TRUE if setup is marked as finished; otherwise, FALSE.
     */
    public function isSetupFinished()
    {
        return $this->getConfigValue('setupFinished') ?: false;
    }


    /**
     * Retrieves integration status.
     *
     * @return string|null
     */
    public function getIntegrationStatus()
    {
        return $this->getConfigValue('integrationStatus');
    }

    /**
     * Sets integration status.
     *
     * @param string $status
     *
     * @return ConfigEntity
     */
    public function setIntegrationStatus($status)
    {
        return $this->saveConfigValue('integrationStatus', $status);
    }

    /**
     * Sets Integration identifier.
     *
     * @param string $integrationId ID of the integration.
     *
     * @return ConfigEntity
     */
    public function setIntegrationId($integrationId)
    {
        return $this->saveConfigValue('integrationId', $integrationId);
    }

    /**
     * Retrieves Integration identifier.
     *
     * @return string | null  IntegrationId configuration.
     */
    public function getIntegrationId()
    {
        return $this->getConfigValue('integrationId');
    }

    /**
     * Sets backup carrier ID.
     *
     * @param string $webhookSecret
     *
     * @return ConfigEntity
     */
    public function setWebhookSecret($webhookSecret)
    {
        return $this->saveConfigValue('webhookSecret', $webhookSecret);
    }

    /**
     * Retrieves Webhook secret.
     *
     * @return string | null  Webhook Secret configuration.
     */
    public function getWebhookSecret()
    {
        return $this->getConfigValue('webhookSecret');
    }

    /**
     * Sets Integration Guid.
     *
     * @param $integrationGuid
     *
     * @return ConfigEntity
     */
    public function setIntegrationGuid($integrationGuid)
    {
        return $this->saveConfigValue('integrationGuid', $integrationGuid);
    }

    /**
     * Retrieves Integration Guid.
     *
     * @return string | null  Integration Guid configuration.
     */
    public function getIntegrationGuid()
    {
        return $this->getConfigValue('integrationGuid');
    }

    /**
     * Removes integration registration data from the database
     * by annulling all integration-related configuration values.
     *
     * @return void
     */
    public function deleteIntegrationData()
    {
        $this->saveConfigValue('integrationId', null);
        $this->saveConfigValue('integrationGuid', null);
        $this->saveConfigValue('webhookSecret', null);
        $this->saveConfigValue('integrationStatus', null);
    }

    /**
     * Returns whether the integration is currently active.
     * Integration is considered active unless explicitly set to DISABLED.
     *
     * @return bool
     */
    public function isIntegrationActive()
    {
        $status = $this->getIntegrationStatus();
        if($status === null) {
            return true;
        }
        if($status !== 'DISABLED') {
            return true;
        }

        return false;
    }

    /**
     * Determines whether the configuration entry is system specific.
     *
     * @param string $name Configuration entry name.
     *
     * @return bool
     */
    protected function isSystemSpecific($name)
    {
        if ($name === 'maxTaskAge') {
            return false;
        }

        return parent::isSystemSpecific($name);
    }
}
