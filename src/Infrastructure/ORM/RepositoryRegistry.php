<?php

namespace Logeecom\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Entities\QueueItem;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\Interfaces\QueueItemRepository;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;

/**
 * Class RepositoryRegistry
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
     * @param string $class
     *
     * @return RepositoryInterface
     * @throws RepositoryNotRegisteredException
     */
    public static function getRepository($class)
    {
        if (!array_key_exists($class, self::$repositories)) {
            throw new RepositoryNotRegisteredException("Repository for entity $class not found or registered.");
        }

        if (!array_key_exists($class, self::$instantiated)) {
            $repositoryClass = self::$repositories[$class];
            /** @var RepositoryInterface $repository */
            $repository = new $repositoryClass();
            $repository->setEntityClass($class);
            self::$instantiated[$class] = $repository;
        }

        return self::$instantiated[$class];
    }

    /**
     * Registers repository for provided entity class
     *
     * @param string $class
     * @param string $repositoryClass
     *
     * @throws RepositoryClassException
     */
    public static function registerRepository($class, $repositoryClass)
    {
        if (!is_subclass_of($repositoryClass, RepositoryInterface::CLASS_NAME)) {
            throw new RepositoryClassException("Class $repositoryClass is not implementation of RepositoryInterface.");
        }

        unset(self::$instantiated[$class]);
        self::$repositories[$class] = $repositoryClass;
    }

    /**
     * Returns queue item repository
     *
     * @return QueueItemRepository
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
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
