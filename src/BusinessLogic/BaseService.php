<?php

namespace Packlink\BusinessLogic;

use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Singleton;

/**
 * Base class for all services. Initializes service as a singleton instance.
 *
 * @package Packlink\BusinessLogic
 */
abstract class BaseService extends Singleton
{
    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Returns an instance of repository for entity.
     *
     * @param string $entityClass Name of entity class.
     *
     * @return RepositoryInterface Instance of a repository.
     */
    protected function getRepository($entityClass)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return RepositoryRegistry::getRepository($entityClass);
    }
}
