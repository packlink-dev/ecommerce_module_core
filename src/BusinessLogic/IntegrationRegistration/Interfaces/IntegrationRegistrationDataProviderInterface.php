<?php

namespace Packlink\BusinessLogic\IntegrationRegistration\Interfaces;

/**
 * Interface IntegrationRegistrationDataProviderInterface. Must be implemented in integration.
 *
 * Provides shop-specific data needed for integration registration with Packlink.
 * Integration ID storage is handled by Configuration, not by this interface.
 */
interface IntegrationRegistrationDataProviderInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Returns the persisted integration GUID.
     *
     * @return string Integration GUID.
     */
    public function getIntegrationGuid();

    /**
     * Returns the persisted webhook secret.
     *
     * @return string Webhook secret used for authentication.
     */
    public function getWebhookSecret();

    /**
     * Returns the integration type (e.g. Prestashop, WooCommerce...).
     *
     * @return string Integration type.
     */
    public function getIntegrationType();

    /**
     * Returns the name of the integration.
     *
     * @return string Integration name.
     */
    public function getIntegrationName();

    /**
     * Returns the WebhookStatusUpdateUrl.
     *
     * @return string Webhook status update URL.
     */
    public function getIntegrationWebhookStatusUpdateUrl();

    /**
     * Removes integration registration data from the database.
     *
     * @return void
     */
    public function deleteIntegrationData();
}
