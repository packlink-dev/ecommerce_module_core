<?php

namespace Packlink\BusinessLogic\IntegrationRegistration\Interfaces;

use Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException;

/**
 * Interface IntegrationRegistrationServiceInterface.
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
     *
     * @throws IntegrationNotRegisteredException
     */
    public function registerIntegration();

    /**
     * Disconnects the integration from Packlink.
     */
    public function disconnectIntegration();

    /**
     * Returns the persisted integration ID if present as class variable,
     * otherwise, if returns the ID from database if present.
     *
     * @return string|null Integration ID.
     */
    public function getIntegrationId();
}
