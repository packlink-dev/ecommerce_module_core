<?php

namespace Logeecom\Infrastructure;

use Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException;
use Logeecom\Infrastructure\Exceptions\ServiceNotRegisteredException;

/**
 * Class ServiceRegister.
 *
 * @package Logeecom\Infrastructure
 */
class ServiceRegister
{
    /**
     * Service register instance.
     *
     * @var ServiceRegister
     */
    private static $instance;
    /**
     * Array of registered services.
     *
     * @var array
     */
    protected $services;

    /**
     * ServiceRegister constructor.
     *
     * @param array $services
     *
     * @throws \InvalidArgumentException
     *  In case delegate of a registered service is not a callable.
     */
    protected function __construct($services = array())
    {
        if (!empty($services)) {
            foreach ($services as $type => $service) {
                try {
                    $this->register($type, $service);
                } catch (ServiceAlreadyRegisteredException $e) {
                    // this cannot happen in constructor because it cannot be that array of services
                    // has 2 keys that are the same
                }
            }
        }

        self::$instance = $this;
    }

    /**
     * Getting service register instance
     *
     * @return ServiceRegister
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ServiceRegister();
        }

        return self::$instance;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Gets service for specified type.
     *
     * @param string $type Type of service. Should be fully qualified class name.
     *
     * @return object Instance of service.
     */
    public static function getService($type)
    {
        // Unhandled exception warning suppressed on purpose so that all classes using service
        // would not need @throws tag.
        /** @noinspection PhpUnhandledExceptionInspection */
        return self::getInstance()->get($type);
    }

    /**
     * Registers service with delegate as second parameter which represents function for creating new service instance.
     *
     * @param string $type Type of service. Should be fully qualified class name.
     * @param callable $delegate Delegate that will give instance of registered service.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
     *  In case service for specified type is already registered.
     * @throws \InvalidArgumentException
     *  In case delegate is not a callable.
     */
    public static function registerService($type, $delegate)
    {
        self::getInstance()->register($type, $delegate);
    }

    /**
     * Register service class.
     *
     * @param string $type Type of service. Should be fully qualified class name.
     * @param callable $delegate Delegate that will give instance of registered service.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceAlreadyRegisteredException
     *  In case service for specified type is already registered.
     * @throws \InvalidArgumentException
     *  In case delegate is not a callable.
     */
    protected function register($type, $delegate)
    {
        if (!empty($this->services[$type])) {
            throw new ServiceAlreadyRegisteredException($type);
        }

        if (!is_callable($delegate)) {
            throw new \InvalidArgumentException("$type delegate is not callable.");
        }

        $this->services[$type] = $delegate;
    }

    /**
     * Getting service instance.
     *
     * @param string $type Type of service. Should be fully qualified class name.
     *
     * @return object Instance of service.
     *
     * @throws \Logeecom\Infrastructure\Exceptions\ServiceNotRegisteredException
     */
    protected function get($type)
    {
        if (empty($this->services[$type])) {
            throw new ServiceNotRegisteredException($type);
        }

        return call_user_func($this->services[$type]);
    }
}
