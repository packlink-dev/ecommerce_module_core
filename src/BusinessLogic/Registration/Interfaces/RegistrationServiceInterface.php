<?php

namespace Packlink\BusinessLogic\Registration\Interfaces;

use Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException;
use Packlink\BusinessLogic\Registration\RegistrationRequest;

interface RegistrationServiceInterface
{

    /**
     * Registers a new user on Packlink PRO.
     *
     * @param RegistrationRequest $request
     *
     * @return string
     *
     * @throws UnableToRegisterAccountException
     */
    public function register(RegistrationRequest $request);
}