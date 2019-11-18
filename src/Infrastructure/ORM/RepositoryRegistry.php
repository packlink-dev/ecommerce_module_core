<?php

namespace Logeecom\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\TaskExecution\QueueItem;

/**
 * Class RepositoryRegistry.
 *
 * @package Logeecom\Infrastructure\ORM
 */
class RepositoryRegistry
{
    /**
     * @var RepositoryInterface[]
     */
    protected static $instantiated = array();
    /**
     * @var array
     */
    protected static $repositories = array();

    /**
     * Returns an instance of repository that is responsible for handling the entity
     *
     * @param string $entityClass Class name of entity.
     *
     * @return RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function getRepository($entityClass)
    {
        if (!self::isRegistered($entityClass)) {
            throw new RepositoryNotRegisteredException("Repository for entity $entityClass not found or registered.");
        }

        if (!array_key_exists($entityClass, self::$instantiated)) {
            $repositoryClass = self::$repositories[$entityClass];
            /** @var RepositoryInterface $repository */
            $repository = new $repositoryClass();
            $repository->setEntityClass($entityClass);
            self::$instantiated[$entityClass] = $repository;
        }

        return self::$instantiated[$entityClass];
    }

    /**
     * Registers repository for provided entity class
     *
     * @param string $entityClass Class name of entity.
     * @param string $repositoryClass Class name of repository.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    public static function registerRepository($entityClass, $repositoryClass)
    {
        if (!is_subclass_of($repositoryClass, RepositoryInterface::CLASS_NAME)) {
            throw new RepositoryClassException("Class $repositoryClass is not implementation of RepositoryInterface.");
        }

        unset(self::$instantiated[$entityClass]);
        self::$repositories[$entityClass] = $repositoryClass;
    }

    /**
     * Checks whether repository has been registered for a particular entity.
     *
     * @param string $entityClass Entity for which check has to be performed.
     *
     * @return boolean Returns TRUE if repository has been registered; FALSE otherwise.
     */
    public static function isRegistered($entityClass)
    {
        return isset(self::$repositories[$entityClass]);
    }

    /**
     * Returns queue item repository.
     *
     * @return QueueItemRepository
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function getQueueItemRepository()
    {
        /** @var QueueItemRepository $repository */
        $repository = self::getRepository(QueueItem::getClassName());
        if (!($repository instanceof QueueItemRepository)) {
            throw new RepositoryClassException('Instance class is not implementation of QueueItemRepository');
        }

        return $repository;
    }
}
