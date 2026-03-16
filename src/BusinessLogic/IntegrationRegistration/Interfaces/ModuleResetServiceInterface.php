<?php

namespace Packlink\BusinessLogic\IntegrationRegistration\Interfaces;

interface ModuleResetServiceInterface
{
    /**
     * Fully qualified name of this interface.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Erases integration data keeping the module/plugin/app installed
     * in the target shop system, available for a new Packlink connection.
     *
     * @return bool
     */
    public function resetModule();
}