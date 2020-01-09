<?php

namespace Packlink\BusinessLogic\DTO;

use Packlink\BusinessLogic\DTO\Exceptions\DtoFactoryRegistrationException;
use Packlink\BusinessLogic\DTO\Exceptions\DtoNotRegisteredException;

/**
 * Class DtoFactory.
 *
 * @package Packlink\BusinessLogic\DTO
 */
class FrontDtoFactory
{
    /**
     * A registry of key - DTO type mapping.
     *
     * @var array
     */
    protected static $registry;

    /**
     * DtoFactory constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Registers Front DTO class for a specific key.
     *
     * @param string $key A key for the DTO type.
     * @param string $class A class to be instantiated.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\DtoFactoryRegistrationException If type is not a subclass of
     *     FrontDto.
     */
    public static function register($key, $class)
    {
        if (!is_subclass_of($class, FrontDto::CLASS_NAME)) {
            throw new DtoFactoryRegistrationException("Class $class is not implementation of FrontDto.");
        }

        self::$registry[$key] = $class;
    }

    /**
     * Gets the instance of the DTO.
     *
     * @param string $key A key for the DTO type.
     * @param array $payload Data to fill in the instantiated DTO.
     *
     * @return FrontDto Instantiated DTO.
     */
    public static function get($key, array $payload)
    {
        if (!self::isRegistered($key)) {
            throw new DtoNotRegisteredException("DTO class is not registered for key $key.");
        }

        /** @var FrontDto $className Actually, it is a string, but this is for completion. */
        $className = self::$registry[$key];

        return $className::fromArray($payload);
    }

    /**
     * Checks if a class is registered for a given key.
     *
     * @param string $key The DTO key.
     *
     * @return bool TRUE if the class is registered; otherwise, FALSE.
     */
    protected static function isRegistered($key)
    {
        return array_key_exists($key, self::$registry);
    }
}
