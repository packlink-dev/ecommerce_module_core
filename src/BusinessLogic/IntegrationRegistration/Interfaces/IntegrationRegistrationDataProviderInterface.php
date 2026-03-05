<?php

namespace Packlink\BusinessLogic\IntegrationRegistration\Interfaces;

/**
 * Interface IntegrationRegistrationDataProviderInterface. Must be implemented in integration.
 */
interface IntegrationRegistrationDataProviderInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @return array Payload.
     */
    public function getRegistrationPayload();

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
     * @return string|null Integration ID.
     */
    public function getIntegrationId();

    /**
     * Saves Integration Identifier to database
     *
     * @param string $integrationId
     *
     * @return void
     */
    public function setIntegrationId($integrationId);

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
