<?php

namespace Packlink\BusinessLogic\IntegrationRegistration;

/**
 * Interface IntegrationRegistrationServiceInterface. Must be implemented in integration.
 */
interface IntegrationRegistrationServiceInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Registers the integration with Packlink and saves
     * integration ID from the response into ConfigEntity.
     *
     * @return string|null Integration identifier or null if request fails
     */
    public function registerIntegration();

    /**
     * Disconnects the integration from Packlink.
     */
    public function disconnectIntegration();

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
     * Returns the persisted integration ID if present as class variable,
     * otherwise, if returns the ID from database if present.
     *
     * @return string Integration ID.
     */
    public function getIntegrationId();

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
     * @return string Integration name.
     */
    public function getIntegrationWebhookStatusUpdateUrl();
}
