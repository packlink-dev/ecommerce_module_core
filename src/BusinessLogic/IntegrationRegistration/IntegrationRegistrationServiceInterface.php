<?php

namespace Packlink\BusinessLogic\IntegrationRegistration;

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
}
