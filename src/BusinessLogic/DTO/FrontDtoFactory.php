<?php

namespace Packlink\BusinessLogic\DTO;

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;

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
    protected static $registry = array();

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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException If type is not a subclass of
     *     FrontDto.
     */
    public static function register($key, $class)
    {
        if (!is_subclass_of($class, FrontDto::CLASS_NAME)) {
            throw new FrontDtoFactoryRegistrationException("Class $class is not implementation of FrontDto.");
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
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     *  When DTO class is not registered for given key.
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     *  When fields are not registered for DTO class or payload contains unknown fields.
     */
    public static function get($key, array $payload)
    {
        if (!self::isRegistered($key)) {
            throw new FrontDtoNotRegisteredException("DTO class is not registered for key $key.");
        }

        /** @var FrontDto $className Actually, it is a string, but this is for completion. */
        $className = self::$registry[$key];

        return $className::fromArray($payload);
    }

    /**
     * Gets the array of instances of DTO.
     *
     * @param string $key A key for the DTO type.
     * @param array $payload Data to fill in the instantiated DTO.
     *
     * @return FrontDto[] Instantiated DTOs.
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public static function getFromBatch($key, array $payload)
    {
        if (!self::isRegistered($key)) {
            throw new FrontDtoNotRegisteredException("DTO class is not registered for key $key.");
        }

        /** @var FrontDto $className Actually, it is a string, but this is for code completion purpose. */
        $className = self::$registry[$key];

        return $className::fromBatch($payload);
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
