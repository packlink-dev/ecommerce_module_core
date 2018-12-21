<?php

namespace Logeecom\Tests\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Entities\QueueItem;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use PHPUnit\Framework\TestCase;

/***
 * Class RepositoryRegistryTest
 * @package Logeecom\Tests\Infrastructure\ORM
 */
class RepositoryRegistryTest extends TestCase
{
    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepository()
    {
        RepositoryRegistry::registerRepository('test', MemoryRepository::getClassName());

        $repository = RepositoryRegistry::getRepository('test');
        $this->assertInstanceOf(MemoryRepository::getClassName(), $repository);
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepositoryWrongRepo()
    {
        RepositoryRegistry::registerRepository('test', MemoryQueueItemRepository::getClassName());

        $repository = RepositoryRegistry::getRepository('test');
        $this->assertNotEquals(MemoryRepository::getClassName(), $repository);
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    public function testRegisterRepositoryWrongRepoClass()
    {
        RepositoryRegistry::registerRepository('test', '\PHPUnit\Framework\TestCase');
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepositoryNotRegistered()
    {
        RepositoryRegistry::getRepository('test2');
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testGetQueueItemRepository()
    {
        RepositoryRegistry::registerRepository(QueueItem::getClassName(), MemoryQueueItemRepository::getClassName());

        $repository = RepositoryRegistry::getQueueItemRepository();
        $this->assertInstanceOf(MemoryQueueItemRepository::getClassName(), $repository);
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testGetQueueItemRepositoryException()
    {
        RepositoryRegistry::registerRepository(QueueItem::getClassName(), MemoryRepository::getClassName());

        RepositoryRegistry::getQueueItemRepository();
    }
}
