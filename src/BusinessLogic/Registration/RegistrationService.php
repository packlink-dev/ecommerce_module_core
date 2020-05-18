<?php

namespace Packlink\BusinessLogic\Registration;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException;

/**
 * Class RegistrationService
 *
 * @package Packlink\BusinessLogic\Registration
 */
class RegistrationService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Registers a new user on Packlink PRO.
     *
     * @param \Packlink\BusinessLogic\Registration\RegistrationRequest $request
     *
     * @return string
     *
     * @throws \Packlink\BusinessLogic\Registration\Exceptions\UnableToRegisterAccountException
     */
    public function register(RegistrationRequest $request)
    {
        try {
            /** @var Proxy $proxy */
            $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

            return $proxy->register($request->toArray());
        } catch (HttpBaseException $e) {
            throw new UnableToRegisterAccountException(
                'Registration failed. Error: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }
}
